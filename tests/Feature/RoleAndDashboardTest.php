<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckActiveSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test public registration always assigns student role server-side.
     */
    public function test_public_registration_always_assigns_student_role(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Student Test',
            'email' => 'registered_student_' . time() . '@securetest.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'teacher', // Malicious attempt to self-promote to teacher
        ]);

        $user = User::where('email', 'like', 'registered_student_%')->latest()->first();

        $this->assertNotNull($user);
        $this->assertEquals(User::ROLE_STUDENT, $user->role);
        $this->assertTrue($user->isStudent());
        $this->assertFalse($user->isTeacher());
    }

    /**
     * Test unauthenticated users cannot access student or teacher dashboard.
     */
    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $response = $this->get('/student/dashboard');
        $response->assertRedirect('/login');

        $response = $this->get('/teacher/dashboard');
        $response->assertRedirect('/login');
    }

    /**
     * Test student can access student dashboard but cannot access teacher dashboard.
     */
    public function test_student_role_authorization_and_boundary(): void
    {
        $this->withoutMiddleware(CheckActiveSession::class);

        $student = User::create([
            'name' => 'Boundary Student',
            'email' => 'boundary_student_' . time() . '@securetest.local',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($student);

        // Student accessing student dashboard -> 200 OK
        $response = $this->get('/student/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertDontSee('Student Dashboard');
        $response->assertDontSee('Student Portal');

        // Student accessing teacher dashboard -> 403 Forbidden
        $response = $this->get('/teacher/dashboard');
        $response->assertStatus(403);

        // Student hitting /dashboard dispatcher -> redirects to /student/dashboard
        $response = $this->get('/dashboard');
        $response->assertRedirect(route('student.dashboard'));
    }

    /**
     * Test teacher can access teacher dashboard but cannot access student dashboard.
     */
    public function test_teacher_role_authorization_and_boundary(): void
    {
        $this->withoutMiddleware(CheckActiveSession::class);

        $teacher = User::create([
            'name' => 'Boundary Teacher',
            'email' => 'boundary_teacher_' . time() . '@securetest.local',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_TEACHER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($teacher);

        // Teacher accessing teacher dashboard -> 200 OK
        $response = $this->get('/teacher/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertDontSee('Teacher Dashboard');
        $response->assertSee('+ Create Class');
        $response->assertSee('Enter class code');
        $response->assertSee('Join Class');
        $response->assertDontSee('Instructor Portal');

        // Teacher accessing student dashboard -> 403 Forbidden
        $response = $this->get('/student/dashboard');
        $response->assertStatus(403);

        // Teacher hitting /dashboard dispatcher -> redirects to /teacher/dashboard
        $response = $this->get('/dashboard');
        $response->assertRedirect(route('teacher.dashboard'));
    }

    /**
     * Test proctoring /test route remains accessible to authenticated users.
     */
    public function test_proctoring_test_route_is_accessible(): void
    {
        $this->withoutMiddleware(CheckActiveSession::class);

        $user = User::create([
            'name' => 'Proctoring Student',
            'email' => 'proctor_student_' . time() . '@securetest.local',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/test');
        $response->assertStatus(200);
        $response->assertSee('Start Test');
        $response->assertSee('webcam');
    }

    /**
     * Test user:make-teacher CLI command safely validates and promotes.
     */
    public function test_make_teacher_command_behavior(): void
    {
        // 1. Invalid email
        $this->artisan('user:make-teacher', ['email' => 'invalid-email'])
            ->expectsOutput("Invalid email address format: 'invalid-email'.")
            ->assertFailed();

        // 2. Non-existent email
        $this->artisan('user:make-teacher', ['email' => 'notfound@securetest.local'])
            ->expectsOutput("No user found with the email: 'notfound@securetest.local'.")
            ->assertFailed();

        // 3. Existing student promotion with decline
        $student = User::create([
            'name' => 'Candidate Teacher',
            'email' => 'candidate_' . time() . '@securetest.local',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STUDENT,
        ]);

        $this->artisan('user:make-teacher', ['email' => $student->email])
            ->expectsConfirmation("Are you sure you want to promote '{$student->name}' ({$student->email}) to TEACHER?", 'no')
            ->expectsOutput('Promotion canceled. No changes were made.')
            ->assertSuccessful();

        $this->assertEquals(User::ROLE_STUDENT, $student->fresh()->role);

        // 4. Existing student promotion with confirmation
        $this->artisan('user:make-teacher', ['email' => $student->email])
            ->expectsConfirmation("Are you sure you want to promote '{$student->name}' ({$student->email}) to TEACHER?", 'yes')
            ->expectsOutput("Success! User '{$student->name}' ({$student->email}) is now a TEACHER.")
            ->assertSuccessful();

        $this->assertEquals(User::ROLE_TEACHER, $student->fresh()->role);
        $this->assertTrue($student->fresh()->isTeacher());
    }

    /**
     * Test registration screen renders properly with guest auth meta tag and without redirect.
     */
    public function test_registration_screen_renders_with_guest_auth_meta_and_does_not_redirect(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('name="auth-check" content="0"', false);
        $response->assertSee('name="name"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="password_confirmation"', false);
    }

    /**
     * Test incomplete registration stays on the register form with validation errors.
     */
    public function test_incomplete_registration_stays_on_form_with_errors(): void
    {
        // Missing email and password confirmation from /register
        $response = $this->from('/register')->post('/register', [
            'name' => 'Partial Name',
            'email' => '',
            'password' => 'secret123',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
        // Redirects back to /register (NOT to /login)
        $response->assertRedirect('/register');
    }

    /**
     * Test registration completes, logs in as student, creates ActiveSession, and reaches Student Dashboard.
     */
    public function test_successful_registration_logs_in_student_with_active_session(): void
    {
        $this->withoutMiddleware(CheckActiveSession::class);

        $email = 'fresh_student_' . time() . '@securetest.local';

        $response = $this->post('/register', [
            'name' => 'Complete Student',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertEquals(User::ROLE_STUDENT, $user->role);

        // Verify ActiveSession was created for the new user
        $activeSession = \App\Models\ActiveSession::where('user_id', $user->id)->first();
        $this->assertNotNull($activeSession);
        $this->assertEquals($user->id, $activeSession->user_id);
        $this->assertNotNull($activeSession->last_activity);

        // Visiting /dashboard as newly registered student redirects directly to student dashboard (no verification notice gating)
        $dashResponse = $this->actingAs($user)->get('/dashboard');
        $dashResponse->assertRedirect(route('student.dashboard'));
    }
}
