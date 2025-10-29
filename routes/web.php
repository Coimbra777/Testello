<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('import');
});

Route::get('/import', function () {
    return view('import');
})->name('freight.import.interface');

Route::get('/freight', function () {
    return redirect('/import');
});
