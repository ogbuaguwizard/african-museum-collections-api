<?php

use App\Http\Controllers\ArtifactController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ArtifactController::class, 'index'])->name('artifacts.index');
Route::get('/artifacts/{artifact}', [ArtifactController::class, 'show'])->name('artifacts.show');