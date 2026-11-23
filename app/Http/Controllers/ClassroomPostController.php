<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\ClassroomPost;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassroomPostController extends Controller
{
    /**
     * Ensure the authenticated user is either the instructor or an enrolled student.
     */
    protected function authorizeParticipant(Classroom $classroom, User $user): void
    {
        $isTeacher = ($classroom->user_id === $user->id);
        $isStudent = $classroom->hasStudent($user->id);

        if (!$isTeacher && !$isStudent) {
            abort(403, 'Unauthorized. You must be the instructor or an enrolled student in this classroom to participate in discussions.');
        }
    }

    /**
     * Store a new post or announcement in the classroom discussion stream.
     */
    public function store(Request $request, Classroom $classroom): RedirectResponse
    {
        $user = Auth::user();
        $this->authorizeParticipant($classroom, $user);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
        ], [
            'content.required' => 'Please enter message content for your post.',
            'content.max' => 'Post content cannot exceed 5,000 characters.',
        ]);

        $post = $classroom->posts()->create([
            'user_id' => $user->id,
            'title' => !empty($validated['title']) ? trim($validated['title']) : null,
            'content' => trim($validated['content']),
        ]);

        $message = ($classroom->user_id === $user->id)
            ? 'Instructor announcement published successfully.'
            : 'Discussion topic shared with the class.';

        return redirect()->to(url()->previous() . '#post-' . $post->id)
            ->with('success', $message);
    }

    /**
     * Update an existing post. Only the post author may edit.
     */
    public function update(Request $request, Classroom $classroom, ClassroomPost $post): RedirectResponse
    {
        if ($post->classroom_id !== $classroom->id) {
            abort(404, 'Post does not belong to this classroom.');
        }

        $user = Auth::user();
        if ($post->user_id !== $user->id) {
            abort(403, 'You can only edit your own posts.');
        }

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $post->update([
            'title' => !empty($validated['title']) ? trim($validated['title']) : null,
            'content' => trim($validated['content']),
        ]);

        return redirect()->to(url()->previous() . '#post-' . $post->id)
            ->with('success', 'Post updated successfully.');
    }

    /**
     * Delete a post. Either the author or the classroom instructor (moderator) can delete.
     */
    public function destroy(Classroom $classroom, ClassroomPost $post): RedirectResponse
    {
        if ($post->classroom_id !== $classroom->id) {
            abort(404, 'Post does not belong to this classroom.');
        }

        $user = Auth::user();
        if (!$post->canDelete($user, $classroom)) {
            abort(403, 'Unauthorized to delete this post.');
        }

        $post->delete();

        return back()->with('success', 'Post deleted successfully.');
    }
}
