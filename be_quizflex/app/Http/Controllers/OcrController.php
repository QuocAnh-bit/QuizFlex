<?php

namespace App\Http\Controllers;

use App\AI\Prompts\QuizPrompt;
use App\Services\AI\AIService;
use Illuminate\Http\Request;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Smalot\PdfParser\Parser;
use Spatie\PdfToImage\Pdf;

class OcrController extends Controller
{
    public function scan(Request $request)
    {
        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,bmp,tif,tiff,webp,pdf'],
        ]);

        try {
            $file = $request->file('image');

            $tesseractPath = env('TESSERACT_PATH', 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe');
            $tesseractLang = env('TESSERACT_LANG', 'vie');

            $extension = strtolower($file->getClientOriginalExtension());
            $text = '';

            // CASE 1: ẢNH
            if ($extension !== 'pdf') {
                $text = (new TesseractOCR($file->getRealPath()))
                    ->executable($tesseractPath)
                    ->lang($tesseractLang)
                    ->run();
            }

            // CASE 2 + 3: PDF
            else {
                // Thử đọc PDF có text trước
                $parser = new Parser();
                $pdf = $parser->parseFile($file->getRealPath());
                $text = trim($pdf->getText());

                // CASE 3: Nếu PDF không có text => PDF scan => convert sang ảnh OCR
                if ($text === '') {
                    $pdfPath = $file->storeAs('ocr', uniqid() . '.pdf', 'public');
                    $fullPdfPath = storage_path('app/public/' . $pdfPath);

                    $imagePath = storage_path('app/public/ocr/' . uniqid() . '.jpg');

                    $pdfImage = new Pdf($fullPdfPath);

                    $pageCount = $pdfImage->getNumberOfPages();
                    $allText = [];

                    for ($page = 1; $page <= $pageCount; $page++) {
                        $pageImagePath = storage_path('app/public/ocr/page_' . uniqid() . '.jpg');

                        $pdfImage->setPage($page)
                            ->saveImage($pageImagePath);

                        $pageText = (new TesseractOCR($pageImagePath))
                            ->executable($tesseractPath)
                            ->lang($tesseractLang)
                            ->run();

                        $allText[] = $pageText;
                    }

                    $text = implode("\n\n", $allText);
                }
            }

            $service = app(AIService::class);
            $prompt = QuizPrompt::textToQuizJson($text);
            $data = $service->parseQuiz($prompt);
            return response()->json([
                'success' => true,
                'message' => 'OCR success',
                'filename' => $file->getClientOriginalName(),
                'quizOrc' => $data,
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
