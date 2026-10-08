<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(): View
    {
        return view('posts.index', [
            'posts' => Post::latestPublished()->paginate(9),
        ]);
    }

    public function show(Post $post): View
    {
        abort_unless($post->isPublished(), 404);

        return view('posts.show', [
            'post' => $post,
            'otherPosts' => Post::latestPublished()->whereKeyNot($post->getKey())->limit(3)->get(),
        ]);
    }
}
