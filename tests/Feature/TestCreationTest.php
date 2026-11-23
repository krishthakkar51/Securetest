<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckActiveSession;
use App\Models\Question;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestCreationTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(CheckActiveSession::class);

        $this->teacher = User::create([
            'name' => 'Prof. Alan Turing',
            'email' => 'turing_' . time() . '@securetest.local',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_TEACHER,
            'email_verified_at' => now(),
        ]);

        $this->student = User::create([
            'name' => 'Ada Lovelace',
            'email' => 'lovelace_' . time() . '@securetest.local',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Test students cannot access teacher test creation routes.
     */
    public function test_students_cannot_access_test_creation_routes(): void
    {
        $this->actingAs($this->student);

        $this->get('/teacher/tests')->assertStatus(403);
        $this->get('/teacher/tests/create')->assertStatus(403);
        $this->post('/teacher/tests', [])->assertStatus(403);
    }

    /**
     * Test teachers can view the Create Test page.
     */
    public function test_teacher_can_view_create_test_page(): void
    {
        $response = $this->actingAs($this->teacher)->get('/teacher/tests/create');

        $response->assertStatus(200);
        $response->assertSee('Create Examination');
        $response->assertSee('Test Title');
        $response->assertSee('Add MCQ');
        $response->assertSee('Add Text Question');
    }

    /**
     * Test teacher can create and save a test containing BOTH MCQ and Text-answer questions.
     */
    public function test_teacher_can_save_test_with_mcq_and_text_questions(): void
    {
        $payload = [
            'title' => 'Operating Systems & Security Final',
            'description' => 'Comprehensive exam with multiple choice and architectural essay questions.',
            'duration_minutes' => 60,
            'questions' => [
                // Question 1: Multiple Choice Question (MCQ)
                [
                    'type' => 'mcq',
                    'text' => 'What is the primary purpose of Virtual Memory in modern operating systems?',
                    'marks' => 2,
                    'correct_option' => 1, // Option B is correct
                    'options' => [
                        ['text' => 'To increase CPU clock speed.'],
                        ['text' => 'To provide an illusion of contiguous memory larger than physical RAM.'],
                        ['text' => 'To prevent hard drive fragmentation.'],
                        ['text' => 'To automatically compile source code.'],
                    ],
                ],
                // Question 2: Text-Answer Question (Written)
                [
                    'type' => 'text',
                    'text' => 'Explain the concept of deadlock in multithreaded systems and detail Coffman’s four necessary conditions.',
                    'marks' => 8,
                ],
                // Question 3: Another MCQ
                [
                    'type' => 'mcq',
                    'text' => 'Which scheduling algorithm is non-preemptive?',
                    'marks' => 2,
                    'correct_option' => 0, // Option A is correct
                    'options' => [
                        ['text' => 'First-Come, First-Served (FCFS)'],
                        ['text' => 'Round Robin (RR)'],
                        ['text' => 'Shortest Remaining Time First (SRTF)'],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->teacher)->post('/teacher/tests', $payload);

        // Verify test was created in database
        $test = Test::where('title', 'Operating Systems & Security Final')->first();
        $this->assertNotNull($test);
        $this->assertEquals($this->teacher->id, $test->user_id);
        $this->assertEquals(60, $test->duration_minutes);
        $this->assertEquals(12, $test->total_marks); // 2 + 8 + 2 = 12

        // Verify redirect to show page
        $response->assertRedirect(route('teacher.tests.show', $test));

        // Verify questions
        $this->assertEquals(3, $test->questions()->count());
        $this->assertEquals(2, $test->mcqCount());
        $this->assertEquals(1, $test->textCount());

        // Verify Question 1 (MCQ)
        $q1 = $test->questions()->where('order', 1)->first();
        $this->assertTrue($q1->isMcq());
        $this->assertEquals(4, $q1->options()->count());
        $correctOption = $q1->correctOption();
        $this->assertNotNull($correctOption);
        $this->assertEquals('To provide an illusion of contiguous memory larger than physical RAM.', $correctOption->option_text);

        // Verify Question 2 (Text)
        $q2 = $test->questions()->where('order', 2)->first();
        $this->assertTrue($q2->isText());
        $this->assertEquals(0, $q2->options()->count());
        $this->assertEquals(8, $q2->marks);

        // Verify Question 3 (MCQ)
        $q3 = $test->questions()->where('order', 3)->first();
        $this->assertTrue($q3->isMcq());
        $this->assertEquals(3, $q3->options()->count());
        $this->assertEquals('First-Come, First-Served (FCFS)', $q3->correctOption()->option_text);
    }

    /**
     * Test teacher can view test details with question and option breakdown.
     */
    public function test_teacher_can_view_test_details(): void
    {
        $test = Test::create([
            'user_id' => $this->teacher->id,
            'title' => 'Algorithms Midterm',
            'duration_minutes' => 45,
            'total_marks' => 10,
            'is_published' => true,
        ]);

        $mcq = Question::create([
            'test_id' => $test->id,
            'question_type' => 'mcq',
            'question_text' => 'What is the worst-case time complexity of QuickSort?',
            'marks' => 3,
            'order' => 1,
        ]);

        $mcq->options()->createMany([
            ['option_text' => 'O(n log n)', 'is_correct' => false, 'order' => 1],
            ['option_text' => 'O(n^2)', 'is_correct' => true, 'order' => 2],
            ['option_text' => 'O(n)', 'is_correct' => false, 'order' => 3],
        ]);

        $textQ = Question::create([
            'test_id' => $test->id,
            'question_type' => 'text',
            'question_text' => 'Describe Dijkstra’s algorithm and its limitations.',
            'marks' => 7,
            'order' => 2,
        ]);

        $response = $this->actingAs($this->teacher)->get(route('teacher.tests.show', $test));

        $response->assertStatus(200);
        $response->assertSee('Algorithms Midterm');
        $response->assertSee('45 Mins');
        $response->assertSee('What is the worst-case time complexity of QuickSort?');
        $response->assertSee('O(n^2)');
        $response->assertSee('Correct Answer');
        $response->assertSee('Describe Dijkstra’s algorithm and its limitations.');
        $response->assertSee('Student Text-Answer Input Area:');
    }

    /**
     * Test published tests are visible on the Student Dashboard.
     */
    public function test_saved_tests_are_displayed_on_student_dashboard(): void
    {
        $test = Test::create([
            'user_id' => $this->teacher->id,
            'title' => 'Software Engineering Test',
            'description' => 'Agile and Scrum fundamentals.',
            'duration_minutes' => 30,
            'total_marks' => 15,
            'is_published' => true,
        ]);

        $test->questions()->create([
            'question_type' => 'mcq',
            'question_text' => 'Sample MCQ',
            'marks' => 5,
            'order' => 1,
        ]);

        $test->questions()->create([
            'question_type' => 'text',
            'question_text' => 'Sample Written Question',
            'marks' => 10,
            'order' => 2,
        ]);

        // Create a classroom and assign the test to it so the enrolled student sees it
        $classroom = \App\Models\Classroom::create([
            'user_id' => $this->teacher->id,
            'name' => 'Computer Science 101',
            'code' => 'CS101XX',
        ]);
        $classroom->students()->attach($this->student->id);
        $classroom->tests()->attach($test->id, [
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->student)->get('/student/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Available Examinations');
        $response->assertSee('Software Engineering Test');
        $response->assertSee('Agile and Scrum fundamentals.');
        $response->assertSee('15 Marks');
        $response->assertSee('Prof. Alan Turing');
        $response->assertSee('30 mins');
    }

    /**
     * Test validation rules for MCQ and Text questions.
     */
    public function test_test_creation_validation_rules(): void
    {
        $this->actingAs($this->teacher);

        // 1. Missing title
        $response = $this->post('/teacher/tests', [
            'title' => '',
            'duration_minutes' => 30,
            'questions' => [
                ['type' => 'text', 'text' => 'Some prompt', 'marks' => 5],
            ],
        ]);
        $response->assertSessionHasErrors(['title']);

        // 2. Missing questions
        $response = $this->post('/teacher/tests', [
            'title' => 'Valid Title',
            'duration_minutes' => 30,
            'questions' => [],
        ]);
        $response->assertSessionHasErrors(['questions']);

        // 3. MCQ with fewer than 2 options
        $response = $this->post('/teacher/tests', [
            'title' => 'Valid Title',
            'duration_minutes' => 30,
            'questions' => [
                [
                    'type' => 'mcq',
                    'text' => 'Incomplete MCQ',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'Single Option'],
                    ],
                    'correct_option' => 0,
                ],
            ],
        ]);
        $response->assertSessionHasErrors(['questions.0.options']);

        // 4. MCQ with missing correct option
        $response = $this->post('/teacher/tests', [
            'title' => 'Valid Title',
            'duration_minutes' => 30,
            'questions' => [
                [
                    'type' => 'mcq',
                    'text' => 'Missing Key MCQ',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'Option 1'],
                        ['text' => 'Option 2'],
                    ],
                    'correct_option' => null,
                ],
            ],
        ]);
        $response->assertSessionHasErrors(['questions.0.correct_option']);
    }
}
