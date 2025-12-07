<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Taeab.com API',
        'version' => '1.0.0'
    ]);
});

