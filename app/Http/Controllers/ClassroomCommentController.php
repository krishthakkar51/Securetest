<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\ClassroomComment;
use App\Models\ClassroomPost;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassroomCommentController extends Controller
{
    /**
     * Ensure the authenticated user is either the instructor or an enrolled student.
     */
    protected function authorizeParticipant(Classroom $classroom, User $user): void
    {
        $isTeacher = ($classroom->user_id === $user->id);
        $isStudent = $classroom->hasStudent($user->id);

        if (!$isTeacher && !$isStudent) {
            abort(403, 'Unauthorized. You must be the instructor or an enrolled student in this classroom to post comments.');
        }
    }

    /**
     * Store a comment or reply under a classroom post.
     */
    public function store(Request $request, Classroom $classroom, ClassroomPost $post): RedirectResponse
    {
        // 1. Verify post belongs to this classroom
        if ($post->classroom_id !== $classroom->id) {
            abort(404, 'Classroom post not found in this classroom.');
        }

        // 2. Authorize participant
        $user = Auth::user();
        $this->authorizeParticipant($classroom, $user);

        // 3. Validation
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'integer', 'exists:classroom_comments,id'],
        ], [
            'content.required' => 'Please enter comment text before submitting.',
            'content.max' => 'Comments cannot exceed 2,000 characters.',
        ]);

        $parentId = null;
        if (!empty($validated['parent_id'])) {
            $parentComment = ClassroomComment::find($validated['parent_id']);

            // Critical security check: Ensure parent comment belongs to the exact same classroom post
            if (!$parentComment || $parentComment->classroom_post_id !== $post->id) {
                abort(422, 'Invalid reply target. The parent comment does not belong to this post.');
            }

            // Flatten multi-level nesting to 1-level thread if parent is already a reply
            $parentId = $parentComment->parent_id ?: $parentComment->id;
        }

        $comment = $post->allComments()->create([
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'content' => trim($validated['content']),
        ]);

        $successMessage = $parentId ? 'Reply posted successfully.' : 'Class comment posted successfully.';

        return redirect()->to(url()->previous() . '#comment-' . $comment->id)
            ->with('success', $successMessage);
    }

    /**
     * Update an existing comment. Only the author may edit their own comment.
     */
    public function update(Request $request, Classroom $classroom, ClassroomComment $comment): RedirectResponse
    {
        $post = $comment->post;
        if (!$post || $post->classroom_id !== $classroom->id) {
            abort(404, 'Comment not found in this classroom.');
        }

        $user = Auth::user();
        if ($comment->user_id !== $user->id) {
            abort(403, 'You can only edit your own comments.');
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $comment->update([
            'content' => trim($validated['content']),
        ]);

        return redirect()->to(url()->previous() . '#comment-' . $comment->id)
            ->with('success', 'Comment updated successfully.');
    }

    /**
     * Delete a comment. The comment author OR the classroom instructor (moderator) can delete.
     */
    public function destroy(Classroom $classroom, ClassroomComment $comment): RedirectResponse
    {
        $post = $comment->post;
        if (!$post || $post->classroom_id !== $classroom->id) {
            abort(404, 'Comment not found in this classroom.');
        }

        $user = Auth::user();
        if (!$comment->canDelete($user, $classroom)) {
            abort(403, 'Unauthorized to delete this comment.');
        }

        $isModeratorAction = ($user->id !== $comment->user_id && $classroom->user_id === $user->id);

        $comment->delete();

        $message = $isModeratorAction
            ? 'Comment removed by instructor moderation.'
            : 'Comment deleted successfully.';

        return back()->with('success', $message);
    }
}
