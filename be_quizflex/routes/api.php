<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use thiagoalessio\TesseractOCR\TesseractOCR;





Route::post('/ocr/scan', function (Request $request) {
    if (!$request->hasFile('image')) {
        return response()->json([
            'success' => false,
            'message' => 'Vui lòng upload file với key là image'
        ], 400);
    }

    $image = $request->file('image');

    try {
        $text = (new TesseractOCR($image->getRealPath()))
            ->executable('C:\\Program Files\\Tesseract-OCR\\tesseract.exe')
            ->lang('vie')
            ->run();

        return response()->json([
            'success' => true,
            'text' => trim($text)
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'OCR failed',
            'error' => $e->getMessage()
        ], 500);
    }
});
// routes/api.php

Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API test success',
        'data' => [
            'name' => 'QuizFlex',
            'version' => '1.0.0',
            'author' => 'Quoc Anh'
        ]
    ]);
});


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
