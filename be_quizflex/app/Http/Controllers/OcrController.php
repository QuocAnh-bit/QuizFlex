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

            if ($request->mode === 'math') {

                // $data = $service->mistralOcrToQuizJson(
                //     $file->getRealPath(),
                //     $file->getClientOriginalExtension()
                // );
                $data = [
                    'questions' => [
                        [
                            'question' => 'HTML là gì?',
                            'options' => [
                                'A' => 'Ngôn ngữ lập trình',
                                'B' => 'Ngôn ngữ đánh dấu',
                                'C' => 'Cơ sở dữ liệu',
                                'D' => 'Hệ điều hành',
                            ],
                            'correct_answer' => 'B',
                        ],
                        [
                            'question' => 'CSS dùng để làm gì?',
                            'options' => [
                                'A' => 'Thiết kế giao diện',
                                'B' => 'Lưu dữ liệu',
                                'C' => 'Xử lý API',
                                'D' => 'Tạo database',
                            ],
                            'correct_answer' => 'A',
                        ],
                        [
                            'question' => 'Giá trị của $2^3$ là bao nhiêu?',
                            'options' => [
                                'A' => '6',
                                'B' => '8',
                                'C' => '9',
                                'D' => '12',
                            ],
                            'correct_answer' => 'B',
                        ],
                        [
                            'question' => 'Giải phương trình $x + 5 = 10$',
                            'options' => [
                                'A' => '3',
                                'B' => '4',
                                'C' => '5',
                                'D' => '6',
                            ],
                            'correct_answer' => 'C',
                        ],
                        [
                            'question' => 'Tính đạo hàm của $f(x)=x^2$',
                            'options' => [
                                'A' => '$2x$',
                                'B' => '$x$',
                                'C' => '$x^3$',
                                'D' => '$2$',
                            ],
                            'correct_answer' => 'A',
                        ],
                        [
                            'question' => 'Ký hiệu của số pi là?',
                            'options' => [
                                'A' => '$\\alpha$',
                                'B' => '$\\beta$',
                                'C' => '$\\pi$',
                                'D' => '$\\theta$',
                            ],
                            'correct_answer' => 'C',
                        ],
                        [
                            'question' => 'Tổng các góc trong tam giác bằng bao nhiêu?',
                            'options' => [
                                'A' => '90°',
                                'B' => '180°',
                                'C' => '270°',
                                'D' => '360°',
                            ],
                            'correct_answer' => 'B',
                        ],
                        [
                            'question' => 'Giới hạn $\\lim_{x \\to \\infty} \\frac{x}{x+1}$ bằng',
                            'options' => [
                                'A' => '0',
                                'B' => '2',
                                'C' => '1',
                                'D' => '-1',
                            ],
                            'correct_answer' => 'C',
                        ],
                        [
                            'question' => 'Vue.js là gì?',
                            'options' => [
                                'A' => 'Framework PHP',
                                'B' => 'Framework Python',
                                'C' => 'Framework Java',
                                'D' => 'Framework JavaScript',
                            ],
                            'correct_answer' => 'D',
                        ],
                        [
                            'question' => 'Tính tích phân $\\int 2x\\,dx$',
                            'options' => [
                                'A' => '$x^2 + C$',
                                'B' => '$2x + C$',
                                'C' => '$x + C$',
                                'D' => '$2x^2 + C$',
                            ],
                            'correct_answer' => 'A',
                        ],
                    ],
                ];
            } else {

                $prompt = QuizPrompt::textToQuizJson($text);

                $data = $service->parseQuiz($prompt);
            }

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
