<?php

use Illuminate\Support\Facades\Route;
use App\Services\RecipePDFService;
use App\Models\Recipe;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Recipe PDF routes
Route::get('/recipes/{recipe}/pdf/preview', function (Recipe $recipe) {
    $pdfService = new RecipePDFService();
    return $pdfService->streamRecipePDF($recipe);
})->name('recipes.pdf.preview');

Route::get('/recipes/{recipe}/pdf/download', function (Recipe $recipe) {
    $pdfService = new RecipePDFService();
    return $pdfService->downloadRecipePDF($recipe);
})->name('recipes.pdf.download');

Route::get('/{any?}', function () {
    return view('spa');
})->where('any', '^(?!api|admin).*$');
