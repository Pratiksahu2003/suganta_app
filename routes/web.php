<?php

use App\Http\Controllers\StorageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| File Storage Proxy - Serve GCS/S3 files through app domain
|--------------------------------------------------------------------------
| When using cloud storage (GCS, S3), files are fetched and streamed here
| so URLs stay on your domain: https://yoursite.com/storage/profile-images/xxx.jpg
*/
Route::get('/storage/{path}', [StorageController::class, 'serve'])
    ->where('path', '.*')
    ->name('storage.serve');

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


