<?php

namespace App\Http\Controllers;

use App\AI\Prompts\QuizPrompt;
use App\Services\AI\AIService;
use App\Services\QuizStoreService;
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

        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $tier = $user->getSubscriptionTier();

        if ($tier === 'free') {
            return response()->json([
                'success' => false,
                'message' => 'Tính năng scan tài liệu OCR yêu cầu nâng cấp gói Plus trở lên.'
            ], 403);
        }

        if ($tier !== 'admin' && $tier !== 'ultra') {
            $startOfMonth = now()->startOfMonth();
            $scanCount = \App\Models\AiLog::where('user_id', $user->id)
                ->where('action_type', 'ocr_upload')
                ->where('created_at', '>=', $startOfMonth)
                ->count();

            $limit = $tier === 'pro' ? 50 : 10;

            if ($scanCount >= $limit) {
                return response()->json([
                    'success' => false,
                    'message' => "Bạn đã đạt giới hạn lượt scan OCR trong tháng này ({$limit} lượt). Vui lòng nâng cấp lên gói cao hơn!"
                ], 403);
            }
        }

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
                    if (!class_exists('Imagick')) {
                        throw new \Exception('Hệ thống Laravel thiếu thư viện chuyển đổi PDF sang ảnh (Imagick/Ghostscript). Vui lòng upload trực tiếp bằng file ảnh (PNG, JPG, JPEG) thay vì file PDF scan.');
                    }

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

                $data = $service->mistralOcrToQuizJson(
                    $file->getRealPath(),
                    $file->getClientOriginalExtension()
                );
            } else {

                $prompt = QuizPrompt::textToQuizJson($text);

                $data = $service->parseQuiz($prompt);
            }

            \App\Models\AiLog::create([
                'user_id' => $user->id,
                'action_type' => 'ocr_upload',
                'status' => 'success',
                'questions_generated' => count($data['questions'] ?? []),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'OCR success',
                'filename' => $file->getClientOriginalName(),
                'quizOrc' => $data,
            ]);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();

            // Dịch lỗi rỗng chữ của Tesseract sang tiếng Việt
            if (str_contains($msg, 'did not produce any output')) {
                $msg = 'Không tìm thấy văn bản nào trong tài liệu. Vui lòng kiểm tra và đảm bảo ảnh chụp rõ nét, có chứa chữ và không bị lật ngược.';
            }
            // Dịch lỗi thiếu thư viện Imagick/Ghostscript khi cắt PDF
            elseif (str_contains(strtolower($msg), 'imagick') || str_contains(strtolower($msg), 'pdf-to-image') || str_contains(strtolower($msg), 'ghostscript')) {
                $msg = 'Hệ thống Laravel thiếu thư viện chuyển đổi PDF sang ảnh (Imagick/Ghostscript). Vui lòng upload trực tiếp bằng file ảnh (PNG, JPG, JPEG) thay vì file PDF scan.';
            }

            return response()->json([
                'success' => false,
                'message' => $msg,
                'error' => $msg,
            ], 500);
        }
    }

    public function importQuiz(Request $request, QuizStoreService $quizStoreService)
    {
        $normalizedData = $quizStoreService->normalizeOcrPayload($request->all());

        $quiz = $quizStoreService->createQuizWithQuestions(
            $normalizedData,
            auth('api')->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Import OCR thành công',
            'data' => $quiz,
        ], 201);
    }
}
