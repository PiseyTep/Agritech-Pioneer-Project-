<?php

use App\Http\Controllers\SwaggerUIController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Route::get('api/docs', [SwaggerUIController::class, 'index']);

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/login', [AuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'adminLogin']);

Route::get('/admin/register', [AuthController::class, 'showRegisterForm'])->name('admin.register');
Route::post('/admin/register', [AuthController::class, 'adminRegister']);
