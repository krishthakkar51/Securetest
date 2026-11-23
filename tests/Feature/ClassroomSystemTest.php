<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckActiveSession;
use App\Models\Classroom;
use App\Models\ClassroomTest;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\StudentAnswer;
use App\Models\Test;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher1;
    private User $teacher2;
    private User $student1;
    private User $student2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(CheckActiveSession::class);

        $this->teacher1 = User::create([
            'name' => 'Prof. Charles Babbage',
            'email' => 'babbage_' . time() . '@securetest.local',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_TEACHER,
            'email_verified_at' => now(),
        ]);

        $this->teacher2 = User::create([
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
            'name' => 'John von Neumann',
            'email' => 'john_' . time() . '@securetest.local',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * 1. Teacher can create classrooms and a unique join code is generated.
     */
    public function test_teacher_can_create_classroom_with_unique_code(): void
    {
        $this->actingAs($this->teacher1);

        $response = $this->post('/teacher/classrooms', [
            'name' => 'Operating Systems 101',
            'description' => 'Concurrency, semaphores, and memory virtualization.',
        ]);

        $classroom = Classroom::where('name', 'Operating Systems 101')->first();
        $this->assertNotNull($classroom);
        $this->assertSame($this->teacher1->id, $classroom->user_id);
        $this->assertNotEmpty($classroom->code);
        $this->assertSame(7, strlen($classroom->code));

        $response->assertRedirect('/teacher/classrooms/' . $classroom->id);
    }

    /**
     * Under Option A, students can also create and own classrooms.
     */
    public function test_students_can_create_and_own_classrooms_under_ownership_model(): void
    {
        $this->actingAs($this->student1);

        $this->get('/teacher/classrooms')->assertStatus(200);
        $this->get('/teacher/classrooms/create')->assertStatus(200);
        $response = $this->post('/teacher/classrooms', [
            'name' => 'Student Founded Lab',
            'description' => 'A study group created by student1',
        ]);

        $classroom = Classroom::where('name', 'Student Founded Lab')->first();
        $this->assertNotNull($classroom);
        $this->assertSame($this->student1->id, $classroom->user_id);
        $response->assertRedirect('/teacher/classrooms/' . $classroom->id);
    }

    /**
     * 2. Teacher classroom isolation: Teachers can only view/manage their own classrooms.
     */
    public function test_teachers_cannot_view_or_manage_other_teachers_classrooms(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Babbage Private Lab',
            'code' => Classroom::generateUniqueCode(),
        ]);

        // Teacher 2 attempts to view Teacher 1's classroom
        $this->actingAs($this->teacher2);
        $this->get("/teacher/classrooms/{$classroom->id}")->assertStatus(403);

        // Teacher 2 attempts to create test in Teacher 1's classroom
        $this->get("/teacher/classrooms/{$classroom->id}/tests/create")->assertStatus(403);
        $this->post("/teacher/classrooms/{$classroom->id}/tests/create", [
            'title' => 'Compiler Design',
        ])->assertStatus(403);
    }

    /**
     * 3. Student enrollment with valid code.
     */
    public function test_student_can_enroll_with_valid_join_code(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Distributed Systems',
            'code' => 'DIST101',
        ]);

        $this->actingAs($this->student1);

        $response = $this->post('/student/classrooms/join', [
            'code' => 'DIST101',
        ]);

        $response->assertRedirect("/student/classrooms/{$classroom->id}");
        $this->assertTrue($classroom->hasStudent($this->student1->id));
        $this->assertSame(1, $classroom->students()->count());
    }

    /**
     * Student rejected with invalid code.
     */
    public function test_student_enrollment_rejected_with_invalid_code(): void
    {
        $this->actingAs($this->student1);

        $response = $this->from('/student/classrooms')->post('/student/classrooms/join', [
            'code' => 'NONEXIST',
        ]);

        $response->assertRedirect('/student/classrooms');
        $response->assertSessionHas('error');
    }

    /**
     * Duplicate enrollment is prevented safely.
     */
    public function test_duplicate_enrollment_is_prevented(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Data Structures',
            'code' => 'DATA777',
        ]);

        $classroom->students()->attach($this->student1->id);

        $this->actingAs($this->student1);

        $response = $this->post('/student/classrooms/join', [
            'code' => 'DATA777',
        ]);

        $response->assertRedirect("/student/classrooms/{$classroom->id}");
        $response->assertSessionHas('info');
        $this->assertSame(1, $classroom->students()->where('users.id', $this->student1->id)->count());
    }

    /**
     * Under Option A, teachers can join other teachers' classrooms as students,
     * but classroom owners cannot join their own classrooms as students.
     */
    public function test_teachers_can_join_other_classrooms_as_students_but_not_their_own(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Machine Learning',
            'code' => 'ML99999',
        ]);

        // Teacher 2 joins Teacher 1's classroom
        $this->actingAs($this->teacher2);

        $response = $this->post('/student/classrooms/join', [
            'code' => 'ML99999',
        ]);

        $response->assertRedirect("/student/classrooms/{$classroom->id}");
        $this->assertTrue($classroom->fresh()->hasStudent($this->teacher2->id));

        // Teacher 1 attempts to join their own classroom as a student
        $this->actingAs($this->teacher1);
        $ownerJoinResponse = $this->post('/student/classrooms/join', [
            'code' => 'ML99999',
        ]);
        $ownerJoinResponse->assertRedirect();
        $this->assertFalse($classroom->fresh()->hasStudent($this->teacher1->id));
    }

    /**
     * 4. Teacher assigns test to classroom with availability window.
     */
    public function test_teacher_can_assign_test_with_availability_window(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Cryptography',
            'code' => 'CRYP202',
        ]);

        $startsAt = Carbon::now()->addHour()->toDateTimeString();
        $dueAt = Carbon::now()->addDays(2)->toDateTimeString();

        $this->actingAs($this->teacher1);

        $response = $this->post("/teacher/classrooms/{$classroom->id}/tests/create", [
            'title' => 'RSA and Elliptic Curves Exam',
            'duration_minutes' => 60,
            'starts_at' => $startsAt,
            'due_at' => $dueAt,
            'allow_late_submissions' => 0,
            'questions' => [
                [
                    'text' => 'Explain RSA public key generation.',
                    'type' => 'text',
                    'marks' => 30,
                ],
            ],
        ]);

        $response->assertRedirect("/teacher/classrooms/{$classroom->id}");

        $assignment = ClassroomTest::where('classroom_id', $classroom->id)
            ->whereHas('test', fn ($q) => $q->where('title', 'RSA and Elliptic Curves Exam'))
            ->first();

        $this->assertNotNull($assignment);
        $this->assertTrue($assignment->is_active);
        $this->assertNotNull($assignment->starts_at);
        $this->assertNotNull($assignment->due_at);
    }

    /**
     * Same test can be assigned to multiple classrooms independently without duplicating questions.
     */
    public function test_same_test_can_be_assigned_to_multiple_classrooms(): void
    {
        $classroomA = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Section A',
            'code' => 'SECTA01',
        ]);

        $classroomB = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Section B',
            'code' => 'SECTB02',
        ]);

        $test = Test::create([
            'user_id' => $this->teacher1->id,
            'title' => 'Midterm Examination',
            'duration_minutes' => 60,
            'total_marks' => 50,
        ]);

        $classroomA->tests()->attach($test->id, ['starts_at' => now(), 'due_at' => now()->addDay()]);
        $classroomB->tests()->attach($test->id, ['starts_at' => now()->addDays(2), 'due_at' => now()->addDays(3)]);

        $this->assertTrue($classroomA->hasTest($test->id));
        $this->assertTrue($classroomB->hasTest($test->id));
        $this->assertSame(2, $test->classrooms()->count());
    }

    /**
     * Teacher can unassign a test from classroom.
     */
    public function test_teacher_can_unassign_test_from_classroom(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Algorithms',
            'code' => 'ALGO555',
        ]);

        $test = Test::create([
            'user_id' => $this->teacher1->id,
            'title' => 'Sorting Algorithms Quiz',
            'duration_minutes' => 20,
            'total_marks' => 10,
        ]);

        $classroom->tests()->attach($test->id);
        $this->assertTrue($classroom->hasTest($test->id));

        $this->actingAs($this->teacher1);
        $response = $this->delete("/teacher/classrooms/{$classroom->id}/tests/{$test->id}");

        $response->assertRedirect("/teacher/classrooms/{$classroom->id}");
        $this->assertFalse($classroom->fresh()->hasTest($test->id));
    }

    /**
     * 5. Student access restriction: Students can only view tests assigned to enrolled classrooms.
     */
    public function test_student_only_sees_tests_assigned_to_enrolled_classrooms(): void
    {
        $enrolledClassroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Enrolled Classroom',
            'code' => 'ENROLL1',
        ]);
        $enrolledClassroom->students()->attach($this->student1->id);

        $unenrolledClassroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Secret Classroom',
            'code' => 'SECRET2',
        ]);

        $testEnrolled = Test::create([
            'user_id' => $this->teacher1->id,
            'title' => 'Visible Enrolled Exam',
            'duration_minutes' => 30,
            'total_marks' => 20,
            'is_published' => true,
        ]);
        $enrolledClassroom->tests()->attach($testEnrolled->id, ['is_active' => true]);

        $testUnenrolled = Test::create([
            'user_id' => $this->teacher1->id,
            'title' => 'Hidden Unenrolled Exam',
            'duration_minutes' => 30,
            'total_marks' => 20,
            'is_published' => true,
        ]);
        $unenrolledClassroom->tests()->attach($testUnenrolled->id, ['is_active' => true]);

        $this->actingAs($this->student1);

        $response = $this->get('/student/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Visible Enrolled Exam');
        $response->assertDontSee('Hidden Unenrolled Exam');

        // Student cannot view details of unenrolled classroom
        $this->get("/student/classrooms/{$unenrolledClassroom->id}")->assertStatus(403);

        // Student cannot view or take test from unenrolled classroom
        $this->get("/student/classrooms/{$unenrolledClassroom->id}/tests/{$testUnenrolled->id}")->assertStatus(403);
        $this->get("/student/classrooms/{$unenrolledClassroom->id}/tests/{$testUnenrolled->id}/take")->assertStatus(403);
    }

    /**
     * 6. Student cannot access test outside availability window.
     */
    public function test_student_cannot_access_test_outside_availability_window(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Physics II',
            'code' => 'PHYS201',
        ]);
        $classroom->students()->attach($this->student1->id);

        // Test scheduled in the future (starts tomorrow)
        $futureTest = Test::create([
            'user_id' => $this->teacher1->id,
            'title' => 'Quantum Mechanics Exam',
            'duration_minutes' => 60,
            'total_marks' => 100,
        ]);

        $classroom->tests()->attach($futureTest->id, [
            'starts_at' => Carbon::now()->addDay(),
            'due_at' => Carbon::now()->addDays(2),
            'is_active' => true,
        ]);

        $this->actingAs($this->student1);

        // Accessing take test should redirect back with error
        $response = $this->get("/student/classrooms/{$classroom->id}/tests/{$futureTest->id}/take");
        $response->assertRedirect("/student/classrooms/{$classroom->id}/tests/{$futureTest->id}");
        $response->assertSessionHas('error');
    }

    /**
     * 7. Student can take exam, submit answers, MCQ auto-grading works, and text answers saved.
     */
    public function test_student_can_take_exam_and_mcq_auto_grading_calculates_score(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Web Development',
            'code' => 'WEB301X',
        ]);
        $classroom->students()->attach($this->student1->id);

        $test = Test::create([
            'user_id' => $this->teacher1->id,
            'title' => 'HTTP & Web Protocols Test',
            'duration_minutes' => 30,
            'total_marks' => 15,
        ]);

        // MCQ Question: 5 Marks
        $mcq = Question::create([
            'test_id' => $test->id,
            'question_type' => Question::TYPE_MCQ,
            'question_text' => 'Which HTTP status code signifies Not Found?',
            'marks' => 5,
            'order' => 1,
        ]);

        $optA = QuestionOption::create([
            'question_id' => $mcq->id,
            'option_text' => '200 OK',
            'is_correct' => false,
            'order' => 1,
        ]);

        $optB = QuestionOption::create([
            'question_id' => $mcq->id,
            'option_text' => '404 Not Found',
            'is_correct' => true,
            'order' => 2,
        ]);

        // Written Text Question: 10 Marks
        $textQ = Question::create([
            'test_id' => $test->id,
            'question_type' => Question::TYPE_TEXT,
            'question_text' => 'Explain the TLS handshake process.',
            'marks' => 10,
            'order' => 2,
        ]);

        $classroom->tests()->attach($test->id, ['is_active' => true]);

        $this->actingAs($this->student1);

        // Student opens take exam route
        $takeResponse = $this->get("/student/classrooms/{$classroom->id}/tests/{$test->id}/take");
        $takeResponse->assertStatus(200);
        $takeResponse->assertSee('Which HTTP status code signifies Not Found?');
        $takeResponse->assertSee('Explain the TLS handshake process.');

        // Student submits answers: selects correct MCQ option B, and types text response
        $submitResponse = $this->post("/student/classrooms/{$classroom->id}/tests/{$test->id}/submit", [
            'answers' => [
                $mcq->id => $optB->id,
                $textQ->id => 'The client sends a ClientHello, server responds with ServerHello and certificate...',
            ],
        ]);

        $submitResponse->assertRedirect("/student/classrooms/{$classroom->id}/tests/{$test->id}");
        $submitResponse->assertSessionHas('success');

        // Verify attempt record
        $attempt = ExamAttempt::where('classroom_id', $classroom->id)
            ->where('test_id', $test->id)
            ->where('user_id', $this->student1->id)
            ->first();

        $this->assertNotNull($attempt);
        $this->assertSame(ExamAttempt::STATUS_SUBMITTED, $attempt->status);
        $this->assertEquals(5, $attempt->score); // 5 marks from MCQ, text pending evaluation
        $this->assertEquals(15, $attempt->total_marks);

        // Verify MCQ answer in DB
        $mcqAns = StudentAnswer::where('exam_attempt_id', $attempt->id)
            ->where('question_id', $mcq->id)
            ->first();
        $this->assertNotNull($mcqAns);
        $this->assertSame($optB->id, $mcqAns->selected_option_id);
        $this->assertTrue($mcqAns->is_correct);
        $this->assertEquals(5, $mcqAns->marks_awarded);

        // Verify text answer in DB
        $textAns = StudentAnswer::where('exam_attempt_id', $attempt->id)
            ->where('question_id', $textQ->id)
            ->first();
        $this->assertNotNull($textAns);
        $this->assertStringContainsString('ClientHello', $textAns->text_answer);
        $this->assertNull($textAns->is_correct);
    }

    /**
     * 8. Teacher can view student results for the classroom test.
     */
    public function test_teacher_can_view_student_submissions_and_scores(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Database Systems',
            'code' => 'DBS401A',
        ]);
        $classroom->students()->attach($this->student1->id);

        $test = Test::create([
            'user_id' => $this->teacher1->id,
            'title' => 'SQL and Indexing Quiz',
            'duration_minutes' => 15,
            'total_marks' => 10,
        ]);

        $classroom->tests()->attach($test->id, ['is_active' => true]);

        ExamAttempt::create([
            'classroom_id' => $classroom->id,
            'test_id' => $test->id,
            'user_id' => $this->student1->id,
            'status' => ExamAttempt::STATUS_SUBMITTED,
            'score' => 10,
            'total_marks' => 10,
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->teacher1);

        $response = $this->get("/teacher/classrooms/{$classroom->id}/tests/{$test->id}/results");
        $response->assertStatus(200);
        $response->assertSee('Results: SQL and Indexing Quiz');
        $response->assertSee('Ada Lovelace');
        $response->assertSee('10 / 10');

        // Teacher 2 cannot view Teacher 1's results
        $this->actingAs($this->teacher2);
        $this->get("/teacher/classrooms/{$classroom->id}/tests/{$test->id}/results")->assertStatus(403);
    }

    /**
     * 9. Teacher can view the edit classroom form prefilled with current values.
     */
    public function test_teacher_can_view_edit_classroom_form_prefilled(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Original Name 101',
            'description' => 'Original Syllabus Description.',
            'code' => 'ORIG101',
        ]);

        $this->actingAs($this->teacher1);

        $response = $this->get("/teacher/classrooms/{$classroom->id}/edit");
        $response->assertStatus(200);
        $response->assertSee('Edit Classroom');
        $response->assertSee('Original Name 101');
        $response->assertSee('Original Syllabus Description.');
        $response->assertSee('ORIG101');
    }

    /**
     * Teacher can successfully update classroom name and description.
     */
    public function test_teacher_can_update_classroom_name_and_description(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Old Cohort Name',
            'description' => 'Old course description.',
            'code' => 'COHORT7',
        ]);

        // Enroll a student and assign a test to verify relationships remain intact
        $classroom->students()->attach($this->student1->id);

        $test = Test::create([
            'user_id' => $this->teacher1->id,
            'title' => 'Assigned Test',
            'duration_minutes' => 30,
            'total_marks' => 20,
        ]);
        $classroom->tests()->attach($test->id);

        $this->actingAs($this->teacher1);

        $response = $this->put("/teacher/classrooms/{$classroom->id}", [
            'name' => 'Renamed Advanced Cohort',
            'description' => 'Updated syllabus and schedule for 2026.',
        ]);

        $response->assertRedirect("/teacher/classrooms/{$classroom->id}");
        $response->assertSessionHas('success');

        $classroom->refresh();
        $this->assertSame('Renamed Advanced Cohort', $classroom->name);
        $this->assertSame('Updated syllabus and schedule for 2026.', $classroom->description);
        $this->assertSame('COHORT7', $classroom->code); // Join code preserved
        $this->assertSame($this->teacher1->id, $classroom->user_id); // Teacher owner preserved
        $this->assertTrue($classroom->hasStudent($this->student1->id)); // Enrolled students preserved
        $this->assertTrue($classroom->hasTest($test->id)); // Test assignments preserved
    }

    /**
     * Validation rules for editing classroom.
     */
    public function test_classroom_rename_validation_rules(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Valid Title',
            'code' => 'VALID01',
        ]);

        $this->actingAs($this->teacher1);

        // 1. Empty name
        $response1 = $this->put("/teacher/classrooms/{$classroom->id}", [
            'name' => '',
            'description' => 'Valid description',
        ]);
        $response1->assertSessionHasErrors(['name']);

        // 2. Name too long (> 255 chars)
        $response2 = $this->put("/teacher/classrooms/{$classroom->id}", [
            'name' => str_repeat('A', 256),
        ]);
        $response2->assertSessionHasErrors(['name']);

        // 3. Description too long (> 2000 chars)
        $response3 = $this->put("/teacher/classrooms/{$classroom->id}", [
            'name' => 'Valid Title',
            'description' => str_repeat('B', 2001),
        ]);
        $response3->assertSessionHasErrors(['description']);

        // Record remained unchanged
        $this->assertSame('Valid Title', $classroom->fresh()->name);
    }

    /**
     * Another teacher cannot view or submit edit form for another teacher's classroom.
     */
    public function test_another_teacher_cannot_edit_or_update_classroom(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Babbage Classroom',
            'code' => 'BABB123',
        ]);

        $this->actingAs($this->teacher2);

        // Cannot view edit form
        $this->get("/teacher/classrooms/{$classroom->id}/edit")->assertStatus(403);

        // Cannot submit update
        $response = $this->put("/teacher/classrooms/{$classroom->id}", [
            'name' => 'Hacked Classroom Name',
        ]);
        $response->assertStatus(403);

        $this->assertSame('Babbage Classroom', $classroom->fresh()->name);
    }

    /**
     * Students cannot access edit or update classroom routes.
     */
    public function test_students_cannot_access_edit_or_update_classroom(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Physics Class',
            'code' => 'PHYS999',
        ]);

        $this->actingAs($this->student1);

        $this->get("/teacher/classrooms/{$classroom->id}/edit")->assertStatus(403);

        $this->put("/teacher/classrooms/{$classroom->id}", [
            'name' => 'Student Rename Attempt',
        ])->assertStatus(403);

        $this->assertSame('Physics Class', $classroom->fresh()->name);
    }

    /**
     * Teacher can create a test and assign to classroom in one step with availability settings.
     */
    public function test_teacher_can_create_test_and_assign_to_classroom_with_schedule(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Algorithms',
            'code' => 'ALGO101',
        ]);

        $this->actingAs($this->teacher1);

        $startsAt = Carbon::now()->addHours(1)->format('Y-m-d H:i:s');
        $dueAt = Carbon::now()->addDays(2)->format('Y-m-d H:i:s');

        $response = $this->post('/teacher/tests', [
            'title' => 'Graph Theory Quiz',
            'description' => 'DFS and BFS fundamentals',
            'duration_minutes' => 60,
            'classroom_id' => $classroom->id,
            'starts_at' => $startsAt,
            'due_at' => $dueAt,
            'allow_late_submissions' => 1,
            'questions' => [
                [
                    'type' => 'mcq',
                    'text' => 'What is the time complexity of BFS with an adjacency list?',
                    'marks' => 5,
                    'options' => [
                        ['text' => 'O(V + E)'],
                        ['text' => 'O(V^2)'],
                    ],
                    'correct_option' => 0,
                ],
                [
                    'type' => 'text',
                    'text' => 'Explain topological sort.',
                    'marks' => 10,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $test = Test::where('title', 'Graph Theory Quiz')->first();
        $this->assertNotNull($test);
        $this->assertSame(15, $test->total_marks);

        $this->assertTrue($classroom->hasTest($test->id));
        $pivot = ClassroomTest::where('classroom_id', $classroom->id)->where('test_id', $test->id)->first();
        $this->assertNotNull($pivot);
        $this->assertTrue((bool) $pivot->allow_late_submissions);
    }

    /**
     * Assigned tests are visible to enrolled students on dashboard and classroom view.
     */
    public function test_assigned_tests_visible_to_enrolled_students(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Distributed Systems',
            'code' => 'DIST101',
        ]);

        $classroom->students()->attach($this->student1->id);

        $test = Test::create([
            'user_id' => $this->teacher1->id,
            'title' => 'Raft Consensus Test',
            'duration_minutes' => 45,
            'total_marks' => 10,
            'is_published' => true,
        ]);

        $classroom->tests()->attach($test->id, [
            'starts_at' => null,
            'due_at' => null,
            'allow_late_submissions' => false,
            'is_active' => true,
        ]);

        $this->actingAs($this->student1);

        // Dashboard view
        $dashResponse = $this->get('/student/dashboard');
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('Raft Consensus Test');
        $dashResponse->assertSee('Distributed Systems');

        // Classroom view
        $classResponse = $this->get("/student/classrooms/{$classroom->id}");
        $classResponse->assertStatus(200);
        $classResponse->assertSee('Raft Consensus Test');
    }

    /**
     * Students who enroll later also see previously assigned tests.
     */
    public function test_student_joining_later_sees_previously_assigned_tests(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Compilers 101',
            'code' => 'COMP999',
        ]);

        $test = Test::create([
            'user_id' => $this->teacher1->id,
            'title' => 'Lexical Analysis Exam',
            'duration_minutes' => 30,
            'total_marks' => 20,
            'is_published' => true,
        ]);

        // Assigned before student joins
        $classroom->tests()->attach($test->id, [
            'starts_at' => null,
            'due_at' => null,
            'allow_late_submissions' => true,
            'is_active' => true,
        ]);

        // Student joins classroom later via join code
        $this->actingAs($this->student1);
        $joinResponse = $this->post('/student/classrooms/join', [
            'code' => 'COMP999',
        ]);
        $joinResponse->assertRedirect("/student/classrooms/{$classroom->id}");

        // Test should be visible immediately
        $showResponse = $this->get("/student/classrooms/{$classroom->id}");
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Lexical Analysis Exam');

        $dashResponse = $this->get('/student/dashboard');
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('Lexical Analysis Exam');
    }

    /**
     * Classroom isolation: Students do NOT see tests from classrooms they are not enrolled in.
     */
    public function test_classroom_test_isolation(): void
    {
        $classroomA = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Classroom Alpha',
            'code' => 'ALPHA01',
        ]);

        $classroomB = Classroom::create([
            'user_id' => $this->teacher2->id,
            'name' => 'Classroom Beta',
            'code' => 'BETA002',
        ]);

        $testA = Test::create([
            'user_id' => $this->teacher1->id,
            'title' => 'Alpha Specific Exam',
            'duration_minutes' => 20,
            'total_marks' => 10,
            'is_published' => true,
        ]);

        $testB = Test::create([
            'user_id' => $this->teacher2->id,
            'title' => 'Beta Secret Exam',
            'duration_minutes' => 20,
            'total_marks' => 10,
            'is_published' => true,
        ]);

        $classroomA->tests()->attach($testA->id, ['is_active' => true]);
        $classroomB->tests()->attach($testB->id, ['is_active' => true]);

        // Student 1 enrolled only in Classroom Alpha
        $classroomA->students()->attach($this->student1->id);

        $this->actingAs($this->student1);

        $dashResponse = $this->get('/student/dashboard');
        $dashResponse->assertSee('Alpha Specific Exam');
        $dashResponse->assertDontSee('Beta Secret Exam');

        // Cannot view Classroom Beta test directly
        $this->get("/student/classrooms/{$classroomB->id}/tests/{$testB->id}")->assertStatus(403);
    }

    /**
     * Tests without start/due dates default to available immediately with no deadline.
     */
    public function test_tests_without_dates_default_to_available_immediately(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Database Internals',
            'code' => 'DBINT01',
        ]);

        $test = Test::create([
            'user_id' => $this->teacher1->id,
            'title' => 'B+ Tree Assignment',
            'duration_minutes' => 30,
            'total_marks' => 10,
            'is_published' => true,
        ]);

        $classroom->tests()->attach($test->id, [
            'starts_at' => null,
            'due_at' => null,
            'allow_late_submissions' => false,
            'is_active' => true,
        ]);

        $assignment = ClassroomTest::where('classroom_id', $classroom->id)->where('test_id', $test->id)->first();
        $this->assertTrue($assignment->isAvailable());
        $this->assertFalse($assignment->isUpcoming());
        $this->assertFalse($assignment->isPastDeadline());
        $this->assertSame('available', $assignment->status());
    }

    /**
     * Deadline enforcement: Past deadline without late submissions blocks student attempt.
     */
    public function test_past_deadline_without_late_submissions_blocks_student_attempt(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Strict History Class',
            'code' => 'HIST101',
        ]);

        $classroom->students()->attach($this->student1->id);

        $test = Test::create([
            'user_id' => $this->teacher1->id,
            'title' => 'Strict Expired Exam',
            'duration_minutes' => 30,
            'total_marks' => 10,
            'is_published' => true,
        ]);

        // Deadline was 1 hour ago, no late submissions allowed
        $classroom->tests()->attach($test->id, [
            'starts_at' => Carbon::now()->subDays(2),
            'due_at' => Carbon::now()->subHour(),
            'allow_late_submissions' => false,
            'is_active' => true,
        ]);

        $this->actingAs($this->student1);

        $response = $this->get("/student/classrooms/{$classroom->id}/tests/{$test->id}/take");
        $response->assertRedirect("/student/classrooms/{$classroom->id}/tests/{$test->id}");
        $response->assertSessionHas('error');
    }

    /**
     * Past deadline with late submissions allowed creates attempt flagged as is_late = true.
     */
    public function test_past_deadline_with_late_allowed_permits_attempt_and_flags_as_late(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Flexible Class',
            'code' => 'FLEX101',
        ]);

        $classroom->students()->attach($this->student1->id);

        $test = Test::create([
            'user_id' => $this->teacher1->id,
            'title' => 'Late Permitted Exam',
            'duration_minutes' => 30,
            'total_marks' => 10,
            'is_published' => true,
        ]);

        Question::create([
            'test_id' => $test->id,
            'question_type' => 'text',
            'question_text' => 'Describe HTTP/3.',
            'marks' => 10,
            'order' => 1,
        ]);

        // Due 2 hours ago, late submissions allowed
        $classroom->tests()->attach($test->id, [
            'starts_at' => Carbon::now()->subDays(1),
            'due_at' => Carbon::now()->subHours(2),
            'allow_late_submissions' => true,
            'is_active' => true,
        ]);

        $this->actingAs($this->student1);

        $takeResponse = $this->get("/student/classrooms/{$classroom->id}/tests/{$test->id}/take");
        $takeResponse->assertStatus(200);

        $attempt = ExamAttempt::where('classroom_id', $classroom->id)
            ->where('test_id', $test->id)
            ->where('user_id', $this->student1->id)
            ->first();

        $this->assertNotNull($attempt);
        $this->assertTrue((bool) $attempt->is_late);
    }

    /**
     * Progressive answer saving via AJAX endpoint saves responses in real time.
     */
    public function test_progressive_answer_saving_via_endpoint(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Networks 101',
            'code' => 'NET101',
        ]);

        $classroom->students()->attach($this->student1->id);

        $test = Test::create([
            'user_id' => $this->teacher1->id,
            'title' => 'Network Protocols',
            'duration_minutes' => 30,
            'total_marks' => 15,
            'is_published' => true,
        ]);

        $mcqQ = Question::create([
            'test_id' => $test->id,
            'question_type' => 'mcq',
            'question_text' => 'What layer does TCP operate at?',
            'marks' => 5,
            'order' => 1,
        ]);

        $optA = QuestionOption::create([
            'question_id' => $mcqQ->id,
            'option_text' => 'Transport',
            'is_correct' => true,
            'order' => 1,
        ]);

        $optB = QuestionOption::create([
            'question_id' => $mcqQ->id,
            'option_text' => 'Network',
            'is_correct' => false,
            'order' => 2,
        ]);

        $textQ = Question::create([
            'test_id' => $test->id,
            'question_type' => 'text',
            'question_text' => 'Explain 3-way handshake.',
            'marks' => 10,
            'order' => 2,
        ]);

        $classroom->tests()->attach($test->id, ['is_active' => true]);

        // Student starts exam
        $this->actingAs($this->student1);
        $this->get("/student/classrooms/{$classroom->id}/tests/{$test->id}/take")->assertStatus(200);

        // Save MCQ answer
        $saveMcq = $this->postJson("/student/classrooms/{$classroom->id}/tests/{$test->id}/save-answer", [
            'question_id' => $mcqQ->id,
            'selected_option_id' => $optA->id,
        ]);
        $saveMcq->assertStatus(200);
        $saveMcq->assertJson(['success' => true]);

        // Save Text answer
        $saveText = $this->postJson("/student/classrooms/{$classroom->id}/tests/{$test->id}/save-answer", [
            'question_id' => $textQ->id,
            'text_answer' => 'SYN, SYN-ACK, ACK establishing connection.',
        ]);
        $saveText->assertStatus(200);
        $saveText->assertJson(['success' => true]);

        // Verify stored in DB
        $attempt = ExamAttempt::where('classroom_id', $classroom->id)->where('test_id', $test->id)->first();
        $this->assertDatabaseHas('student_answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $mcqQ->id,
            'selected_option_id' => $optA->id,
            'is_correct' => true,
            'marks_awarded' => 5,
        ]);

        $this->assertDatabaseHas('student_answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $textQ->id,
            'text_answer' => 'SYN, SYN-ACK, ACK establishing connection.',
        ]);
    }

    /**
     * Teacher results breakdown categorizes students into 4 distinct groups:
     * submittedOnTime, submittedLate, inProgress, and unattemptedStudents.
     */
    public function test_teacher_results_breakdown_into_four_categories(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Data Science Class',
            'code' => 'DS10101',
        ]);

        // Enroll Student 1 and Student 2
        $classroom->students()->attach([$this->student1->id, $this->student2->id]);

        $test = Test::create([
            'user_id' => $this->teacher1->id,
            'title' => 'Pandas & NumPy Test',
            'duration_minutes' => 30,
            'total_marks' => 10,
            'is_published' => true,
        ]);

        $classroom->tests()->attach($test->id, [
            'starts_at' => null,
            'due_at' => Carbon::now()->addHour(),
            'allow_late_submissions' => true,
            'is_active' => true,
        ]);

        // Student 1 submitted on time
        ExamAttempt::create([
            'classroom_id' => $classroom->id,
            'test_id' => $test->id,
            'user_id' => $this->student1->id,
            'status' => ExamAttempt::STATUS_SUBMITTED,
            'is_late' => false,
            'score' => 10,
            'total_marks' => 10,
            'started_at' => Carbon::now()->subMinutes(20),
            'submitted_at' => Carbon::now()->subMinutes(5),
        ]);

        // Student 2 has NOT started yet

        $this->actingAs($this->teacher1);

        $response = $this->get("/teacher/classrooms/{$classroom->id}/tests/{$test->id}/results");
        $response->assertStatus(200);

        // Student 1 appears under on-time
        $response->assertSee($this->student1->name);
        $response->assertSee('Submitted on time');

        // Student 2 appears in not started
        $response->assertSee($this->student2->name);
    }

    /**
     * 24. Verify old right-side assignment panel is completely removed and layout is full-width.
     */
    public function test_assignment_panel_is_completely_removed_from_teacher_classroom_view(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Full Width Classroom',
            'code' => 'FWC123',
        ]);

        $this->actingAs($this->teacher1);

        $response = $this->get(route('teacher.classrooms.show', $classroom));
        $response->assertOk();

        // Ensure old assignment panel UI elements are completely gone
        $response->assertDontSee('Assign Test to Classroom');
        $response->assertDontSee('Select Examination');
        $response->assertDontSee('Confirm Assignment');
        $response->assertDontSee('name="test_id"', false);
        $response->assertDontSee('Assign without re-creating questions.');
        $response->assertDontSee('lg:col-span-2');
        $response->assertDontSee('lg:grid-cols-3');

        // Ensure + Create Examination button is present and prominent
        $response->assertSee('+ Create Examination');
        $response->assertSee(route('teacher.classrooms.tests.create', $classroom));

        // Ensure core classroom features remain present
        $response->assertSee('Classroom Study Materials');
        $response->assertSee('Assigned Examinations');
        $response->assertSee('Enrolled Students');
    }

    /**
     * 25. Verify old assignment route is removed and classroom-first test creation works seamlessly.
     */
    public function test_old_assignment_route_is_removed_and_classroom_first_test_creation_is_functional(): void
    {
        $classroom = Classroom::create([
            'user_id' => $this->teacher1->id,
            'name' => 'Physics 101',
            'code' => 'PHYS101',
        ]);

        $this->actingAs($this->teacher1);

        // 1. Verify old assignment route does not exist
        $oldRouteResponse = $this->post("/teacher/classrooms/{$classroom->id}/tests", [
            'test_id' => 1,
        ]);
        $this->assertTrue(in_array($oldRouteResponse->getStatusCode(), [404, 405]));

        // 2. Verify classroom-first test create page renders
        $createPageResponse = $this->get(route('teacher.classrooms.tests.create', $classroom));
        $createPageResponse->assertOk();
        $createPageResponse->assertSee($classroom->name);

        // 3. Verify classroom-first test store endpoint works and automatically assigns test
        $storeResponse = $this->post(route('teacher.classrooms.tests.store-direct', $classroom), [
            'title' => 'Kinematics & Dynamics Exam',
            'description' => 'Midterm exam for Physics 101.',
            'duration_minutes' => 60,
            'starts_at' => now()->toDateTimeString(),
            'due_at' => now()->addDays(7)->toDateTimeString(),
            'allow_late_submissions' => 1,
            'questions' => [
                [
                    'text' => 'What is the SI unit of force?',
                    'type' => 'mcq',
                    'marks' => 5,
                    'options' => [
                        ['text' => 'Joule'],
                        ['text' => 'Newton'],
                        ['text' => 'Watt'],
                    ],
                    'correct_option' => 1,
                ],
                [
                    'text' => 'State Newton second law of motion in your own words.',
                    'type' => 'text',
                    'marks' => 10,
                ],
            ],
        ]);

        $storeResponse->assertRedirect(route('teacher.classrooms.show', $classroom));

        // Verify test was created in database and automatically attached to the classroom
        $this->assertDatabaseHas('tests', [
            'title' => 'Kinematics & Dynamics Exam',
            'user_id' => $this->teacher1->id,
            'total_marks' => 15,
        ]);

        $test = Test::where('title', 'Kinematics & Dynamics Exam')->first();
        $this->assertNotNull($test);
        $this->assertTrue($classroom->fresh()->hasTest($test->id));

        // Verify test is rendered in the classroom's Assigned Examinations list
        $showResponse = $this->get(route('teacher.classrooms.show', $classroom));
        $showResponse->assertOk();
        $showResponse->assertSee('Kinematics & Dynamics Exam');
        $showResponse->assertSee('total marks');
    }
}

