<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckActiveSession;
use App\Models\CheatingEvent;
use App\Models\Classroom;
use App\Models\ClassroomJoinRequest;
use App\Models\ClassroomTest;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\StudentAnswer;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomStudentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $otherTeacher;
    private User $student1;
    private User $student2;
    private Classroom $classroom1;
    private Classroom $classroom2;
    private Test $test1;

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

        $this->student1 = User::create([
            'name' => 'Ada Lovelace',
            'email' => 'ada_' . time() . '@securetest.local',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);

        $this->student2 = User::create([
            'name' => 'Alan Turing',
            'email' => 'alan_' . time() . '@securetest.local',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);

        $this->classroom1 = Classroom::create([
            'user_id' => $this->teacher->id,
            'name' => 'Computer Architecture 101',
            'code' => Classroom::generateUniqueCode(),
            'is_email_restricted' => false,
        ]);

        $this->classroom2 = Classroom::create([
            'user_id' => $this->otherTeacher->id,
            'name' => 'Parallel Computing 201',
            'code' => Classroom::generateUniqueCode(),
            'is_email_restricted' => false,
        ]);

        $this->test1 = Test::create([
            'user_id' => $this->teacher->id,
            'title' => 'Pipelining & Caching Exam',
            'duration_minutes' => 60,
            'total_marks' => 20,
        ]);

        ClassroomTest::create([
            'classroom_id' => $this->classroom1->id,
            'test_id' => $this->test1->id,
            'is_active' => true,
        ]);
    }

    /**
     * 1. Guest and unauthorized roles cannot remove students.
     */
    public function test_student_removal_requires_auth_and_teacher_role(): void
    {
        $this->classroom1->students()->attach($this->student1->id);

        // Guest
        $this->delete(route('teacher.classrooms.students.remove', [$this->classroom1, $this->student1]))
            ->assertRedirect(route('login'));

        // Student cannot access teacher removal route
        $this->actingAs($this->student2);
        $this->delete(route('teacher.classrooms.students.remove', [$this->classroom1, $this->student1]))
            ->assertStatus(403);
    }

    /**
     * 2. Non-owner teacher cannot remove student from another teacher's classroom.
     */
    public function test_non_owner_teacher_cannot_remove_student(): void
    {
        $this->classroom1->students()->attach($this->student1->id);

        $this->actingAs($this->otherTeacher);
        $this->delete(route('teacher.classrooms.students.remove', [$this->classroom1, $this->student1]))
            ->assertStatus(403);

        $this->assertTrue($this->classroom1->fresh()->hasStudent($this->student1->id));
    }

    /**
     * 3. Classroom owner can remove student; user account and other classroom enrollments are preserved.
     */
    public function test_classroom_owner_can_remove_student_preserving_account_and_other_classes(): void
    {
        // Enroll student in both classrooms
        $this->classroom1->students()->attach($this->student1->id);
        $this->classroom2->students()->attach($this->student1->id);

        $this->actingAs($this->teacher);
        $response = $this->delete(route('teacher.classrooms.students.remove', [$this->classroom1, $this->student1]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Removed from classroom 1
        $this->assertFalse($this->classroom1->fresh()->hasStudent($this->student1->id));

        // Preserved in classroom 2
        $this->assertTrue($this->classroom2->fresh()->hasStudent($this->student1->id));

        // User account intact
        $this->assertDatabaseHas('users', ['id' => $this->student1->id]);
    }

    /**
     * 4. Removal strictly preserves exam attempts, student answers, scores, and proctoring evidence.
     */
    public function test_student_removal_strictly_preserves_exam_history_and_evidence(): void
    {
        $this->classroom1->students()->attach($this->student1->id);

        // Create question, options, attempt, answers, and cheating event
        $question = Question::create([
            'test_id' => $this->test1->id,
            'question_type' => Question::TYPE_MCQ,
            'question_text' => 'What is L1 cache?',
            'marks' => 5,
            'order' => 1,
        ]);

        $option = QuestionOption::create([
            'question_id' => $question->id,
            'option_text' => 'Fast on-chip cache',
            'is_correct' => true,
            'order' => 1,
        ]);

        $attempt = ExamAttempt::create([
            'classroom_id' => $this->classroom1->id,
            'test_id' => $this->test1->id,
            'user_id' => $this->student1->id,
            'status' => ExamAttempt::STATUS_SUBMITTED,
            'score' => 5.0,
            'total_marks' => 20.0,
            'started_at' => now()->subMinutes(30),
            'submitted_at' => now()->subMinutes(5),
        ]);

        $answer = StudentAnswer::create([
            'exam_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'selected_option_id' => $option->id,
            'is_correct' => true,
            'marks_awarded' => 5.0,
            'is_graded' => true,
        ]);

        $event = CheatingEvent::create([
            'exam_attempt_id' => $attempt->id,
            'user_id' => $this->student1->id,
            'event_type' => 'tab_hidden',
            'occurred_at' => now()->subMinutes(15),
            'evidence_path' => 'evidence/test_shot.jpg',
        ]);

        // Historical join request
        $joinRequest = ClassroomJoinRequest::create([
            'classroom_id' => $this->classroom1->id,
            'user_id' => $this->student1->id,
            'status' => ClassroomJoinRequest::STATUS_ACCEPTED,
            'reviewed_by' => $this->teacher->id,
            'reviewed_at' => now()->subDays(1),
        ]);

        // Teacher removes student
        $this->actingAs($this->teacher);
        $this->delete(route('teacher.classrooms.students.remove', [$this->classroom1, $this->student1]))
            ->assertRedirect();

        // Membership detached
        $this->assertFalse($this->classroom1->fresh()->hasStudent($this->student1->id));

        // All historical records remain intact
        $this->assertDatabaseHas('exam_attempts', ['id' => $attempt->id, 'score' => 5.0]);
        $this->assertDatabaseHas('student_answers', ['id' => $answer->id, 'marks_awarded' => 5.0]);
        $this->assertDatabaseHas('cheating_events', ['id' => $event->id, 'event_type' => 'tab_hidden']);
        $this->assertDatabaseHas('classroom_join_requests', ['id' => $joinRequest->id]);
    }

    /**
     * 5. Removed student is blocked from classroom and test direct URLs with HTTP 403.
     */
    public function test_removed_student_is_blocked_from_classroom_and_tests(): void
    {
        $this->classroom1->students()->attach($this->student1->id);

        $this->actingAs($this->student1);
        $this->get(route('student.classrooms.show', $this->classroom1))->assertOk();
        $this->get(route('student.classrooms.tests.show', [$this->classroom1, $this->test1]))->assertOk();

        // Teacher removes student
        $this->actingAs($this->teacher);
        $this->delete(route('teacher.classrooms.students.remove', [$this->classroom1, $this->student1]));

        // Student tries to access routes directly
        $this->actingAs($this->student1);
        $this->get(route('student.classrooms.show', $this->classroom1))->assertStatus(403);
        $this->get(route('student.classrooms.tests.show', [$this->classroom1, $this->test1]))->assertStatus(403);
        $this->get(route('student.classrooms.tests.take', [$this->classroom1, $this->test1]))->assertStatus(403);
    }

    /**
     * 6. Active Access Requests table shows ONLY pending requests; accepted/rejected are hidden.
     */
    public function test_active_access_requests_panel_only_shows_pending_requests(): void
    {
        $pendingUser = User::create([
            'name' => 'Pending Student',
            'email' => 'pending_' . time() . '@external.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);

        $acceptedUser = User::create([
            'name' => 'Accepted Student',
            'email' => 'accepted_' . time() . '@external.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);

        $rejectedUser = User::create([
            'name' => 'Rejected Student',
            'email' => 'rejected_' . time() . '@external.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);

        ClassroomJoinRequest::create([
            'classroom_id' => $this->classroom1->id,
            'user_id' => $pendingUser->id,
            'status' => ClassroomJoinRequest::STATUS_PENDING,
        ]);

        ClassroomJoinRequest::create([
            'classroom_id' => $this->classroom1->id,
            'user_id' => $acceptedUser->id,
            'status' => ClassroomJoinRequest::STATUS_ACCEPTED,
            'reviewed_by' => $this->teacher->id,
            'reviewed_at' => now(),
        ]);

        ClassroomJoinRequest::create([
            'classroom_id' => $this->classroom1->id,
            'user_id' => $rejectedUser->id,
            'status' => ClassroomJoinRequest::STATUS_REJECTED,
            'reviewed_by' => $this->teacher->id,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($this->teacher);
        $response = $this->get(route('teacher.classrooms.show', $this->classroom1));

        $response->assertOk();

        // Button label reflects ONLY pending count (1)
        $response->assertSee('Access Requests (1)');
        $response->assertDontSee('Access Requests (3)');

        // Pending user appears in table
        $response->assertSee($pendingUser->name);
        $response->assertSee($pendingUser->email);

        // Accepted and Rejected users do NOT appear in the access requests table
        $response->assertDontSee($acceptedUser->email);
        $response->assertDontSee($rejectedUser->email);

        // Database records strictly preserved
        $this->assertDatabaseHas('classroom_join_requests', ['user_id' => $acceptedUser->id, 'status' => 'accepted']);
        $this->assertDatabaseHas('classroom_join_requests', ['user_id' => $rejectedUser->id, 'status' => 'rejected']);
        $this->assertDatabaseHas('classroom_join_requests', ['user_id' => $pendingUser->id, 'status' => 'pending']);
    }

    /**
     * 7. Collapsible panels are collapsed by default on initial page load and contain toggle controls.
     */
    public function test_collapsible_panels_are_hidden_by_default_with_toggle_controls(): void
    {
        $this->classroom1->students()->attach($this->student1->id);

        $this->actingAs($this->teacher);
        $response = $this->get(route('teacher.classrooms.show', $this->classroom1));

        $response->assertOk();

        // Enrolled Students button label
        $response->assertSee('Enrolled Students (1)');

        // Access Requests button label
        $response->assertSee('Access Requests (0)');

        // Both panels are collapsed by default (hidden class present)
        $response->assertSee('id="enrolledStudentsPanel" class="hidden', false);
        $response->assertSee('id="accessRequestsPanel" class="hidden', false);

        // Toggle buttons have click triggers and aria controls
        $response->assertSee('onclick="toggleClassroomPanel(\'students\')"', false);
        $response->assertSee('onclick="toggleClassroomPanel(\'requests\')"', false);

        // Remove student form exists with confirmation prompt
        $response->assertSee(route('teacher.classrooms.students.remove', [$this->classroom1, $this->student1]));
        $response->assertSee('confirm(', false);
    }
}
