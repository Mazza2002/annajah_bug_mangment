<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BugController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Authentication Routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        
        // Auth/Profile
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/user', [AuthController::class, 'user']);
        Route::get('/auth/roles', function() {
            return response()->json(\App\Models\Role::all());
        });
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
        Route::get('/auth/sessions', [AuthController::class, 'sessions']);
        Route::delete('/auth/sessions/{id}', [AuthController::class, 'revokeSession']);

        // Bugs
        Route::apiResource('bugs', BugController::class);
        Route::patch('bugs/{bug}/status', [BugController::class, 'updateStatus']);
        Route::patch('bugs/{bug}/assign', [BugController::class, 'assign']);
        Route::post('bugs/{bug}/comments', [BugController::class, 'addComment']);
        Route::post('bugs/{bug}/attachments', [BugController::class, 'uploadAttachment']);
        Route::delete('bugs/attachments/{id}', [BugController::class, 'deleteAttachment']);
        Route::post('bugs/{bug}/links', [BugController::class, 'linkBug']);
        
        // AI Layer mock routes
        Route::post('bugs/check-duplicates', [BugController::class, 'checkDuplicates']);
        Route::post('bugs/suggest-severity', [BugController::class, 'suggestSeverity']);

        // Projects
        Route::apiResource('projects', ProjectController::class);

        // Taxonomies (Categories, Tags)
        // Let's assume we have a basic TaxonomyController or just return from DB
        Route::get('/categories', function() {
            return response()->json(\App\Models\Category::all());
        });
        Route::get('/tags', function() {
            return response()->json(\App\Models\Tag::all());
        });

        // Notifications
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead']);

        // Users & Admin
        Route::get('users', [UserController::class, 'index']);
        Route::post('users/invite', [UserController::class, 'invite']);
        Route::put('users/{user}/role', [UserController::class, 'changeRole']);
        Route::delete('users/{user}', [UserController::class, 'destroy']);

        // Analytics
        Route::get('analytics/dashboard', [AnalyticsController::class, 'overview']);
        Route::get('analytics/performance', [AnalyticsController::class, 'developerPerformance']);
    });
});
