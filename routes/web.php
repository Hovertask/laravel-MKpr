<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

Route::get('/', function () {
    return view('welcome');
});

// NOTE: broadcasting auth endpoint moved to `routes/api.php` to support
// token (Bearer) based authentication used by the React SPA.
