<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Invoice Route (for signed URL generation only - invoice served on main site)
|--------------------------------------------------------------------------
*/
Route::get('/payment/invoice/{orderId}', function () {
    return redirect()->away(config('invoice.base_url', 'https://www.suganta.com'));
})->name('payments.invoice')->where('orderId', '[A-Za-z0-9_\-]+');

Route::get('/', function () {
    return  redirect('https://www.suganta.com');
});

Route::get('/api', function () {
    return  redirect('https://www.suganta.com');
});

Route::get('/api/v1', function () {
    return  redirect('https://www.suganta.com');
});


