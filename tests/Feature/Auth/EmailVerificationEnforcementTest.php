<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\CheckActiveSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(CheckActiveSession::class);
    }

    public function test_unverified_student_is_not_blocked_and_can_dispatch_to_student_dashboard(): void
    {
        $user = User::factory()->unverified()->create([
            'role' => User::ROLE_STUDENT,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        // Unverified user is directly dispatched to their role dashboard, never to verification.notice
        $response->assertRedirect(route('student.dashboard'));
    }

    public function test_unverified_student_can_access_student_dashboard(): void
    {
        $user = User::factory()->unverified()->create([
            'role' => User::ROLE_STUDENT,
        ]);

        $response = $this->actingAs($user)->get('/student/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }

    public function test_unverified_student_can_access_student_classrooms(): void
    {
        $user = User::factory()->unverified()->create([
            'role' => User::ROLE_STUDENT,
        ]);

        $response = $this->actingAs($user)->get('/student/classrooms');

        $response->assertStatus(200);
    }

    public function test_unverified_teacher_can_access_teacher_dashboard(): void
    {
        $teacher = User::factory()->unverified()->create([
            'role' => User::ROLE_TEACHER,
        ]);

        $response = $this->actingAs($teacher)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }

    public function test_unverified_teacher_can_access_classroom_management(): void
    {
        $teacher = User::factory()->unverified()->create([
            'role' => User::ROLE_TEACHER,
        ]);

        $response = $this->actingAs($teacher)->get('/teacher/classrooms');

        $response->assertStatus(200);
    }

    public function test_unverified_user_can_access_profile(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertStatus(200);
    }

    public function test_unverified_user_can_access_test_route(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/test');

        $response->assertStatus(200);
        $response->assertSee('Start Test');
    }

    public function test_user_can_still_manually_view_verification_notice_if_requested(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'student.pending@example.com',
        ]);

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
        $response->assertSee('Verify Your Email Address');
    }
}
