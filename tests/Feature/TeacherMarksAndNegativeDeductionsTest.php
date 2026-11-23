<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckActiveSession;
use App\Models\CheatingEvent;
use App\Models\Classroom;
use App\Models\ClassroomTest;
use App\Models\ExamAttempt;
use App\Models\MarkDeductionAudit;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\StudentAnswer;
use App\Models\Test;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TeacherMarksAndNegativeDeductionsTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $otherTeacher;
    private User $student;
    private Classroom $classroom;
    private Test $test;
    private ClassroomTest $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(CheckActiveSession::class);

        // Create Users
        $this->teacher = User::create([
            'name' => 'Prof. Katherine Johnson',
            'email' => 'johnson@securetest.local',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_TEACHER,
            'email_verified_at' => now(),
        ]);

        $this->otherTeacher = User::create([
            'name' => 'Prof. Dorothy Vaughan',
            'email' => 'vaughan@securetest.local',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_TEACHER,
            'email_verified_at' => now(),
        ]);

        $this->student = User::create([
            'name' => 'Mary Jackson',
            'email' => 'jackson@securetest.local',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);

        // Create Classroom
        $this->classroom = Classroom::create([
            'user_id' => $this->teacher->id,
            'name' => 'Orbital Mechanics 401',
            'code' => 'ORBIT401',
        ]);
        $this->classroom->students()->attach($this->student->id);

        // Create Test with 20 Total Marks
        $this->test = Test::create([
            'user_id' => $this->teacher->id,
            'title' => 'Trajectory Dynamics Exam',
            'duration_minutes' => 60,
            'total_marks' => 20,
        ]);

        // Assign Test to Classroom with deadline in future initially
        $this->classroom->tests()->attach($this->test->id, [
            'starts_at' => Carbon::now()->subHours(2),
            'due_at' => Carbon::now()->addHours(2), // Active, deadline in future
            'allow_late_submissions' => false,
            'is_active' => true,
        ]);

        $this->assignment = ClassroomTest::where('classroom_id', $this->classroom->id)
            ->where('test_id', $this->test->id)
            ->firstOrFail();
    }

    /**
     * 1. Teacher cannot access marks list before the deadline has passed (403 Forbidden).
     */
    public function test_teacher_cannot_access_marks_list_before_deadline(): void
    {
        $this->actingAs($this->teacher);

        $response = $this->get(route('teacher.classrooms.tests.marks', [$this->classroom, $this->test]));

        $response->assertStatus(403);
    }

    /**
     * 2. Teacher cannot export CSV marks report before the deadline has passed (403 Forbidden).
     */
    public function test_teacher_cannot_export_csv_before_deadline(): void
    {
        $this->actingAs($this->teacher);

        $response = $this->get(route('teacher.classrooms.tests.marks.export-csv', [$this->classroom, $this->test]));

        $response->assertStatus(403);
    }

    /**
     * 3. Teacher can access marks list after the deadline has passed (200 OK).
     */
    public function test_teacher_can_access_marks_list_after_deadline(): void
    {
        // Advance past deadline
        $this->assignment->update([
            'due_at' => Carbon::now()->subMinutes(10),
        ]);

        // Create an attempt for the student
        $attempt = ExamAttempt::create([
            'classroom_id' => $this->classroom->id,
            'test_id' => $this->test->id,
            'user_id' => $this->student->id,
            'status' => ExamAttempt::STATUS_SUBMITTED,
            'score' => 17.50,
            'total_marks' => 20.00,
            'started_at' => Carbon::now()->subHour(),
            'submitted_at' => Carbon::now()->subMinutes(20),
        ]);

        $this->actingAs($this->teacher);

        $response = $this->get(route('teacher.classrooms.tests.marks', [$this->classroom, $this->test]));

        $response->assertOk();
        $response->assertSee('Student Marks: Trajectory Dynamics Exam');
        $response->assertSee('Mary Jackson');
        $response->assertSee('17.50');
        // Verify negative marks input has wheel prevention, decimal step, and script listener
        $response->assertSee('name="negative_marks"', false);
        $response->assertSee('onwheel="this.blur(); event.preventDefault();"', false);
        $response->assertSee('marks-input', false);
        $response->assertSee('step="any"', false);
        $response->assertSee('input.addEventListener(\'wheel\'', false);

        // Verify that NO invented roll number is shown (since users table has no roll_number)
        $response->assertDontSee('STU-');
    }

    /**
     * 4. Unauthorized teacher cannot access marks list or export CSV even after deadline.
     */
    public function test_unauthorized_teacher_cannot_access_marks_list_or_export(): void
    {
        $this->assignment->update([
            'due_at' => Carbon::now()->subMinutes(10),
        ]);

        $this->actingAs($this->otherTeacher);

        // Marks list
        $marksResponse = $this->get(route('teacher.classrooms.tests.marks', [$this->classroom, $this->test]));
        $marksResponse->assertStatus(403);

        // CSV export
        $csvResponse = $this->get(route('teacher.classrooms.tests.marks.export-csv', [$this->classroom, $this->test]));
        $csvResponse->assertStatus(403);
    }

    /**
     * 5. Students cannot access teacher marks list or export CSV routes.
     */
    public function test_students_cannot_access_teacher_marks_routes(): void
    {
        $this->assignment->update([
            'due_at' => Carbon::now()->subMinutes(10),
        ]);

        $this->actingAs($this->student);

        $marksResponse = $this->get(route('teacher.classrooms.tests.marks', [$this->classroom, $this->test]));
        $marksResponse->assertStatus(403);

        $csvResponse = $this->get(route('teacher.classrooms.tests.marks.export-csv', [$this->classroom, $this->test]));
        $csvResponse->assertStatus(403);
    }

    /**
     * 6. Teacher can apply negative marks, preserving original exam score and storing audit record.
     */
    public function test_teacher_can_apply_negative_marks_with_audit_trail(): void
    {
        $attempt = ExamAttempt::create([
            'classroom_id' => $this->classroom->id,
            'test_id' => $this->test->id,
            'user_id' => $this->student->id,
            'status' => ExamAttempt::STATUS_SUBMITTED,
            'score' => 18.00,
            'total_marks' => 20.00,
            'started_at' => Carbon::now()->subHours(2),
            'submitted_at' => Carbon::now()->subHour(),
        ]);

        $this->actingAs($this->teacher);

        $response = $this->post(route('teacher.classrooms.tests.attempts.negative-marks', [
            $this->classroom,
            $this->test,
            $attempt,
        ]), [
            'negative_marks' => 2.50,
            'reason' => 'Unauthorized reference material observed during remote session review.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $attempt->refresh();

        // 1. Original exam marks are preserved
        $this->assertEquals(18.00, $attempt->score);

        // 2. Negative marks are stored
        $this->assertEquals(2.50, $attempt->negative_marks);
        $this->assertSame('Unauthorized reference material observed during remote session review.', $attempt->negative_marks_reason);

        // 3. Final Marks = Exam Marks - Negative Marks (18.00 - 2.50 = 15.50)
        $this->assertEquals(15.50, $attempt->final_marks);

        // 4. Audit record was created in database
        $this->assertDatabaseHas('mark_deduction_audits', [
            'exam_attempt_id' => $attempt->id,
            'user_id' => $this->teacher->id,
            'original_score' => 18.00,
            'negative_marks' => 2.50,
            'final_marks' => 15.50,
            'reason' => 'Unauthorized reference material observed during remote session review.',
        ]);

        $this->assertCount(1, $attempt->markDeductionAudits);
    }

    /**
     * 7. Negative marks validation: deductions cannot be negative, cannot exceed original exam score, and require reason.
     */
    public function test_negative_marks_validation_rules(): void
    {
        $attempt = ExamAttempt::create([
            'classroom_id' => $this->classroom->id,
            'test_id' => $this->test->id,
            'user_id' => $this->student->id,
            'status' => ExamAttempt::STATUS_SUBMITTED,
            'score' => 12.00,
            'total_marks' => 20.00,
        ]);

        $this->actingAs($this->teacher);

        // A. Deduction below 0 is rejected
        $belowZero = $this->post(route('teacher.classrooms.tests.attempts.negative-marks', [
            $this->classroom,
            $this->test,
            $attempt,
        ]), [
            'negative_marks' => -1.00,
            'reason' => 'Invalid negative deduction test.',
        ]);
        $belowZero->assertSessionHasErrors('negative_marks');

        // B. Deduction exceeding original score is rejected (12.50 > 12.00)
        $exceedsScore = $this->post(route('teacher.classrooms.tests.attempts.negative-marks', [
            $this->classroom,
            $this->test,
            $attempt,
        ]), [
            'negative_marks' => 12.50,
            'reason' => 'Deduction exceeding total score.',
        ]);
        $exceedsScore->assertSessionHasErrors('negative_marks');

        // C. Missing reason is rejected
        $missingReason = $this->post(route('teacher.classrooms.tests.attempts.negative-marks', [
            $this->classroom,
            $this->test,
            $attempt,
        ]), [
            'negative_marks' => 2.00,
            'reason' => '',
        ]);
        $missingReason->assertSessionHasErrors('reason');

        // D. Unauthorized teacher cannot apply negative marks
        $this->actingAs($this->otherTeacher);
        $unauth = $this->post(route('teacher.classrooms.tests.attempts.negative-marks', [
            $this->classroom,
            $this->test,
            $attempt,
        ]), [
            'negative_marks' => 1.00,
            'reason' => 'Unauthorized adjustment attempt.',
        ]);
        $unauth->assertStatus(403);
    }

    /**
     * 8. Cheating events NEVER deduct marks automatically.
     */
    public function test_cheating_events_never_deduct_marks_automatically(): void
    {
        $attempt = ExamAttempt::create([
            'classroom_id' => $this->classroom->id,
            'test_id' => $this->test->id,
            'user_id' => $this->student->id,
            'status' => ExamAttempt::STATUS_IN_PROGRESS,
            'score' => 15.00,
            'negative_marks' => 0.00,
            'total_marks' => 20.00,
        ]);

        $this->actingAs($this->student);

        // Post several cheating events with high suspicion scores
        $this->postJson(route('cheating-event.store'), [
            'event_type' => 'fullscreen_timeout',
            'suspicion_score' => 10,
            'exam_attempt_id' => $attempt->id,
        ])->assertOk();

        $this->postJson(route('cheating-event.store'), [
            'event_type' => 'tab_switch',
            'suspicion_score' => 5,
            'exam_attempt_id' => $attempt->id,
        ])->assertOk();

        $attempt->refresh();

        // Scores and negative marks MUST remain completely unchanged by proctoring events
        $this->assertEquals(15.00, $attempt->score);
        $this->assertEquals(0.00, $attempt->negative_marks);
        $this->assertEquals(15.00, $attempt->final_marks);
    }

    /**
     * 9. Downloadable CSV marks report format, headers, filename, Excel BOM, and absence of negative marks column.
     */
    public function test_csv_marks_report_export_format_and_contents(): void
    {
        $this->assignment->update([
            'due_at' => Carbon::now()->subMinutes(30),
        ]);

        // Student attempt with negative mark deduction
        $attempt = ExamAttempt::create([
            'classroom_id' => $this->classroom->id,
            'test_id' => $this->test->id,
            'user_id' => $this->student->id,
            'status' => ExamAttempt::STATUS_SUBMITTED,
            'score' => 16.50,
            'negative_marks' => 1.50,
            'negative_marks_reason' => 'Late tab alert confirmed.',
            'total_marks' => 20.00,
            'started_at' => Carbon::now()->subHours(2),
            'submitted_at' => Carbon::now()->subHour(),
        ]);

        $this->actingAs($this->teacher);

        $response = $this->get(route('teacher.classrooms.tests.marks.export-csv', [$this->classroom, $this->test]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // Verify Filename includes classroom name and test title
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment;', $contentDisposition);
        $this->assertStringContainsString('orbital_mechanics_401', $contentDisposition);
        $this->assertStringContainsString('trajectory_dynamics_exam', $contentDisposition);
        $this->assertStringContainsString('.csv', $contentDisposition);

        $streamContent = $response->streamedContent();

        // 1. Starts with UTF-8 BOM (\xEF\xBB\xBF) for Excel compatibility
        $this->assertStringStartsWith("\xEF\xBB\xBF", $streamContent);

        // 2. Header contains classroom name and test title
        $this->assertStringContainsString('Classroom,"Orbital Mechanics 401"', $streamContent);
        $this->assertStringContainsString('"Test Title","Trajectory Dynamics Exam"', $streamContent);

        // 3. Table contains Student Name and Final Marks (16.50 - 1.50 = 15.00)
        $this->assertStringContainsString('Mary Jackson', $streamContent);
        $this->assertStringContainsString('15.00', $streamContent);

        // 4. Does NOT include a separate Negative Marks column header
        $this->assertStringNotContainsString('Negative Marks', $streamContent);
        $this->assertStringNotContainsString('negative_marks', $streamContent);
    }
}
