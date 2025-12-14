<?php

use Illuminate\Support\Facades\Route;

// Catch-all route to serve the SPA
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');

require __DIR__.'/auth.php';
