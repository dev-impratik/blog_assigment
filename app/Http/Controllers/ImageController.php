<?php
namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Helpers\ImageHelper;

/**
 * @OA\Schema(
 *     schema="Image",
 *     type="object",
 *     description="Image schema",
 *     required={"id", "url", "is_primary"},
 *     @OA\Property(property="id", type="integer", format="int64", description="Unique identifier of the image"),
 *     @OA\Property(property="url", type="string", format="uri", description="URL of the image"),
 *     @OA\Property(property="is_primary", type="boolean", description="Whether the image is the primary one for the post")
 * )
 */
class ImageController extends Controller
{
    /**
     * Upload images for a blog post.
     */
    /**
     * @OA\Post(
     *     path="/api/auth/posts/{postId}/images",
     *     summary="Upload images for a blog post",
     *     tags={"Images"},
     *     description="Upload multiple images for a specific blog post.",
     *     operationId="uploadImages",
     *     @OA\Parameter(
     *         name="postId",
     *         in="path",
     *         required=true,
     *         description="ID of the blog post",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         description="Images to upload",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"images"},
     *                 @OA\Property(
     *                     property="images",
     *                     type="array",
     *                     @OA\Items(
     *                         type="file",
     *                         format="binary",
     *                         description="Image files to upload"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Images uploaded successfully!",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Images uploaded successfully!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error uploading images",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An error occurred")
     *         )
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function uploadImages(Request $request, $postId)
    {
        // Validate the request
        $request->validate([
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        // Find the post
        $post = Post::findOrFail($postId);

        try {
            
            foreach ($request->file('images') as $file) {
               
                $dir = storage_path('app/public/images');
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                $filePath = $file->store('images', 'public');

                $image = new Image([
                    'url' => $filePath,
                    'is_primary' => false
                ]);

                $post->images()->save($image);
            }

            return response()->json(['message' => 'Images uploaded successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Get all images for a blog post.
     */
    /**
     * @OA\Get(
     *     path="/api/auth/posts/{postId}/images",
     *     summary="Get all images for a blog post",
     *     tags={"Images"},
     *     description="Retrieve all images associated with a specific blog post.",
     *     operationId="getImages",
     *     @OA\Parameter(
     *         name="postId",
     *         in="path",
     *         required=true,
     *         description="ID of the blog post",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of images",
     *         @OA\JsonContent(type="array",
     *             @OA\Items(ref="#/components/schemas/Image")
     *         )
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function getImages($postId)
    {
        $post = Post::find($postId);

        if($post !== null){
            $images = $post->images;
            return response()->json(["images"=> $post, "success"=> true, "error"=> false, "message"=> "Image fetched successfully!!"], 200);
        }
        return response()->json(["images"=> $post, "success"=> true, "error"=> false, "message"=> "Sorry no images found."], 200);
    }

    /**
     * Set an image as the primary.
     */
    /**
     * @OA\Put(
     *     path="/api/auth/images/{id}/primary",
     *     summary="Set an image as the primary",
     *     tags={"Images"},
     *     description="Set a specific image as the primary (featured) image for a blog post.",
     *     operationId="setPrimary",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the image",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Primary image set successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Primary image set successfully")
     *         )
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function setPrimary($id)
    {
        $image = Image::find($id);
        if($image == null){
            return response()->json(["images"=> $image, "success"=> true, "error"=> false, "message"=> "Sorry no images found."], 200);
        }
        
        $image->post->images()->update(['is_primary' => false]);

        
        $image->is_primary = true;
        $image->save();

        // $thumbnailPath = ImageHelper::generateThumbnail($filePath);

        return response()->json([
            'message' => 'Primary image set successfully',
            // 'thumbnail' => $thumbnailPath
        ]);
    }

    /**
     * Delete an image.
     */
    /**
     * @OA\Delete(
     *     path="/api/auth/images/{id}",
     *     summary="Delete an image",
     *     tags={"Images"},
     *     description="Delete a specific image from a blog post.",
     *     operationId="deleteImage",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the image",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Image deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Image deleted successfully")
     *         )
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function deleteImage($id)
    {
        $image = Image::find($id);
        if($image == null){
            return response()->json(["images"=> $image, "success"=> true, "error"=> false, "message"=> "Sorry no images found."], 200);
        }
        Storage::disk('public')->delete(str_replace('/storage/', '', $image->url)); // Remove the image file
        $image->delete();

        return response()->json(['message' => 'Image deleted successfully']);
    }
}
