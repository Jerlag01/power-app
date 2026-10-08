<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProjectController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'redirectToLocale']);

Route::prefix('{locale}')
    ->whereIn('locale', array_keys(config('app.locales')))
    ->middleware(SetLocale::class)
    ->group(function () {
        Route::get('/', [PageController::class, 'home'])->name('home');
        Route::get('/expertises', [PageController::class, 'expertises'])->name('expertises');
        Route::get('/certificates', [PageController::class, 'certificates'])->name('certificates');
        Route::get('/who-is-who', [PageController::class, 'whoIsWho'])->name('who-is-who');
        Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');

        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');

        Route::get('/news', [PostController::class, 'index'])->name('posts.index');
        Route::get('/news/{post}', [PostController::class, 'show'])->name('posts.show');

        Route::get('/contact', [ContactController::class, 'show'])->name('contact');
        Route::post('/contact', [ContactController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('contact.store');
    });
