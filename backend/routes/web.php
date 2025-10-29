<?php

use Illuminate\Support\Facades\Route;

Route::get('/' , function () {
    return ['message' => 'Budget Manager API', 'version' => '1.0.0'];
});
