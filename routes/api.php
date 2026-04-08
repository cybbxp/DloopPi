<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AssetsController;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// 资产管理 API
Route::prefix('api/assets')->middleware(['webapi'])->group(function () {
    // 项目和分类
    Route::get('projects', [AssetsController::class, 'projects']);
    Route::post('projects', [AssetsController::class, 'createProject']);
    Route::get('categories', [AssetsController::class, 'categories']);

    // 资产 CRUD
    Route::get('/', [AssetsController::class, 'lists']);
    Route::post('/', [AssetsController::class, 'store']);
    Route::get('{id}', [AssetsController::class, 'detail']);
    Route::put('{id}', [AssetsController::class, 'update']);
    Route::delete('{id}', [AssetsController::class, 'destroy']);

    // 版本管理
    Route::post('{id}/versions', [AssetsController::class, 'uploadVersion']);
    Route::get('{id}/versions', [AssetsController::class, 'versions']);
    Route::get('{id}/versions/{version}/download', [AssetsController::class, 'downloadVersion'])->name('api.assets.versions.download');

    // 任务关联
    Route::post('{id}/tasks', [AssetsController::class, 'attachTask']);

    // 本地路径
    Route::get('{id}/localPath', [AssetsController::class, 'localPath']);
    
    // 挂载检测与挂载
    Route::post('mount-status', [AssetsController::class, 'mountStatus']);
    Route::post('mount-share', [AssetsController::class, 'mountShare']);
});
