<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): Response
    {
        $posts = Post::where('user_id', Auth::id())
            ->latest()
            ->paginate(8)
            ->through(fn ($post) => [
                'id' => $post->id,
                'title' => $post->title,
                'body' => $post->body,
                'published' => (bool) $post->published,
                'created_at' => $post->created_at->toDateTimeString(),
            ]);

        return Inertia::render('Posts/Index', [
            'posts' => $posts,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Posts/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'max:255'],
            'body' => ['required', 'string'],
            'published' => ['nullable', 'boolean'],
        ]);

        Post::create([
            ...$data,
            'published' => $request->boolean('published'),
            'user_id' => Auth::id(),
        ]);

        return redirect()
            ->route('posts.index')
            ->with('success', 'Post created successfully.');
    }

    public function edit(Post $post): Response
    {
        $this->authorizePost($post);

        return Inertia::render('Posts/Edit', [
            'post' => $post,
        ]);
    }

    public function update(Request $request, Post $post)
    {
        $this->authorizePost($post);

        $data = $request->validate([
            'title' => ['required', 'max:255'],
            'body' => ['required', 'string'],
            'published' => ['nullable', 'boolean'],
        ]);

        $post->update([
            ...$data,
            'published' => $request->boolean('published'),
        ]);

        return redirect()
            ->route('posts.index')
            ->with('success', 'Post updated.');
    }

    public function destroy(Post $post)
    {
        $this->authorizePost($post);
        $post->delete();

        return redirect()
            ->route('posts.index')
            ->with('success', 'Post deleted.');
    }

    protected function authorizePost(Post $post): void
    {
        abort_if($post->user_id !== Auth::id(), 403);
    }
}
