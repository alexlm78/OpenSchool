<?php

use App\Http\Controllers\SubmissionFileDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->get('/submission-files/{submissionFile}/download', SubmissionFileDownloadController::class)
    ->name('submission-files.download');
