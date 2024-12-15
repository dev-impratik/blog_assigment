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
                "message" => "Unauthorized user."
            ];
        }
        return $comment;
    }

    // Add a comment to a post
    /**
     * @OA\Post(
     *     path="/api/auth/posts/{postId}/comments",
     *     tags={"Comments"},
     *     summary="Add a comment to a post",
     *     description="Add a comment to a specific post.",
     *     operationId="storeComment",
     *     @OA\Parameter(
     *         name="postId",
     *         in="path",
     *         required=true,
     *         description="ID of the post",
     *         example=1
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content"},
     *             @OA\Property(property="content", type="string", example="This is a comment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Comment added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="error", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Comment added successfully!"),
     *             @OA\Property(property="comment", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="content", type="string", example="This is a comment"),
     *                 @OA\Property(property="user_id", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
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
    /**
     * @OA\Get(
     *     path="/api/auth/posts/{postId}/comments",
     *     tags={"Comments"},
     *     summary="Retrieve all comments for a post",
     *     description="Get all comments associated with a specific post.",
     *     operationId="indexComments",
     *     @OA\Parameter(
     *         name="postId",
     *         in="path",
     *         required=true,
     *         description="ID of the post",
     *         example=1
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comments retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="error", type="boolean", example=false),
     *             @OA\Property(property="comments", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="content", type="string", example="This is a comment"),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="user", type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="John Doe")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
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
    /**
     * @OA\Patch(
     *     path="/api/auth/comments/{id}",
     *     tags={"Comments"},
     *     summary="Update a comment",
     *     description="Update the content of a comment.",
     *     operationId="updateComment",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the comment",
     *         example=1
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content"},
     *             @OA\Property(property="content", type="string", example="Updated comment content")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comment updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="error", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Comment updated successfully!"),
     *             @OA\Property(property="comment", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="content", type="string", example="Updated comment content"),
     *                 @OA\Property(property="user_id", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
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
    /**
     * @OA\Delete(
     *     path="/api/auth/comments/{id}",
     *     tags={"Comments"},
     *     summary="Delete a comment",
     *     description="Delete a comment by its ID.",
     *     operationId="destroyComment",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the comment",
     *         example=1
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comment deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="error", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Comment deleted successfully!")
     *         )
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
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
