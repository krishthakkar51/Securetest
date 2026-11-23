<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckActiveSession;
use App\Models\Classroom;
use App\Models\ClassroomComment;
use App\Models\ClassroomPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClassroomDiscussionAndBannerTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected User $otherTeacher;
    protected User $student;
    protected User $otherStudent;
    protected Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(CheckActiveSession::class);

        // Teacher owner
        $this->teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER,
        ]);

        // Second teacher (not owner)
        $this->otherTeacher = User::factory()->create([
            'role' => User::ROLE_TEACHER,
        ]);

        // Enrolled student
        $this->student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
        ]);

        // Non-enrolled student
        $this->otherStudent = User::factory()->create([
            'role' => User::ROLE_STUDENT,
        ]);

        // Classroom owned by teacher
        $this->classroom = Classroom::create([
            'user_id' => $this->teacher->id,
            'name' => 'Advanced Algorithms CS301',
            'description' => 'Discussion and lecture notes for CS301.',
            'code' => Classroom::generateUniqueCode(),
            'banner_image_path' => Classroom::getRandomDefaultBanner(),
        ]);

        // Enroll student in classroom
        $this->classroom->students()->attach($this->student->id);
    }

    /*
    |--------------------------------------------------------------------------
    | Banner Assignment & Persistence Tests
    |--------------------------------------------------------------------------
    */

    public function test_classroom_creation_without_banner_assigns_random_default_academic_banner(): void
    {
        $this->actingAs($this->teacher);

        $response = $this->post(route('teacher.classrooms.store'), [
            'name' => 'Physics 101 - Mechanics',
            'description' => 'Classical mechanics and dynamics.',
        ]);

        $createdClassroom = Classroom::where('name', 'Physics 101 - Mechanics')->first();
        $this->assertNotNull($createdClassroom);
        $this->assertNotNull($createdClassroom->banner_image_path);
        $this->assertContains($createdClassroom->banner_image_path, Classroom::DEFAULT_BANNERS);
        $this->assertNotNull($createdClassroom->bannerUrl());

        $response->assertRedirect(route('teacher.classrooms.show', $createdClassroom));
    }

    public function test_assigned_banner_persists_consistently_across_reloads(): void
    {
        $this->actingAs($this->teacher);

        $initialBanner = $this->classroom->banner_image_path;
        $this->assertNotNull($initialBanner);

        // Reload from database
        $reloadedClassroom = Classroom::find($this->classroom->id);
        $this->assertSame($initialBanner, $reloadedClassroom->banner_image_path);

        // Viewing the classroom show page doesn't alter the banner
        $response = $this->get(route('teacher.classrooms.show', $this->classroom));
        $response->assertStatus(200);

        $this->classroom->refresh();
        $this->assertSame($initialBanner, $this->classroom->banner_image_path);
    }

    public function test_classroom_creation_with_specific_default_banner(): void
    {
        $this->actingAs($this->teacher);

        $response = $this->post(route('teacher.classrooms.store'), [
            'name' => 'Organic Chemistry Lab',
            'default_banner' => 'images/banners/science.svg',
        ]);

        $classroom = Classroom::where('name', 'Organic Chemistry Lab')->first();
        $this->assertNotNull($classroom);
        $this->assertSame('images/banners/science.svg', $classroom->banner_image_path);
    }

    public function test_classroom_creation_with_custom_uploaded_banner(): void
    {
        Storage::fake('public');
        $this->actingAs($this->teacher);

        $file = UploadedFile::fake()->create('custom_header.jpg', 100, 'image/jpeg');

        $response = $this->post(route('teacher.classrooms.store'), [
            'name' => 'Art & Design Studio',
            'banner_image' => $file,
        ]);

        $classroom = Classroom::where('name', 'Art & Design Studio')->first();
        $this->assertNotNull($classroom);
        $this->assertStringStartsWith('classroom_banners/', $classroom->banner_image_path);
        Storage::disk('public')->assertExists($classroom->banner_image_path);
    }

    public function test_editing_classroom_retains_current_banner_if_none_selected(): void
    {
        $this->actingAs($this->teacher);
        $originalBanner = $this->classroom->banner_image_path;

        $response = $this->put(route('teacher.classrooms.update', $this->classroom), [
            'name' => 'Advanced Algorithms CS301 (Updated Name)',
            'description' => 'Updated syllabus description.',
        ]);

        $this->classroom->refresh();
        $this->assertSame('Advanced Algorithms CS301 (Updated Name)', $this->classroom->name);
        $this->assertSame($originalBanner, $this->classroom->banner_image_path);
    }

    public function test_editing_classroom_can_switch_to_different_academic_banner(): void
    {
        $this->actingAs($this->teacher);

        $response = $this->put(route('teacher.classrooms.update', $this->classroom), [
            'name' => $this->classroom->name,
            'default_banner' => 'images/banners/technology.svg',
        ]);

        $this->classroom->refresh();
        $this->assertSame('images/banners/technology.svg', $this->classroom->banner_image_path);
    }

    /*
    |--------------------------------------------------------------------------
    | Classroom Discussion Posts Tests
    |--------------------------------------------------------------------------
    */

    public function test_teacher_can_publish_announcement_post_in_owned_classroom(): void
    {
        $this->actingAs($this->teacher);

        $response = $this->post(route('classrooms.posts.store', $this->classroom), [
            'title' => 'Welcome to CS301',
            'content' => 'Please review the textbook chapter 1 before Tuesday.',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('classroom_posts', [
            'classroom_id' => $this->classroom->id,
            'user_id' => $this->teacher->id,
            'title' => 'Welcome to CS301',
            'content' => 'Please review the textbook chapter 1 before Tuesday.',
        ]);

        $post = ClassroomPost::where('classroom_id', $this->classroom->id)->first();
        $this->assertTrue($post->isInstructorAnnouncement());
    }

    public function test_enrolled_student_can_publish_discussion_post_in_classroom(): void
    {
        $this->actingAs($this->student);

        $response = $this->post(route('classrooms.posts.store', $this->classroom), [
            'title' => 'Question about Assignment 1',
            'content' => 'Is problem 3 due at midnight or end of class?',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('classroom_posts', [
            'classroom_id' => $this->classroom->id,
            'user_id' => $this->student->id,
            'title' => 'Question about Assignment 1',
        ]);

        $post = ClassroomPost::where('title', 'Question about Assignment 1')->first();
        $this->assertFalse($post->isInstructorAnnouncement());
    }

    public function test_unauthorized_user_cannot_post_in_classroom(): void
    {
        // Unenrolled student
        $this->actingAs($this->otherStudent);
        $response = $this->post(route('classrooms.posts.store', $this->classroom), [
            'content' => 'Intruder comment.',
        ]);
        $response->assertStatus(403);

        // Teacher who does not own the classroom
        $this->actingAs($this->otherTeacher);
        $response2 = $this->post(route('classrooms.posts.store', $this->classroom), [
            'content' => 'Other teacher comment.',
        ]);
        $response2->assertStatus(403);
    }

    public function test_author_can_edit_own_post(): void
    {
        $post = ClassroomPost::create([
            'classroom_id' => $this->classroom->id,
            'user_id' => $this->teacher->id,
            'title' => 'Draft Announcement',
            'content' => 'Initial draft content.',
        ]);

        $this->actingAs($this->teacher);
        $response = $this->put(route('classrooms.posts.update', [$this->classroom, $post]), [
            'title' => 'Final Announcement',
            'content' => 'Updated finalized content.',
        ]);

        $response->assertSessionHas('success');
        $post->refresh();
        $this->assertSame('Final Announcement', $post->title);
        $this->assertSame('Updated finalized content.', $post->content);
    }

    public function test_user_cannot_edit_another_users_post(): void
    {
        $post = ClassroomPost::create([
            'classroom_id' => $this->classroom->id,
            'user_id' => $this->teacher->id,
            'content' => 'Teacher original content.',
        ]);

        $this->actingAs($this->student);
        $response = $this->put(route('classrooms.posts.update', [$this->classroom, $post]), [
            'content' => 'Malicious edit by student.',
        ]);

        $response->assertStatus(403);
        $this->assertSame('Teacher original content.', $post->fresh()->content);
    }

    /*
    |--------------------------------------------------------------------------
    | Classroom Comments & Threaded Replies Tests
    |--------------------------------------------------------------------------
    */

    public function test_teacher_and_student_can_comment_on_posts(): void
    {
        $post = ClassroomPost::create([
            'classroom_id' => $this->classroom->id,
            'user_id' => $this->teacher->id,
            'content' => 'Office hours are moved to 3 PM.',
        ]);

        // Student comments
        $this->actingAs($this->student);
        $response = $this->post(route('classrooms.comments.store', [$this->classroom, $post]), [
            'content' => 'Thank you for the update, Professor!',
        ]);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('classroom_comments', [
            'classroom_post_id' => $post->id,
            'user_id' => $this->student->id,
            'content' => 'Thank you for the update, Professor!',
            'parent_id' => null,
        ]);

        // Teacher comments back
        $this->actingAs($this->teacher);
        $response2 = $this->post(route('classrooms.comments.store', [$this->classroom, $post]), [
            'content' => 'You are welcome. See you in room 402.',
        ]);
        $response2->assertSessionHas('success');

        $this->assertDatabaseHas('classroom_comments', [
            'classroom_post_id' => $post->id,
            'user_id' => $this->teacher->id,
            'content' => 'You are welcome. See you in room 402.',
        ]);
    }

    public function test_unauthorized_user_cannot_comment_on_posts(): void
    {
        $post = ClassroomPost::create([
            'classroom_id' => $this->classroom->id,
            'user_id' => $this->teacher->id,
            'content' => 'Class announcement.',
        ]);

        $this->actingAs($this->otherStudent);
        $response = $this->post(route('classrooms.comments.store', [$this->classroom, $post]), [
            'content' => 'Unenrolled student comment.',
        ]);
        $response->assertStatus(403);
    }

    public function test_threaded_reply_to_existing_comment(): void
    {
        $post = ClassroomPost::create([
            'classroom_id' => $this->classroom->id,
            'user_id' => $this->teacher->id,
            'content' => 'Exam scope announcement.',
        ]);

        $parentComment = ClassroomComment::create([
            'classroom_post_id' => $post->id,
            'user_id' => $this->student->id,
            'content' => 'Does this include dynamic programming?',
        ]);

        // Teacher replies to student's comment
        $this->actingAs($this->teacher);
        $response = $this->post(route('classrooms.comments.store', [$this->classroom, $post]), [
            'parent_id' => $parentComment->id,
            'content' => 'Yes, dynamic programming up to chapter 4 is included.',
        ]);

        $response->assertSessionHas('success');

        $reply = ClassroomComment::where('parent_id', $parentComment->id)->first();
        $this->assertNotNull($reply);
        $this->assertSame('Yes, dynamic programming up to chapter 4 is included.', $reply->content);
        $this->assertSame($this->teacher->id, $reply->user_id);
    }

    public function test_cross_post_reply_is_rejected(): void
    {
        $post1 = ClassroomPost::create([
            'classroom_id' => $this->classroom->id,
            'user_id' => $this->teacher->id,
            'content' => 'Post 1.',
        ]);

        $post2 = ClassroomPost::create([
            'classroom_id' => $this->classroom->id,
            'user_id' => $this->teacher->id,
            'content' => 'Post 2.',
        ]);

        $commentOnPost1 = ClassroomComment::create([
            'classroom_post_id' => $post1->id,
            'user_id' => $this->student->id,
            'content' => 'Comment on post 1.',
        ]);

        // Attempt to reply on Post 2 targeting a comment from Post 1
        $this->actingAs($this->student);
        $response = $this->post(route('classrooms.comments.store', [$this->classroom, $post2]), [
            'parent_id' => $commentOnPost1->id,
            'content' => 'Cross-post reply attempt.',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_edit_own_comment(): void
    {
        $post = ClassroomPost::create([
            'classroom_id' => $this->classroom->id,
            'user_id' => $this->teacher->id,
            'content' => 'Test announcement.',
        ]);

        $comment = ClassroomComment::create([
            'classroom_post_id' => $post->id,
            'user_id' => $this->student->id,
            'content' => 'Initial comment with a typo.',
        ]);

        $this->actingAs($this->student);
        $response = $this->put(route('classrooms.comments.update', [$this->classroom, $comment]), [
            'content' => 'Fixed comment without typo.',
        ]);

        $response->assertSessionHas('success');
        $this->assertSame('Fixed comment without typo.', $comment->fresh()->content);
    }

    public function test_user_cannot_edit_another_users_comment(): void
    {
        $post = ClassroomPost::create([
            'classroom_id' => $this->classroom->id,
            'user_id' => $this->teacher->id,
            'content' => 'Test announcement.',
        ]);

        $comment = ClassroomComment::create([
            'classroom_post_id' => $post->id,
            'user_id' => $this->student->id,
            'content' => 'Student comment.',
        ]);

        // Even teacher cannot edit student's written words (can only delete/moderate)
        $this->actingAs($this->teacher);
        $response = $this->put(route('classrooms.comments.update', [$this->classroom, $comment]), [
            'content' => 'Teacher editing student comment.',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_comment(): void
    {
        $post = ClassroomPost::create([
            'classroom_id' => $this->classroom->id,
            'user_id' => $this->teacher->id,
            'content' => 'Post.',
        ]);

        $comment = ClassroomComment::create([
            'classroom_post_id' => $post->id,
            'user_id' => $this->student->id,
            'content' => 'Student comment to delete.',
        ]);

        $this->actingAs($this->student);
        $response = $this->delete(route('classrooms.comments.destroy', [$this->classroom, $comment]));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('classroom_comments', ['id' => $comment->id]);
    }

    public function test_teacher_can_delete_any_comment_as_moderator(): void
    {
        $post = ClassroomPost::create([
            'classroom_id' => $this->classroom->id,
            'user_id' => $this->student->id,
            'content' => 'Discussion topic.',
        ]);

        $inappropriateComment = ClassroomComment::create([
            'classroom_post_id' => $post->id,
            'user_id' => $this->student->id,
            'content' => 'Inappropriate comment content.',
        ]);

        // Teacher deletes student comment for moderation
        $this->actingAs($this->teacher);
        $response = $this->delete(route('classrooms.comments.destroy', [$this->classroom, $inappropriateComment]));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('classroom_comments', ['id' => $inappropriateComment->id]);
    }

    public function test_student_cannot_delete_another_users_comment(): void
    {
        $post = ClassroomPost::create([
            'classroom_id' => $this->classroom->id,
            'user_id' => $this->teacher->id,
            'content' => 'Discussion post.',
        ]);

        $teacherComment = ClassroomComment::create([
            'classroom_post_id' => $post->id,
            'user_id' => $this->teacher->id,
            'content' => 'Teacher comment.',
        ]);

        $this->actingAs($this->student);
        $response = $this->delete(route('classrooms.comments.destroy', [$this->classroom, $teacherComment]));
        $response->assertStatus(403);

        $this->assertDatabaseHas('classroom_comments', ['id' => $teacherComment->id]);
    }

    public function test_deleting_post_cascades_and_removes_comments_and_replies(): void
    {
        $post = ClassroomPost::create([
            'classroom_id' => $this->classroom->id,
            'user_id' => $this->teacher->id,
            'content' => 'Post with thread.',
        ]);

        $parentComment = ClassroomComment::create([
            'classroom_post_id' => $post->id,
            'user_id' => $this->student->id,
            'content' => 'Top comment.',
        ]);

        $reply = ClassroomComment::create([
            'classroom_post_id' => $post->id,
            'user_id' => $this->teacher->id,
            'parent_id' => $parentComment->id,
            'content' => 'Nested reply.',
        ]);

        $this->actingAs($this->teacher);
        $response = $this->delete(route('classrooms.posts.destroy', [$this->classroom, $post]));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('classroom_posts', ['id' => $post->id]);
        $this->assertDatabaseMissing('classroom_comments', ['id' => $parentComment->id]);
        $this->assertDatabaseMissing('classroom_comments', ['id' => $reply->id]);
    }

    public function test_discussion_stream_renders_on_teacher_and_student_show_pages(): void
    {
        $post = ClassroomPost::create([
            'classroom_id' => $this->classroom->id,
            'user_id' => $this->teacher->id,
            'title' => 'Midterm Review Session',
            'content' => 'Join the review session on Zoom this Friday.',
        ]);

        ClassroomComment::create([
            'classroom_post_id' => $post->id,
            'user_id' => $this->student->id,
            'content' => 'Will it be recorded?',
        ]);

        // Teacher view
        $this->actingAs($this->teacher);
        $teacherResp = $this->get(route('teacher.classrooms.show', $this->classroom));
        $teacherResp->assertStatus(200);
        $teacherResp->assertSee('Classroom Stream &amp; Discussions', false);
        $teacherResp->assertSee('Midterm Review Session');
        $teacherResp->assertSee('Instructor Announcement');
        $teacherResp->assertSee('Will it be recorded?');

        // Student view
        $this->actingAs($this->student);
        $studentResp = $this->get(route('student.classrooms.show', $this->classroom));
        $studentResp->assertStatus(200);
        $studentResp->assertSee('Classroom Stream &amp; Discussions', false);
        $studentResp->assertSee('Midterm Review Session');
        $studentResp->assertSee('Instructor Announcement');
        $studentResp->assertSee('Will it be recorded?');
    }
}
