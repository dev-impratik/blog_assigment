<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ImageController;


Route::group([
    'middleware' => ['api'],
    'prefix' => 'auth'
], function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

Route::group([
    'middleware' => ['api', 'authorize_jwt'],
    'prefix' => 'auth'
], function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
    Route::post('/me', [AuthController::class, 'me'])->name('me');
    Route::post('/posts', [PostController::class, 'store'])->name('store');
    Route::get('/posts', [PostController::class, 'index'])->name('index');
    Route::get('/posts/search', [PostController::class, 'search'])->name('searchposts');
    Route::get('/posts/{id}', [PostController::class, 'show'])->name('show');
    Route::patch('/posts/{id}', [PostController::class, 'update'])->name('udpate');
    Route::delete('/posts/{id}', [PostController::class, 'destroy'])->name('destroy');

    Route::post('/posts/{postId}/comments', [CommentController::class, 'store'])->name('storecomment');
    Route::get('/posts/{postId}/comments', [CommentController::class, 'index'])->name('indexcomment');
    Route::patch('/comments/{id}', [CommentController::class, 'update'])->name('udpatecomment');
    Route::delete('/comments/{id}', [CommentController::class, 'destroy'])->name('destroycomment');

    Route::post('/posts/{postId}/images', [ImageController::class, 'uploadImages'])->name('');
    Route::get('/posts/{postId}/images', [ImageController::class, 'getImages'])->name('');
    Route::put('/images/{id}/primary', [ImageController::class, 'setPrimary'])->name('');
    Route::delete('/images/{id}', [ImageController::class, 'deleteImage'])->name('');

});
