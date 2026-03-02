<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Placeholder routes for API notifications
Route::get('/dashboard', function () {})->name('dashboard');
Route::get('/dashboard/profile', function () {})->name('dashboard.profile');
Route::get('/login', function () {})->name('login');
Route::get('/verification/notice', function () {})->name('verification.notice');
Route::get('/notifications/settings', function () {})->name('notifications.settings');
Route::get('/subscription/details', function () {})->name('subscription.details');
