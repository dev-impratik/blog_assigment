<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    // Check if a post exists, authorized and is valid
    private function checkPost($postId, $user)
    {
        $post = Post::find($postId);

        if (!$post) {
            return [
                "success" => false,
                "error" => true,
                "message" => "Post not found."
            ];
        }

        // Check if there are comments for the post
        if ($post->comments->isEmpty()) {
            return [
                "success" => false,
                "error" => true,
                "message" => "No comments found for this post."
            ];
        }

        return $post;
    }

    // Check if a comment exists, authorized and is valid
    private function checkComment($commentId, $user)
    {
        $comment = Comment::find($commentId);
        if (!$comment) {
            return [
                "success" => false,
                "error" => true,
                "message" => "Comment not found."
            ];
        }
        if ($comment->user_id !== $user->id ) {
            return[
                "success" => false,
                "error" => true,
                "message" => "Unauthorized user or comment not available -00"
            ];
        }
        return $comment;
    }

    // Add a comment to a post
    public function store(Request $request, $postId)
    {
        $request->validate([
            'content' => 'required|string|max:500',
        ]);

        $post = $this->checkPost($postId, Auth::user());
        if (is_array($post)) { 
            return $post;
        }

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => Auth::id(),
            'content' => $request->input('content'),
        ]);

        return response()->json([
            "success" => true, 
            "error" => false, 
            "message" => 'Comment added successfully!', 
            'comment' => [
                'id' => $comment->id,
                'content' => $comment->content,
                'user_id' => $comment->user_id,
            ]
        ], 201);
    }

    // Retrieve all comments for a post
    public function index($postId)
    {
        $post = $this->checkPost($postId, Auth::user());
        if (is_array($post)) {
            return $post;
        }

        $comments = $post->comments()->with('user:id,name')->paginate(10);

        if ($comments->isEmpty()) {
            return response()->json([
                "success" => true,
                "error" => false,
                "message" => "No comments found for this post."
            ], 404);
        }

        return response()->json(["success" => true, "error" => false, "comments" => $comments]);
    }

    // Update a comment
    public function update(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string|max:500',
        ]);

        $comment = $this->checkComment($id, Auth::user());
        if (is_array($comment)) {
            return $comment;
        }

        $comment->update(['content' => $request->input('content')]);

        return response()->json(["success" => true, "error" => false, "message" => 'Comment updated successfully!', 'comment' => $comment]);
    }

    // Delete a comment
    public function destroy($id)
    {
        $comment = $this->checkComment($id, Auth::user());
        if (is_array($comment)) {
            return $comment;
        }

        $comment->delete();

        return response()->json(["success" => true, "error" => false, "message" => 'Comment deleted successfully!']);
    }
}
