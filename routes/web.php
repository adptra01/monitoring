<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

// Route::view('/', 'welcome', [
//     'canRegister' => Features::enabled(Features::registration()),
// ])->name('home');

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

require __DIR__.'/settings.php';
