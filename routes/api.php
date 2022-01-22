<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\Products\UploadController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Middleware\HeaderProjectNameRequired;

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

Route::group(['middleware' => ['cors']], function () {
    Route::get('/', function () {
        return response()->json(['message' => 'Hello World!'], 200);
    });

//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});

//Route::group(['middleware' => ['auth:api']], function () {
//
//});

    Route::get('/product/{slug}', [ProductController::class, 'detail'])->middleware(HeaderProjectNameRequired::class);
    Route::delete('products/{id}', [ProductController::class, 'customDestroy']);
    Route::resource('products', ProductController::class)->middleware(HeaderProjectNameRequired::class);
    Route::get('/category/{slug}', [CategoryController::class, 'detail'])->middleware(HeaderProjectNameRequired::class);
    Route::resource('categories', CategoryController::class)->middleware(HeaderProjectNameRequired::class);
    Route::post('login', [AuthenticationController::class, 'login']);
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::group(['middleware' => ['auth:api']], function () {
        Route::post('logout', [AuthenticationController::class, 'logout']);
//    Route::post('register', 'Auth\RegisterUserController@store');
        Route::post('me', [AuthenticationController::class, 'me']);
        Route::post('products/upload', [UploadController::class, 'upload']);
    });
});
