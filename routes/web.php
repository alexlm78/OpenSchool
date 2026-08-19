<?php

declare(strict_types=1);

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\SubmissionFileDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/alumno');
});

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])
    ->name('locale.switch')
    ->middleware('web');

Route::middleware(['auth'])->get('/submission-files/{submissionFile}/download', SubmissionFileDownloadController::class)
    ->name('submission-files.download');
