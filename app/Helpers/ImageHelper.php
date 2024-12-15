<?php

namespace App\Helpers;

use Spatie\Image\Image;

class ImageHelper
{
    /**
     * Generate a thumbnail for a given image.
     *
     * @param string $filePath
     * @param int $width
     * @param int $height
     * @return string
     */
    public static function generateThumbnail($filePath, $width = 150, $height = 150)
    {
        // Ensure the 'thumbnails' directory exists
        $thumbnailDir = storage_path('app/public/thumbnails');
        if (!is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        // Resolve full file path and thumbnail path
        $fullPath = storage_path('app/public/' . $filePath);
        $thumbnailPath = $thumbnailDir . '/' . basename($filePath);

        // Check if the file exists before processing
        if (!file_exists($fullPath)) {
            throw new \Exception("File does not exist at path: " . $fullPath);
        }

        // Generate and save the thumbnail
        Image::load($fullPath)
            ->width($width)
            ->height($height)
            ->save($thumbnailPath);

        // Return the relative path of the thumbnail
        return 'thumbnails/' . basename($filePath);
    }
}

