<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;
/**
 * @OA\Schema(
 *     schema="Post",
 *     type="object",
 *     description="Post schema",
 *     required={"id", "content", "created_at", "updated_at"},
 *     @OA\Property(property="id", type="integer", description="Post ID"),
 *     @OA\Property(property="content", type="string", description="Content of the post"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Post creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Post update timestamp"),
 *     @OA\Property(
 *         property="comments",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Comment")
 *     ),
 *     @OA\Property(
 *         property="images",
 *         type="array",
 *         @OA\Items(type="string", description="Image URL")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Comment",
 *     type="object",
 *     description="Comment schema",
 *     required={"id", "post_id", "content", "created_at", "updated_at"},
 *     @OA\Property(property="id", type="integer", description="Comment ID"),
 *     @OA\Property(property="post_id", type="integer", description="Associated post ID"),
 *     @OA\Property(property="content", type="string", description="Comment content"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Comment creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Comment update timestamp")
 * )
 *
 * @OA\Schema(
 *     schema="PaginatedResponse",
 *     type="object",
 *     description="Paginated response for posts",
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Post")
 *     ),
 *     @OA\Property(property="current_page", type="integer", description="Current page number"),
 *     @OA\Property(property="last_page", type="integer", description="Last page number"),
 *     @OA\Property(property="total", type="integer", description="Total number of items")
 * )
 */

class PostController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/auth/posts",
     *     summary="Get list of posts with comments and images",
     *     tags={"Posts"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of posts per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/PaginatedResponse")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function index(Request $request)
    {
        $query = Post::with(['comments' => function ($query) {
            $query->select('id', 'post_id', 'content', 'created_at', 'updated_at');
        }, 'images'])
        ->select('id', 'content', 'created_at', 'updated_at');

        $query->orderBy('created_at', 'desc');

        $page = $request->query('page', 1);
        $limit = $request->query('limit', 10);
        $posts = $query->paginate($limit, ['*'], 'page', $page);

        return response()->json($posts);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/posts",
     *     summary="Create a new post",
     *     tags={"Posts"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Post data",
     *         @OA\JsonContent(
     *             required={"title", "content"},
     *             @OA\Property(property="title", type="string", example="Sample Post Title"),
     *             @OA\Property(property="content", type="string", example="This is the post content")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Post created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Post")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/auth/posts/{id}",
     *     summary="Get a single post by ID",
     *     tags={"Posts"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1),
     *         description="ID of the post"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post fetched successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Post")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function show($id)
    {
        $post = Post::with('comments', 'images')->find($id);
        
        if(!$this->check_post_permissions($post)){
            return response()->json(["success"=> false, "error"=> true, "message" => "Post not available"], 401);
        }
        return response()->json(["post"=> $post, "success"=> true, "error"=> false, "message"=> "Post fetched successfully!!"], 200);
        
    }

    /**
     * @OA\Patch(
     *     path="/api/auth/posts/{id}",
     *     summary="Update a post",
     *     tags={"Posts"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1),
     *         description="ID of the post"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Updated post data",
     *         @OA\JsonContent(
     *             required={"title", "content"},
     *             @OA\Property(property="title", type="string", example="Updated Post Title"),
     *             @OA\Property(property="content", type="string", example="Updated post content")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Post")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function update(Request $request, $id)
    {
        $post = Post::find($id);

        if(!$this->check_post_permissions($post)){
            return response()->json(["success"=> false, "error"=> true, "message" => "Post not available"], 401);
        }

        $post->title = $request->title;
        $post->content = $request->content;
        $post->save();

        return response()->json(["post"=> $post, "success"=> true, "error"=> false, "message" => "Post updated successfully!!"], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/auth/posts/{id}",
     *     summary="Delete a post",
     *     tags={"Posts"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1),
     *         description="ID of the post"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post deleted successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Post")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function destroy($id)
    {
        $post = Post::find($id);
        
        if(!$this->check_post_permissions($post)){
            return response()->json(["success"=> false, "error"=> true, "message" => "Post not available"], 401);
        }

        $post->delete();
        
        return response()->json(["post"=> $post, "success"=> true, "error"=> false, "message" => "Post deleted successfully!!"], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/auth/posts/search",
     *     summary="Search for posts with filters",
     *     tags={"Posts"},
     *     @OA\Parameter(
     *         name="title",
     *         in="query",
     *         description="Filter posts by title",
     *         required=false,
     *         @OA\Schema(type="string", example="Sample Title")
     *     ),
     *     @OA\Parameter(
     *         name="content",
     *         in="query",
     *         description="Filter posts by content",
     *         required=false,
     *         @OA\Schema(type="string", example="Sample Content")
     *     ),
     *     @OA\Parameter(
     *         name="author",
     *         in="query",
     *         description="Filter posts by author (ID or username)",
     *         required=false,
     *         @OA\Schema(type="string", example="1 or JohnDoe")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of posts per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search results fetched successfully",
     *         @OA\JsonContent(ref="#/components/schemas/PaginatedResponse")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function search(Request $request)
    {
        $query = Post::with(['author', 'comments' => function ($query) {
            $query->select('id', 'post_id', 'content');
        }])
        ->select('posts.id', 'title', 'content', 'author_id')
        ->join('users', 'posts.author_id', '=', 'users.id'); // Join with users table

        // Apply search filters
        if ($request->has('title')) {
            $query->where('title', 'like', '%' . $request->input('title') . '%');
        }

        if ($request->has('content')) {
            $query->where('content', 'like', '%' . $request->input('content') . '%');
        }

        if ($request->has('author')) {
            // Allow searching by either ID or username
            $query->where(function ($q) use ($request) {
                $q->where('posts.author_id', $request->input('author'))
                ->orWhere('users.username', 'like', '%' . $request->input('author') . '%');
            });
        }

        // Pagination and sorting
        $page = $request->query('page', 1);
        $limit = $request->query('limit', 10);
        $posts = $query->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            "success" => true,
            "error" => false,
            "message" => "Search results fetched successfully!",
            "data" => $posts
        ]);
    }



    public function check_post_permissions($post){
        $return = (!$post || (auth()->id() !== $post->author_id && !auth()->user()->hasRole('admin'))) ? false : true;
        return $return;
    }
}
