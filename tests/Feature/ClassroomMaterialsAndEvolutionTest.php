<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckActiveSession;
use App\Models\Classroom;
use App\Models\ClassroomMaterial;
use App\Models\Question;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClassroomMaterialsAndEvolutionTest extends TestCase
{
    use RefreshDatabase;

    protected User $userAlpha;
    protected User $userBeta;
    protected Classroom $classAlpha;
    protected Classroom $classBeta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(CheckActiveSession::class);

        // User Alpha
        $this->userAlpha = User::create([
            'name' => 'Alice Teacher-Student',
            'email' => 'alice@securetest.local',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_TEACHER,
            'email_verified_at' => now(),
        ]);

        // User Beta
        $this->userBeta = User::create([
            'name' => 'Bob Student-Teacher',
            'email' => 'bob@securetest.local',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);

        // Classroom owned by Alpha
        $this->classAlpha = Classroom::create([
            'user_id' => $this->userAlpha->id,
            'name' => 'Algorithms & Data Structures',
            'code' => 'ALGO101',
        ]);

        // Classroom owned by Beta (under Option A)
        $this->classBeta = Classroom::create([
            'user_id' => $this->userBeta->id,
            'name' => 'Web Security Lab',
            'code' => 'SECLAB1',
        ]);

        // User Beta enrolls as a student in Class Alpha
        $this->classAlpha->students()->attach($this->userBeta->id);

        // User Alpha enrolls as a student in Class Beta
        $this->classBeta->students()->attach($this->userAlpha->id);
    }

    /**
     * Test Option A: Same user can be instructor in their own classroom and student in another.
     */
    public function test_same_user_has_dual_roles_in_different_classrooms(): void
    {
        // Alice manages Class Alpha as owner
        $this->actingAs($this->userAlpha);
        $this->get("/teacher/classrooms/{$this->classAlpha->id}")->assertStatus(200);

        // Alice cannot manage Class Beta (owned by Bob)
        $this->get("/teacher/classrooms/{$this->classBeta->id}")->assertStatus(403);

        // Alice participates in Class Beta as enrolled student
        $this->get("/student/classrooms/{$this->classBeta->id}")->assertStatus(200);

        // Bob manages Class Beta as owner
        $this->actingAs($this->userBeta);
        $this->get("/teacher/classrooms/{$this->classBeta->id}")->assertStatus(200);

        // Bob participates in Class Alpha as enrolled student
        $this->get("/student/classrooms/{$this->classAlpha->id}")->assertStatus(200);
    }

    /**
     * Test classroom banner image upload and fallback theme.
     */
    public function test_classroom_banner_upload_and_theme_fallback(): void
    {
        Storage::fake('public');

        $this->actingAs($this->userAlpha);

        // Fallback theme is deterministic
        $theme = $this->classAlpha->bannerTheme();
        $this->assertArrayHasKey('bg', $theme);
        $this->assertArrayHasKey('accent', $theme);
        $this->assertNull($this->classAlpha->bannerUrl());

        // Upload custom banner with real valid image bytes (compatible without GD extension)
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $file = UploadedFile::fake()->createWithContent('custom_banner.png', $pngData);

        $response = $this->put("/teacher/classrooms/{$this->classAlpha->id}", [
            'name' => 'Algorithms & Data Structures',
            'banner_image' => $file,
        ]);

        $response->assertRedirect("/teacher/classrooms/{$this->classAlpha->id}");
        $this->classAlpha->refresh();

        $this->assertNotNull($this->classAlpha->banner_image_path);
        Storage::disk('public')->assertExists($this->classAlpha->banner_image_path);
        $this->assertNotNull($this->classAlpha->bannerUrl());

        // Remove custom banner
        $removeResponse = $this->put("/teacher/classrooms/{$this->classAlpha->id}", [
            'name' => 'Algorithms & Data Structures',
            'remove_banner' => 1,
        ]);

        $removeResponse->assertRedirect("/teacher/classrooms/{$this->classAlpha->id}");
        $this->classAlpha->refresh();
        $this->assertNull($this->classAlpha->banner_image_path);
    }

    /**
     * Test classroom-based direct test creation.
     */
    public function test_classroom_based_test_creation(): void
    {
        $this->actingAs($this->userAlpha);

        // View classroom test create form
        $createResponse = $this->get("/teacher/classrooms/{$this->classAlpha->id}/tests/create");
        $createResponse->assertStatus(200);
        $createResponse->assertSee($this->classAlpha->name);

        // Store test directly in classroom
        $storeResponse = $this->post("/teacher/classrooms/{$this->classAlpha->id}/tests/create", [
            'title' => 'Graph Theory Quiz',
            'description' => 'DFS and BFS traversals',
            'duration_minutes' => 30,
            'allow_late_submissions' => 1,
            'questions' => [
                [
                    'text' => 'What is the time complexity of BFS with an adjacency list?',
                    'type' => Question::TYPE_MCQ,
                    'marks' => 5,
                    'options' => [
                        ['text' => 'O(V + E)'],
                        ['text' => 'O(V^2)'],
                    ],
                    'correct_option' => 0,
                ],
                [
                    'text' => 'Explain topological sort.',
                    'type' => Question::TYPE_TEXT,
                    'marks' => 10,
                ],
            ],
        ]);

        $storeResponse->assertRedirect("/teacher/classrooms/{$this->classAlpha->id}");

        $test = Test::where('title', 'Graph Theory Quiz')->first();
        $this->assertNotNull($test);
        $this->assertSame(15, $test->total_marks);
        $this->assertSame(2, $test->questions()->count());

        // Verify test is attached to classroom
        $this->assertTrue($this->classAlpha->tests()->where('tests.id', $test->id)->exists());
    }

    /**
     * Test study materials upload, download permissions, and deletion.
     */
    public function test_study_materials_lifecycle_and_secure_download(): void
    {
        Storage::fake('local');

        $this->actingAs($this->userAlpha);

        // Teacher uploads material
        $fakePdf = UploadedFile::fake()->create('lecture1_graphs.pdf', 1024, 'application/pdf');

        $uploadResponse = $this->post("/teacher/classrooms/{$this->classAlpha->id}/materials", [
            'title' => 'Lecture 1: Graphs and Trees',
            'description' => 'Introduction to directed acyclic graphs and trees',
            'file' => $fakePdf,
        ]);

        $uploadResponse->assertRedirect("/teacher/classrooms/{$this->classAlpha->id}");

        $material = ClassroomMaterial::where('title', 'Lecture 1: Graphs and Trees')->first();
        $this->assertNotNull($material);
        $this->assertSame($this->classAlpha->id, $material->classroom_id);
        $this->assertSame($this->userAlpha->id, $material->user_id);
        $this->assertSame('lecture1_graphs.pdf', $material->original_filename);

        Storage::disk('local')->assertExists($material->file_path);

        // Enrolled student (Bob) downloads the material
        $this->actingAs($this->userBeta);
        $downloadResponse = $this->get("/student/classrooms/{$this->classAlpha->id}/materials/{$material->id}/download");
        $downloadResponse->assertStatus(200);

        // Non-enrolled user attempts to download material
        $unrelatedUser = User::create([
            'name' => 'Charlie Outsider',
            'email' => 'charlie@securetest.local',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);
        $this->actingAs($unrelatedUser);
        $this->get("/student/classrooms/{$this->classAlpha->id}/materials/{$material->id}/download")->assertStatus(403);

        // Teacher downloads material
        $this->actingAs($this->userAlpha);
        $teacherDownload = $this->get("/teacher/classrooms/{$this->classAlpha->id}/materials/{$material->id}/download");
        $teacherDownload->assertStatus(200);

        // Teacher deletes material
        $deleteResponse = $this->delete("/teacher/classrooms/{$this->classAlpha->id}/materials/{$material->id}");
        $deleteResponse->assertRedirect("/teacher/classrooms/{$this->classAlpha->id}");

        $this->assertDatabaseMissing('classroom_materials', ['id' => $material->id]);
        Storage::disk('local')->assertMissing($material->file_path);
    }
}
