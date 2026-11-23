@php
    $currentUser = Auth::user();
    $isInstructor = ($classroom->user_id === $currentUser->id);
@endphp

<div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-100 dark:border-gray-700 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-gray-100 dark:border-gray-700 pb-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                </span>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                    Classroom Stream &amp; Discussions
                </h3>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Announcements, class discussions, and teacher-moderated comments.
            </p>
        </div>

        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
            {{ $classroom->posts->count() }} {{ Str::plural('Post', $classroom->posts->count()) }}
        </span>
    </div>

    <!-- Create Post / Announcement Box -->
    <div x-data="{ expanded: false }" class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700 transition">
        <form method="POST" action="{{ route('classrooms.posts.store', $classroom) }}" class="space-y-3">
            @csrf

            <div class="flex items-start gap-3">
                <!-- User Avatar / Initials -->
                <div class="w-9 h-9 rounded-full shrink-0 flex items-center justify-center font-bold text-xs shadow-sm {{ $isInstructor ? 'bg-emerald-700 text-white' : 'bg-stone-200 text-stone-800 dark:bg-stone-700 dark:text-stone-200' }}">
                    {{ $currentUser->initials() }}
                </div>

                <div class="flex-1 min-w-0">
                    <!-- Title Input (Shown when expanded) -->
                    <div x-show="expanded" x-cloak class="mb-2">
                        <input
                            type="text"
                            name="title"
                            placeholder="Optional topic or title..."
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 focus:ring-emerald-600 focus:border-emerald-600"
                        />
                    </div>

                    <!-- Post Content Textarea -->
                    <textarea
                        name="content"
                        rows="2"
                        @focus="expanded = true"
                        :rows="expanded ? 3 : 2"
                        required
                        maxlength="5000"
                        placeholder="{{ $isInstructor ? 'Announce something to your class...' : 'Ask a question or share a thought with your classmates...' }}"
                        class="w-full text-sm rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 focus:ring-emerald-600 focus:border-emerald-600 transition placeholder-gray-400"
                    ></textarea>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-1">
                <span class="text-[11px] text-gray-400">
                    Posting as <strong class="text-gray-700 dark:text-gray-300">{{ $currentUser->name }}</strong> ({{ $isInstructor ? 'Instructor' : 'Student' }})
                </span>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        x-show="expanded"
                        x-cloak
                        @click="expanded = false"
                        class="px-3 py-1.5 text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="inline-flex items-center px-4 py-2 bg-[#164e3b] hover:bg-[#123e2f] active:bg-[#0e3024] text-white text-xs font-semibold rounded-lg shadow-sm transition"
                    >
                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        {{ $isInstructor ? 'Post Announcement' : 'Post to Class' }}
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Posts Stream Feed -->
    @if ($classroom->posts->count() > 0)
        <div class="space-y-5">
            @foreach ($classroom->posts as $post)
                @php
                    $isAnnouncement = ($post->user_id === $classroom->user_id);
                    $canEditPost = $post->canEdit($currentUser);
                    $canDeletePost = $post->canDelete($currentUser, $classroom);
                    $commentCount = $post->allComments->count();
                @endphp

                <div
                    id="post-{{ $post->id }}"
                    x-data="{ showReplyInput: false, editingPost: false, showComments: true }"
                    class="rounded-xl border transition shadow-sm overflow-hidden {{ $isAnnouncement ? 'border-emerald-200 dark:border-emerald-800/80 bg-emerald-50/20 dark:bg-emerald-950/10' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800' }}"
                >
                    <!-- Post Header -->
                    <div class="p-4 sm:p-5 flex items-start justify-between gap-3 border-b border-gray-100 dark:border-gray-700/60">
                        <div class="flex items-start gap-3 min-w-0">
                            <!-- Author Avatar -->
                            <div class="w-10 h-10 rounded-full shrink-0 flex items-center justify-center font-bold text-xs shadow-inner {{ $isAnnouncement ? 'bg-emerald-800 text-white' : 'bg-stone-100 dark:bg-stone-700 text-stone-800 dark:text-stone-200 border border-stone-200 dark:border-stone-600' }}">
                                {{ $post->user ? $post->user->initials() : 'U' }}
                            </div>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="font-bold text-sm text-gray-900 dark:text-gray-100 truncate">
                                        {{ $post->user ? $post->user->name : 'Unknown User' }}
                                    </h4>

                                    <!-- Distinction Badge: Instructor Announcement vs Class Discussion -->
                                    @if ($isAnnouncement)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                                            Instructor Announcement
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                            Class Discussion
                                        </span>
                                    @endif
                                </div>

                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ $post->created_at->diffForHumans() }}
                                    @if ($post->updated_at && $post->updated_at->gt($post->created_at))
                                        <span class="italic text-gray-400">(edited)</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <!-- Post Actions Menu -->
                        @if ($canEditPost || $canDeletePost)
                            <div class="flex items-center gap-1 shrink-0">
                                @if ($canEditPost)
                                    <button
                                        type="button"
                                        @click="editingPost = !editingPost"
                                        class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                        title="Edit Post"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                @endif

                                @if ($canDeletePost)
                                    <form method="POST" action="{{ route('classrooms.posts.destroy', [$classroom, $post]) }}" onsubmit="return confirm('Are you sure you want to delete this post? All associated comments and replies will also be removed.');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="p-1.5 text-rose-500 hover:text-rose-700 dark:hover:text-rose-400 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/30 transition"
                                            title="{{ $isInstructor && $post->user_id !== $currentUser->id ? 'Remove post as Instructor' : 'Delete Post' }}"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Post Body / Content -->
                    <div class="p-4 sm:p-5">
                        <!-- Inline Edit Form -->
                        <div x-show="editingPost" x-cloak class="mb-4 p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                            <form method="POST" action="{{ route('classrooms.posts.update', [$classroom, $post]) }}" class="space-y-3">
                                @csrf
                                @method('PUT')
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Title</label>
                                    <input type="text" name="title" value="{{ old('title', $post->title) }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Content</label>
                                    <textarea name="content" rows="3" required class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">{{ old('content', $post->content) }}</textarea>
                                </div>
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" @click="editingPost = false" class="px-3 py-1.5 text-xs text-gray-500 hover:text-gray-700">Cancel</button>
                                    <button type="submit" class="px-3 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold rounded-lg shadow-sm">Save Changes</button>
                                </div>
                            </form>
                        </div>

                        <!-- Read Mode Content -->
                        <div x-show="!editingPost">
                            @if ($post->title)
                                <h5 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-2">
                                    {{ $post->title }}
                                </h5>
                            @endif
                            <div class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line leading-relaxed">
                                {!! nl2br(e($post->content)) !!}
                            </div>
                        </div>
                    </div>

                    <!-- Post Footer & Comments Summary -->
                    <div class="px-4 sm:px-5 py-2.5 bg-gray-50/60 dark:bg-gray-900/40 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <button
                            type="button"
                            @click="showComments = !showComments"
                            class="inline-flex items-center gap-1.5 font-semibold text-emerald-700 dark:text-emerald-400 hover:underline"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                            </svg>
                            <span>{{ $commentCount }} {{ Str::plural('class comment', $commentCount) }}</span>
                            <span x-text="showComments ? '▾' : '▸'"></span>
                        </button>

                        <button
                            type="button"
                            @click="showComments = true; $nextTick(() => $refs.commentInput.focus())"
                            class="text-xs font-medium text-gray-500 hover:text-emerald-700 dark:hover:text-emerald-400 transition"
                        >
                            + Add Comment
                        </button>
                    </div>

                    <!-- Collapsible Comments Thread -->
                    <div x-show="showComments" class="p-4 sm:p-5 bg-gray-50/30 dark:bg-gray-900/20 border-t border-gray-100 dark:border-gray-700/60 space-y-4">
                        @if ($post->comments->count() > 0)
                            <div class="space-y-3.5">
                                @foreach ($post->comments as $comment)
                                    @php
                                        $isInstructorComment = $comment->isInstructorComment($classroom);
                                        $canEditComment = $comment->canEdit($currentUser);
                                        $canDeleteComment = $comment->canDelete($currentUser, $classroom);
                                    @endphp

                                    <!-- Top-Level Comment -->
                                    <div id="comment-{{ $comment->id }}" x-data="{ replyOpen: false, editingComment: false }" class="space-y-2">
                                        <div class="flex items-start gap-3 group">
                                            <!-- Commenter Avatar -->
                                            <div class="w-8 h-8 rounded-full shrink-0 flex items-center justify-center font-bold text-[11px] shadow-sm {{ $isInstructorComment ? 'bg-emerald-800 text-white' : 'bg-stone-200 dark:bg-stone-700 text-stone-700 dark:text-stone-300' }}">
                                                {{ $comment->user ? $comment->user->initials() : 'U' }}
                                            </div>

                                            <div class="flex-1 min-w-0 bg-white dark:bg-gray-800 rounded-xl p-3 border border-gray-200 dark:border-gray-700 shadow-2xs">
                                                <!-- Comment Header -->
                                                <div class="flex items-center justify-between gap-2 mb-1">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <span class="font-bold text-xs text-gray-900 dark:text-gray-100">
                                                            {{ $comment->user ? $comment->user->name : 'Unknown User' }}
                                                        </span>

                                                        @if ($isInstructorComment)
                                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold uppercase bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">
                                                                Instructor
                                                            </span>
                                                        @else
                                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                                Student
                                                            </span>
                                                        @endif

                                                        <span class="text-[10px] text-gray-400">
                                                            {{ $comment->created_at->diffForHumans() }}
                                                        </span>
                                                    </div>

                                                    <!-- Comment Actions -->
                                                    <div class="flex items-center gap-1 shrink-0">
                                                        @if ($canEditComment)
                                                            <button
                                                                type="button"
                                                                @click="editingComment = !editingComment"
                                                                class="text-[11px] text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 px-1 py-0.5 rounded transition"
                                                                title="Edit Comment"
                                                            >
                                                                Edit
                                                            </button>
                                                        @endif

                                                        @if ($canDeleteComment)
                                                            <form method="POST" action="{{ route('classrooms.comments.destroy', [$classroom, $comment]) }}" onsubmit="return confirm('Are you sure you want to delete this comment?');" class="inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button
                                                                    type="submit"
                                                                    class="text-[11px] text-rose-500 hover:text-rose-700 px-1 py-0.5 rounded transition"
                                                                    title="{{ $isInstructor && $comment->user_id !== $currentUser->id ? 'Delete as Instructor (Moderation)' : 'Delete Comment' }}"
                                                                >
                                                                    {{ $isInstructor && $comment->user_id !== $currentUser->id ? 'Remove (Mod)' : 'Delete' }}
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>

                                                <!-- Comment Content / Edit Form -->
                                                <div x-show="!editingComment" class="text-xs text-gray-800 dark:text-gray-200 whitespace-pre-line leading-relaxed">
                                                    {!! nl2br(e($comment->content)) !!}
                                                </div>

                                                <div x-show="editingComment" x-cloak class="mt-2">
                                                    <form method="POST" action="{{ route('classrooms.comments.update', [$classroom, $comment]) }}" class="space-y-2">
                                                        @csrf
                                                        @method('PUT')
                                                        <textarea name="content" rows="2" required class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">{{ old('content', $comment->content) }}</textarea>
                                                        <div class="flex items-center justify-end gap-1.5">
                                                            <button type="button" @click="editingComment = false" class="px-2 py-1 text-[11px] text-gray-500 hover:text-gray-700">Cancel</button>
                                                            <button type="submit" class="px-2.5 py-1 bg-emerald-700 text-white text-[11px] font-semibold rounded shadow-xs">Save</button>
                                                        </div>
                                                    </form>
                                                </div>

                                                <!-- Reply Button -->
                                                <div class="mt-2 pt-1 border-t border-gray-100 dark:border-gray-700/60 flex items-center gap-3">
                                                    <button
                                                        type="button"
                                                        @click="replyOpen = !replyOpen"
                                                        class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-400 hover:underline flex items-center gap-1"
                                                    >
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                                        </svg>
                                                        Reply
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Nested Replies List -->
                                        @if ($comment->replies->count() > 0)
                                            <div class="ml-8 sm:ml-11 pl-3 border-l-2 border-emerald-200 dark:border-emerald-800 space-y-2.5">
                                                @foreach ($comment->replies as $reply)
                                                    @php
                                                        $isInstructorReply = $reply->isInstructorComment($classroom);
                                                        $canEditReply = $reply->canEdit($currentUser);
                                                        $canDeleteReply = $reply->canDelete($currentUser, $classroom);
                                                    @endphp
                                                    <div id="comment-{{ $reply->id }}" x-data="{ editingReply: false }" class="flex items-start gap-2.5">
                                                        <div class="w-6 h-6 rounded-full shrink-0 flex items-center justify-center font-bold text-[9px] shadow-xs {{ $isInstructorReply ? 'bg-emerald-800 text-white' : 'bg-stone-200 dark:bg-stone-700 text-stone-700 dark:text-stone-300' }}">
                                                            {{ $reply->user ? $reply->user->initials() : 'U' }}
                                                        </div>

                                                        <div class="flex-1 min-w-0 bg-white dark:bg-gray-800/90 rounded-lg p-2.5 border border-gray-200 dark:border-gray-700 shadow-2xs">
                                                            <div class="flex items-center justify-between gap-2 mb-1">
                                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                                    <span class="font-bold text-[11px] text-gray-900 dark:text-gray-100">
                                                                        {{ $reply->user ? $reply->user->name : 'Unknown User' }}
                                                                    </span>
                                                                    @if ($isInstructorReply)
                                                                        <span class="px-1 py-0.2 rounded text-[8px] font-bold uppercase bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">
                                                                            Instructor
                                                                        </span>
                                                                    @endif
                                                                    <span class="text-[9px] text-gray-400">
                                                                        {{ $reply->created_at->diffForHumans() }}
                                                                    </span>
                                                                </div>

                                                                <div class="flex items-center gap-1">
                                                                    @if ($canEditReply)
                                                                        <button type="button" @click="editingReply = !editingReply" class="text-[10px] text-gray-400 hover:text-gray-600 px-1 py-0.5">
                                                                            Edit
                                                                        </button>
                                                                    @endif
                                                                    @if ($canDeleteReply)
                                                                        <form method="POST" action="{{ route('classrooms.comments.destroy', [$classroom, $reply]) }}" onsubmit="return confirm('Delete this reply?');" class="inline">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit" class="text-[10px] text-rose-500 hover:text-rose-700 px-1 py-0.5">
                                                                                {{ $isInstructor && $reply->user_id !== $currentUser->id ? 'Remove (Mod)' : 'Delete' }}
                                                                            </button>
                                                                        </form>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <div x-show="!editingReply" class="text-xs text-gray-800 dark:text-gray-200 whitespace-pre-line">
                                                                {!! nl2br(e($reply->content)) !!}
                                                            </div>

                                                            <div x-show="editingReply" x-cloak class="mt-1">
                                                                <form method="POST" action="{{ route('classrooms.comments.update', [$classroom, $reply]) }}" class="space-y-1.5">
                                                                    @csrf
                                                                    @method('PUT')
                                                                    <textarea name="content" rows="2" required class="w-full text-xs rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">{{ old('content', $reply->content) }}</textarea>
                                                                    <div class="flex items-center justify-end gap-1">
                                                                        <button type="button" @click="editingReply = false" class="px-2 py-0.5 text-[10px] text-gray-500">Cancel</button>
                                                                        <button type="submit" class="px-2 py-0.5 bg-emerald-700 text-white text-[10px] rounded">Save</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        <!-- Threaded Reply Input Field -->
                                        <div x-show="replyOpen" x-cloak class="ml-8 sm:ml-11 pl-3 border-l-2 border-emerald-400">
                                            <form method="POST" action="{{ route('classrooms.comments.store', [$classroom, $post]) }}" class="space-y-2">
                                                @csrf
                                                <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                                <div class="flex items-start gap-2">
                                                    <textarea
                                                        name="content"
                                                        rows="2"
                                                        required
                                                        maxlength="2000"
                                                        placeholder="Reply to {{ $comment->user ? $comment->user->name : 'comment' }}..."
                                                        class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 focus:ring-emerald-600 focus:border-emerald-600"
                                                    ></textarea>
                                                </div>
                                                <div class="flex items-center justify-end gap-2">
                                                    <button type="button" @click="replyOpen = false" class="px-2 py-1 text-xs text-gray-500">Cancel</button>
                                                    <button type="submit" class="px-3 py-1 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold rounded-lg shadow-2xs">Reply</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-400 italic">No comments on this post yet. Be the first to start the discussion!</p>
                        @endif

                        <!-- Add a Class Comment Form (Bottom of Post) -->
                        <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                            <form method="POST" action="{{ route('classrooms.comments.store', [$classroom, $post]) }}" class="flex items-center gap-2.5">
                                @csrf
                                <div class="w-7 h-7 rounded-full shrink-0 flex items-center justify-center font-bold text-[10px] shadow-2xs {{ $isInstructor ? 'bg-emerald-800 text-white' : 'bg-stone-200 dark:bg-stone-700 text-stone-700 dark:text-stone-300' }}">
                                    {{ $currentUser->initials() }}
                                </div>
                                <div class="flex-1 relative">
                                    <input
                                        x-ref="commentInput"
                                        type="text"
                                        name="content"
                                        required
                                        maxlength="2000"
                                        placeholder="Add a class comment..."
                                        class="w-full text-xs pr-16 py-2 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 focus:ring-emerald-600 focus:border-emerald-600 placeholder-gray-400"
                                    />
                                    <button
                                        type="submit"
                                        class="absolute right-1 top-1/2 -translate-y-1/2 px-3 py-1 bg-[#164e3b] hover:bg-[#123e2f] text-white text-[11px] font-semibold rounded-lg shadow-2xs transition"
                                    >
                                        Post
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Empty State -->
        <div class="border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl p-8 text-center">
            <div class="w-12 h-12 mx-auto mb-2 text-emerald-600 dark:text-emerald-400 flex items-center justify-center bg-emerald-50 dark:bg-emerald-950/40 rounded-full">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
            </div>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">No discussions or announcements yet.</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ $isInstructor ? 'Post the first announcement or guideline to welcome your enrolled students.' : 'Post a question or comment to begin collaborating with your class.' }}
            </p>
        </div>
    @endif
</div>
