<?php

use App\Http\Controllers\ArtifactController;
use App\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ArtifactController::class, 'index'])->name('artifacts.index');
Route::get('/artifacts/{artifact}', [ArtifactController::class, 'show'])->name('artifacts.show');

Route::get('/import/trigger', [ImportController::class, 'trigger']);
Route::get('/import/status', [ImportController::class, 'status']);
Route::get('/import/reset', [ImportController::class, 'reset']);
Route::get('/import/log', [ImportController::class, 'log']);