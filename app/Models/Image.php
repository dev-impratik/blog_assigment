<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Image extends Model
{
    use HasFactory;

    /**
     * Get the post that owns the image.
     */

    protected $fillable = ['post_id', 'url', 'is_primary'];
    protected $table = 'image';



    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
