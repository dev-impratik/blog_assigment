<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::with(['comments' => function ($query) {
            $query->select('id', 'post_id', 'content', 'created_at', 'updated_at');
        }])
        ->select('id', 'content', 'created_at', 'updated_at');

        $query->orderBy('created_at', 'desc');

        $page = $request->query('page', 1);
        $limit = $request->query('limit', 10);
        $posts = $query->paginate($limit, ['*'], 'page', $page);

        return response()->json($posts);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string'
        ]);

        $post = new Post();
        $post->title = $request->title;
        $post->content = $request->content;
        $post->author_id = auth()->id();
        $post->save();

        return response()->json(["post"=> $post, "success"=> true, "error"=> false, "message"=> "Post created successfully!!"], 201);
    }

    public function show($id)
    {
        $post = Post::with('comments')->find($id);
        
        if(!$this->check_post_permissions($post)){
            return response()->json(["success"=> false, "error"=> true, "message" => "Unauthorized user or post not available"], 401);
        }
        return response()->json(["post"=> $post, "success"=> true, "error"=> false, "message"=> "Post fetched successfully!!"], 200);
        
    }

    public function update(Request $request, $id)
    {
        $post = Post::find($id);

        if(!$this->check_post_permissions($post)){
            return response()->json(["success"=> false, "error"=> true, "message" => "Unauthorized user or post not available"], 401);
        }

        $post->title = $request->title;
        $post->content = $request->content;
        $post->save();

        return response()->json(["post"=> $post, "success"=> true, "error"=> false, "message" => "Post updated successfully!!"], 200);
    }

    public function destroy($id)
    {
        $post = Post::find($id);
        
        if(!$this->check_post_permissions($post)){
            return response()->json(["success"=> false, "error"=> true, "message" => "Unauthorized user or post not available"], 401);
        }

        $post->delete();
        
        return response()->json(["post"=> $post, "success"=> true, "error"=> false, "message" => "Post deleted successfully!!"], 200);
    }

    public function check_post_permissions($post){
        $return = (!$post || (auth()->id() !== $post->author_id && !auth()->user()->hasRole('admin'))) ? false : true;
        return $return;
    }
}
