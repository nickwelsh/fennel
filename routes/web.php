<?php

use nickwelsh\Fennel\Http\Controllers\ImageController;

Route::get('/'.Config::string('fennel.endpoint_name').'/{options}/{path}', [ImageController::class, 'show'])
    ->where('options', '([a-zA-Z\.-]+=[a-zA-Z0-9;\.-]+,?)+')
    ->where('path', '.*')
    ->name('fennel.handle');
