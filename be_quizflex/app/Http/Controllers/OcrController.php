<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use thiagoalessio\TesseractOCR\TesseractOCR;

class OcrController extends Controller
{
    public function scan(Request $request)
    {
        if (!$request->hasFile('image')) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng upload file với key là image',
            ], 400);
        }

        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,bmp,tif,tiff,webp'],
        ]);

        try {
            $image = $request->file('image');

            $tesseractPath = env('TESSERACT_PATH', 'G:/QuizFlex/tesseract.exe');
            $tesseractLang = env('TESSERACT_LANG', 'eng');

            $text = (new TesseractOCR($image->getRealPath()))
                ->executable($tesseractPath)
                ->lang($tesseractLang)
                ->run();

            return response()->json([
                'success' => true,
                'message' => 'OCR success',
                'filename' => $image->getClientOriginalName(),
                'text' => trim($text),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'OCR failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}