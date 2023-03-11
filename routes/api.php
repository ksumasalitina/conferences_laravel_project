<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LectureController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ZoomController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::controller(MeetingController::class)->group(function (){
    Route::get('meetings', 'index');
    Route::get('/countries','getCountries');
});

Route::controller(MeetingController::class)->middleware(['api', 'auth:sanctum'])->group(function() {
    Route::prefix('meetings')->group(function() {
        Route::get('/export', 'export');
        Route::get('/export/members/{id}','exportMembers');
        Route::get('/filter','filterMeetings');
        Route::get('/search', 'search');
    });
    Route::post('join/{id}', 'join')->name('join');
    Route::post('cancel/{id}', 'cancel')->name('cancel');
    Route::resource('meetings', MeetingController::class)->only(['show','create','store','update','destroy']);
});

Route::controller(LectureController::class)->middleware('auth:sanctum')->group(function (){
    Route::prefix('lectures')->group(function (){
        Route::get('/filter','filterLectures');
        Route::get('/search', 'search');
        Route::get('/export/{id}', 'export');
        Route::get('/download/{presentation}','download');
    });
    Route::get('/meetings/lectures/{id}', 'showByMeeting');
    Route::get('/meetings/slots/{id}', 'getSlots');
    Route::get('/meeting/{id}/lecture/', 'showByMeetingUser');
    Route::resource('lectures', LectureController::class);
});

Route::controller(CommentController::class)->middleware('auth:sanctum')->group(function(){
    Route::resource('comments', CommentController::class);
    Route::get('/comments/export/{id}', 'export');
});

Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('/logout', 'logout')->middleware('auth:sanctum');
    Route::put('/profile/edit/{id}','update')->middleware('auth:sanctum');
});

Route::controller(FavoriteController::class)->middleware('auth:sanctum')->prefix('favorites')->group(function (){
    Route::post('/add/{id}', 'add');
    Route::delete('/delete/{id}', 'delete');
    Route::get('/', 'show');
});

Route::controller(CategoryController::class)->middleware('auth:sanctum')->group(function (){
    Route::post('/category','add');
    Route::get('/categories/list','getCategoryList');
    Route::get('/categories','getCategories');
    Route::post('/category/delete', 'deleteCategory');
});

Route::controller(ZoomController::class)->middleware('auth:sanctum')->prefix('zoom')->group(function (){
    Route::get('/list','list');
    Route::post('/','create');
    Route::put('/{id}','update');
    Route::get('/{id}','get');
    Route::delete('/{id}','delete');
});

