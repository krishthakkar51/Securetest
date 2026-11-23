<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckActiveSession;
use App\Models\Classroom;
use App\Models\ClassroomJoinRequest;
use App\Models\ClassroomTest;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomEmailRestrictionTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $otherTeacher;
    private User $studentMatching;
    private User $studentNonMatching;
    private User $studentFakeDomain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(CheckActiveSession::class);

        $this->teacher = User::create([
            'name' => 'Prof. Charles Babbage',
            'email' => 'babbage_' . time() . '@securetest.local',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_TEACHER,
            'email_verified_at' => now(),
        ]);

        $this->otherTeacher = User::create([
            'name' => 'Prof. Grace Hopper',
            'email' => 'hopper_' . time() . '@securetest.local',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_TEACHER,
            'email_verified_at' => now(),
        ]);

        $this->studentMatching = User::create([
            'name' => 'Ada Lovelace',
            'email' => 'ada_' . time() . '@college.edu',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);

        $this->studentNonMatching = User::create([
            'name' => 'Bob Smith',
            'email' => 'bob_' . time() . '@gmail.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);

        $this->studentFakeDomain = User::create([
            'name' => 'Eve Attacker',
            'email' => 'eve_' . time() . '@fakecollege.edu',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * 1. Teacher can create a classroom with email domain restriction enabled.
     */
    public function test_teacher_can_create_classroom_with_email_restriction(): void
    {
        $this->actingAs($this->teacher);

        $response = $this->post(route('teacher.classrooms.store'), [
            'name' => 'Algorithms & Data Structures',
            'description' => 'Restricted to college students only.',
            'is_email_restricted' => '1',
            'allowed_email_domain' => '@college.edu',
        ]);

        $classroom = Classroom::where('name', 'Algorithms & Data Structures')->first();
        $this->assertNotNull($classroom);
        $this->assertTrue($classroom->is_email_restricted);
        $this->assertSame('@college.edu', $classroom->allowed_email_domain);
        $response->assertRedirect(route('teacher.classrooms.show', $classroom));
    }

    /**
     * 2. Teacher can create an unrestricted classroom.
     */
    public function test_teacher_can_create_unrestricted_classroom(): void
    {
        $this->actingAs($this->teacher);

        $response = $this->post(route('teacher.classrooms.store'), [
            'name' => 'Open Course',
            'description' => 'Anyone can join.',
            'is_email_restricted' => '0',
            'allowed_email_domain' => '',
        ]);

        $classroom = Classroom::where('name', 'Open Course')->first();
        $this->assertNotNull($classroom);
        $this->assertFalse($classroom->is_email_restricted);
        $this->assertNull($classroom->allowed_email_domain);
        $response->assertRedirect(route('teacher.classrooms.show', $classroom));
    }

    /**
     * 3. Student with matching email joins directly.
     */
    public function test_student_with_matching_email_joins_restricted_classroom_directly(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher->id,
            'name' => 'Restricted Lab',
            'code' => Classroom::generateUniqueCode(),
            'is_email_restricted' => true,
            'allowed_email_domain' => '@college.edu',
        ]);

        $this->actingAs($this->studentMatching);

        $response = $this->post(route('student.classrooms.join'), [
            'code' => $classroom->code,
        ]);

        $response->assertRedirect(route('student.classrooms.show', $classroom));
        $this->assertTrue($classroom->hasStudent($this->studentMatching->id));
        $this->assertDatabaseMissing('classroom_join_requests', [
            'classroom_id' => $classroom->id,
            'user_id' => $this->studentMatching->id,
        ]);
    }

    /**
     * 4. Edge Case 4: Exact domain matching prevents substring and fake domain matches.
     */
    public function test_exact_domain_matching_rejects_substring_and_fake_domains(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher->id,
            'name' => 'Restricted Lab',
            'code' => Classroom::generateUniqueCode(),
            'is_email_restricted' => true,
            'allowed_email_domain' => '@college.edu',
        ]);

        // 1. Eve with @fakecollege.edu must NOT join directly
        $this->actingAs($this->studentFakeDomain);
        $response = $this->post(route('student.classrooms.join'), [
            'code' => $classroom->code,
        ]);

        $response->assertRedirect(route('student.classrooms.index'));
        $this->assertFalse($classroom->hasStudent($this->studentFakeDomain->id));
        $this->assertDatabaseHas('classroom_join_requests', [
            'classroom_id' => $classroom->id,
            'user_id' => $this->studentFakeDomain->id,
            'status' => ClassroomJoinRequest::STATUS_PENDING,
        ]);

        // 2. Bob with @gmail.com must NOT join directly
        $this->actingAs($this->studentNonMatching);
        $response2 = $this->post(route('student.classrooms.join'), [
            'code' => $classroom->code,
        ]);

        $response2->assertRedirect(route('student.classrooms.index'));
        $this->assertFalse($classroom->hasStudent($this->studentNonMatching->id));
        $this->assertDatabaseHas('classroom_join_requests', [
            'classroom_id' => $classroom->id,
            'user_id' => $this->studentNonMatching->id,
            'status' => ClassroomJoinRequest::STATUS_PENDING,
        ]);
    }

    /**
     * 5. Prevent duplicate active requests when student resubmits join code while pending.
     */
    public function test_resubmitting_join_code_while_pending_does_not_create_duplicate(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher->id,
            'name' => 'Restricted Lab',
            'code' => Classroom::generateUniqueCode(),
            'is_email_restricted' => true,
            'allowed_email_domain' => '@college.edu',
        ]);

        $this->actingAs($this->studentNonMatching);

        // First attempt creates request
        $this->post(route('student.classrooms.join'), ['code' => $classroom->code]);
        $this->assertCount(1, ClassroomJoinRequest::where('classroom_id', $classroom->id)->where('user_id', $this->studentNonMatching->id)->get());

        // Second attempt notices pending request and does not duplicate
        $response = $this->post(route('student.classrooms.join'), ['code' => $classroom->code]);
        $response->assertRedirect(route('student.classrooms.index'));
        $response->assertSessionHas('info');
        $this->assertCount(1, ClassroomJoinRequest::where('classroom_id', $classroom->id)->where('user_id', $this->studentNonMatching->id)->get());
    }

    /**
     * 6. Edge Case 3: Resubmitting join code after rejection updates existing record to pending.
     */
    public function test_resubmitting_join_code_after_rejection_updates_record_to_pending(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher->id,
            'name' => 'Restricted Lab',
            'code' => Classroom::generateUniqueCode(),
            'is_email_restricted' => true,
            'allowed_email_domain' => '@college.edu',
        ]);

        $request = ClassroomJoinRequest::create([
            'classroom_id' => $classroom->id,
            'user_id' => $this->studentNonMatching->id,
            'status' => ClassroomJoinRequest::STATUS_REJECTED,
            'reviewed_by' => $this->teacher->id,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($this->studentNonMatching);

        // Resubmitting join code
        $response = $this->post(route('student.classrooms.join'), [
            'code' => $classroom->code,
        ]);

        $response->assertRedirect(route('student.classrooms.index'));
        $response->assertSessionHas('info');

        // Verify only 1 record exists and its status transitioned back to pending
        $this->assertCount(1, ClassroomJoinRequest::where('classroom_id', $classroom->id)->where('user_id', $this->studentNonMatching->id)->get());
        $request->refresh();
        $this->assertSame(ClassroomJoinRequest::STATUS_PENDING, $request->status);
        $this->assertNull($request->reviewed_at);
        $this->assertNull($request->reviewed_by);
    }

    /**
     * 7. Student with pending or rejected request cannot access protected classroom routes.
     */
    public function test_student_with_pending_or_rejected_request_cannot_access_classroom_resources(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher->id,
            'name' => 'Restricted Lab',
            'code' => Classroom::generateUniqueCode(),
            'is_email_restricted' => true,
            'allowed_email_domain' => '@college.edu',
        ]);

        $test = Test::create([
            'user_id' => $this->teacher->id,
            'title' => 'Midterm Exam',
            'duration_minutes' => 60,
            'total_marks' => 100,
        ]);

        ClassroomTest::create([
            'classroom_id' => $classroom->id,
            'test_id' => $test->id,
            'is_active' => true,
        ]);

        // Request is pending
        ClassroomJoinRequest::create([
            'classroom_id' => $classroom->id,
            'user_id' => $this->studentNonMatching->id,
            'status' => ClassroomJoinRequest::STATUS_PENDING,
        ]);

        $this->actingAs($this->studentNonMatching);

        // Direct show route blocked
        $this->get(route('student.classrooms.show', $classroom))->assertStatus(403);

        // Direct test show route blocked
        $this->get(route('student.classrooms.tests.show', [$classroom, $test]))->assertStatus(403);

        // Direct take route blocked
        $this->get(route('student.classrooms.tests.take', [$classroom, $test]))->assertStatus(403);
    }

    /**
     * 8. Edge Case 1: Enabling restriction on an existing classroom does NOT break existing enrollments.
     */
    public function test_enabling_restriction_later_preserves_existing_student_enrollments(): void
    {
        // Classroom starts unrestricted
        $classroom = Classroom::create([
            'user_id' => $this->teacher->id,
            'name' => 'Originally Open Classroom',
            'code' => Classroom::generateUniqueCode(),
            'is_email_restricted' => false,
        ]);

        // Student with @gmail.com joins while open
        $classroom->students()->attach($this->studentNonMatching->id);
        $this->assertTrue($classroom->hasStudent($this->studentNonMatching->id));

        // Teacher edits classroom and restricts to @college.edu
        $this->actingAs($this->teacher);
        $this->put(route('teacher.classrooms.update', $classroom), [
            'name' => 'Now Restricted Classroom',
            'is_email_restricted' => '1',
            'allowed_email_domain' => '@college.edu',
        ]);

        $classroom->refresh();
        $this->assertTrue($classroom->is_email_restricted);
        $this->assertSame('@college.edu', $classroom->allowed_email_domain);

        // Student's existing enrollment is preserved
        $this->assertTrue($classroom->hasStudent($this->studentNonMatching->id));

        // Student can still access classroom
        $this->actingAs($this->studentNonMatching);
        $this->get(route('student.classrooms.show', $classroom))->assertOk();
    }

    /**
     * 9. Teacher can view, accept, reject, and mark requests as pending.
     */
    public function test_teacher_can_review_and_update_join_request_statuses(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher->id,
            'name' => 'Physics 101',
            'code' => Classroom::generateUniqueCode(),
            'is_email_restricted' => true,
            'allowed_email_domain' => '@college.edu',
        ]);

        $joinRequest = ClassroomJoinRequest::create([
            'classroom_id' => $classroom->id,
            'user_id' => $this->studentNonMatching->id,
            'status' => ClassroomJoinRequest::STATUS_PENDING,
        ]);

        // Teacher views classroom show page and sees student name and email
        $this->actingAs($this->teacher);
        $showResponse = $this->get(route('teacher.classrooms.show', $classroom));
        $showResponse->assertOk();
        $showResponse->assertSee($this->studentNonMatching->name);
        $showResponse->assertSee($this->studentNonMatching->email);
        $showResponse->assertSee('Access Requests');
        $showResponse->assertSee('1 Pending');

        // Other teacher cannot review requests
        $this->actingAs($this->otherTeacher);
        $this->post(route('teacher.classrooms.join-requests.update', [$classroom, $joinRequest]), [
            'status' => 'accepted',
        ])->assertStatus(403);

        // Teacher accepts the request
        $this->actingAs($this->teacher);
        $acceptResponse = $this->post(route('teacher.classrooms.join-requests.update', [$classroom, $joinRequest]), [
            'status' => 'accepted',
        ]);
        $acceptResponse->assertRedirect();
        $joinRequest->refresh();
        $this->assertTrue($joinRequest->isAccepted());
        $this->assertSame($this->teacher->id, $joinRequest->reviewed_by);
        $this->assertNotNull($joinRequest->reviewed_at);
        $this->assertTrue($classroom->hasStudent($this->studentNonMatching->id));

        // Student can now access the classroom
        $this->actingAs($this->studentNonMatching);
        $this->get(route('student.classrooms.show', $classroom))->assertOk();

        // Edge Case 2: Teacher changes status to rejected -> student access is revoked
        $this->actingAs($this->teacher);
        $rejectResponse = $this->post(route('teacher.classrooms.join-requests.update', [$classroom, $joinRequest]), [
            'status' => 'rejected',
        ]);
        $rejectResponse->assertRedirect();
        $joinRequest->refresh();
        $this->assertTrue($joinRequest->isRejected());
        $this->assertFalse($classroom->hasStudent($this->studentNonMatching->id));

        // Direct access is now blocked (403)
        $this->actingAs($this->studentNonMatching);
        $this->get(route('student.classrooms.show', $classroom))->assertStatus(403);

        // Edge Case 2: Teacher changes status back to pending -> student access remains revoked
        $this->actingAs($this->teacher);
        $pendingResponse = $this->post(route('teacher.classrooms.join-requests.update', [$classroom, $joinRequest]), [
            'status' => 'pending',
        ]);
        $pendingResponse->assertRedirect();
        $joinRequest->refresh();
        $this->assertTrue($joinRequest->isPending());
        $this->assertFalse($classroom->hasStudent($this->studentNonMatching->id));

        $this->actingAs($this->studentNonMatching);
        $this->get(route('student.classrooms.show', $classroom))->assertStatus(403);
    }

    /**
     * 10. Student sees their access request status on student classrooms index view.
     */
    public function test_student_sees_request_status_on_classrooms_index(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher->id,
            'name' => 'Chemistry 202',
            'code' => Classroom::generateUniqueCode(),
            'is_email_restricted' => true,
            'allowed_email_domain' => '@college.edu',
        ]);

        ClassroomJoinRequest::create([
            'classroom_id' => $classroom->id,
            'user_id' => $this->studentNonMatching->id,
            'status' => ClassroomJoinRequest::STATUS_PENDING,
        ]);

        $this->actingAs($this->studentNonMatching);

        $response = $this->get(route('student.classrooms.index'));
        $response->assertOk();
        $response->assertSee('My Classroom Access Requests');
        $response->assertSee('Chemistry 202');
        $response->assertSee('Pending');
        $response->assertDontSee('Pending Review');
        $response->assertDontSee('@college.edu');
    }
}
