<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnswerController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OcrController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PaymentController;
use App\Services\AI\AIService;
use App\AI\Prompts\QuizPrompt;



Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API test success',
        'data' => [
            'name' => 'QuizFlex',
            'version' => '1.1.0',
            'author' => 'QuizFlex Team',
        ],
    ]);
});



Route::get('/ai-test', function () {
    try {
        $service = app(AIService::class);
        $promt = QuizPrompt::textToQuizJson("
            Câu hỏi trắc nghiệm về mắt Thầy Hiểu NKC

CÂU HỎI TRẮC NGHIỆM VỀ MẮT

Quyét mã QR để xem đáp án chi tiết

Câu 1. Khi nói về sự điều tiết của mắt, phát biểu nào sau đây là đúng?

A. Do có sự điều tiết, nên mắt có thể nhìn rõ được tất cả các vật nằm trước mắt

B. Khi quan sát các vật dịch chuyển ra xa mắt thì thể thuỷ tinh của mắt cong dần lên

C. Khi quan sát các vật dịch chuyển ra xa mắt thì thể thuỷ tinh của mắt xẹp dần xuống.

D. Khi quan sát các vật dịch chuyển lại gần mắt thì thể thuỷ tinh của mắt xẹp dần xuống.

Câu 2. Để quan sát rõ các vật thì mắt phải điều tiết sao cho

A. Độ tụ của mắt luôn giảm xuống

B. Ảnh của vật luôn nằm trên võng mạc

C. Độ tụ của mắt luôn tăng lên

D. Ảnh của vật nằm giữa thuỷ tinh thể và võng mạc

Câu 3. Điểm cực viễn (C

v

) của mắt là

A. Khi mắt không điều tiết, điểm gần nhất trên trục của mắt cho ảnh trên võng mạc.

B. Khi mắt điều tiết tối đa, điểm xa nhất trên trục của mắt cho ảnh trên võng mạc

C. Khi mắt điều tiết tối đa, điểm gần nhất trên trục của mắt cho ảnh trên võng mạc

D. Khi mắt không điều tiết, điểm xa nhất trên trục của mắt cho ảnh trên võng mạc.

Câu hỏi trắc nghiệm về mắt Thầy Hiểu NKC

Câu 4. Điểm cực cận (C

c

) của mắt là

A. Khi mắt không điều tiết, điểm gần nhất trên trục của mắt cho ảnh trên võng mạc

B. Khi mắt điều tiết tối đa, điểm gần nhất trên trục của mắt cho ảnh trên võng mạc

C. Khi mắt điều tiết tối đa, điểm xa nhất trên trục của mắt cho ảnh trên võng mạc

D. Khi mắt không điều tiết, điểm xa nhất trên trục của mắt cho ảnh trên võng mạc.

Câu 5. Khi nói về khoảng nhìn rõ của mắt, phát biểu nào sau đây sai?

A. Mắt có khoảng nhìn rõ từ 25cm đến vô cực là bình thương

B. Mắt có khoảng nhìn rõ từ 10 cm đến 50cm là mắt bị cận thị

C. Mắt có khoảng nhìn từ 80cm đến vô cực là vắt bị viễn thị

D. Mắt có khoảng nhìn rõ từ 15cm đến vô cực là mắt bị tật cận thị.

Câu 6. Xét về phương diện quang hình, mắt có tác dụng tương đương với hệ quang học

nào sau đây?

A. hệ lăng kính

B. hệ thấu kính hội tụ

C. thấu kính phân kì

D. hệ gương cầu.

Câu 7. Khi nói về các tật của mắt, phát biểu nào sau đây là sai?

A. Mắt cận không nhìn rõ được các vật ở xa, chỉ nhìn rõ được các vật ở gần

B. Mắt viễn không nhìn rõ được các vật ở gần, chỉ nhìn rõ được ác vật ở xa

C. Mắt lão không nhìn rõ các vật ở gần cũng không nhìn rõ được vật ở xa

D. Mắt lão có khả năng quan sát hoàn toàn giống mắt cận và mắt viễn.

Câu 8. Khi nói về các cách sửa tật của mắt, phát biểu nào sau đây là sai?

Câu hỏi trắc nghiệm về mắt Thầy Hiểu NKC

A. Muốn sửa tật cận thị ta phải đeo vào mắt một thấu kính phân kì có độ tụ phù hợp

B. Muốn sửa thật viễn thị ta phải đeo vào mắt một thấu kính hội tụ có độ tụ phù hợp.

C. Muốn sửa tật lão thị ta phải đeo vào mắt một thấu kính hai tròng gồm nửa trên là kính

hội tụ, nửa dưới là kính phân kì.

D. Muốn sửa tật lão thị ta phải đeo vào mắt một thấu kính hai tròng gồm nửa trên là kính

phân kì, nửa dưới là kính hội tụ.

Câu 9. Để khắc phục tật cận thị của mắt khi quan sát các vật ở vô cực mà mắt không

điều tiết thì phải ghép thêm vào mắt một thấu kính

A. phân kì có độ tụ nhỏ

B. phân kì có độ tụ thích hợp

C. hội tụ có độ tụ nhỏ

D. hội tụ có độ tụ thích hợp

Câu 10. Để khắc phục tật viễn thị của mắt khi quan sát các vật ở vô cực mà mắt không

điều tiết thì phải ghép thêm vào mắt một thấu kính

A. phân kì có độ tụ nhỏ

B. phân kì có độ tụ thích hợp

C. hội tụ có độ tụ nhỏ

D. hội tụ có độ tụ thích hợp

Câu 11. Một người bị cận thị có khoảng cách từ thể thuỷ tinh đến điểm cực cận là

OC

c

và điểm cực viễn OC

v

. Để sửa tật của mắt người này thì người đó phải đeo sát mắt

một kính có tiêu cự là

A. f = OC

c

B. f = -OC

c

C. f = OC

v

Câu hỏi trắc nghiệm về mắt Thầy Hiểu NKC

D. f = -OC

v

Câu 12. Một người cận thị phải đeo sát mắt kính cận số 0,5. Nếu xem tivi mà không

muốn đeo kính thì người đó phải cách màn hình xa nhất một đoạn

A. 0,5m

B. 1m

C. 1,5m

D. 2m

Câu 13. Một người cận thị về già, khi đọc sách cách mắt gần nhất 25cm phải đeo sát

mắt kính số 2. Điểm cực cận của người đó nằm trên trục của mắt và cách mắt

A. 25cm

B. 50cm

C. 1m

D.2m

Câu 14. Một người cận thị đeo sát mắt một kính có độ tụ -1,5dp thì nhìn rõ được các vật

ở xa mà không phải điều tiết. Điểm cực viễn của người đó nằm trên trục của mắt và cách

mắt.

A. 50cm

B. 67cm

C. 150cm

D. 300cm

Câu 15. Một người viễn thị có điểm cực cận cách mắt 50cm. Khi đeo sát mắt một kính

có độ tụ +1dp, người này sẽ nhìn rõ được những vật gần nhất cách mắt

A. 40cm

B. 33,3cm

Câu hỏi trắc nghiệm về mắt Thầy Hiểu NKC

C. 27,5cm

C. 26,7cm

Câu 16. Một người viễn thị có điểm cực cận cách mắt gần nhất 40cm. Để nhìn rõ vật đặt

cách mắt gần nhất 25cm, người này cần đeo kính (đeo sát mắt) có độ tụ là

A. -2,5dp

B. 2,5dp

C. -1,5dp

D. 1,5dp

Câu 17. Một người cận thị có khoảng nhìn rõ từ 12,5cm đến 50cm. Khi đeo kính (đeo

sát mắt) chữa tật của mắt để khi nhìn vật ở vô cực mà mắt không điều tiết, người này

nhìn rõ được các vật đặt gần nhất cách mắt

A. 15cm

B. 16,7cm

C. 17,5cm

D. 22,5cm

Câu 18. Một người cận thị có khoảng nhìn rõ từ 12,5cm đến 50cm. Khi đeo kính (đeo

sát mắt) có độ tụ -1dp. Khoảng nhìn rõ của người này khi đeo kính là

A. từ 13,3cm đến 75cm

B. từ 14,3cm đến 75cm

C. từ 14,3cm đến 100cm

D. từ 13,3cm đến 100cm

Câu 19. Một người viễn thị nhìn rõ được vật đặt cách mắt gần nhất 40cm. Để nhìn rõ vật

đặt cách mắt gần nhất 25cm, người này cần đeo kính (kính cách mắt 1cm) có độ tụ là

A. 1,4dp

Câu hỏi trắc nghiệm về mắt Thầy Hiểu NKC

B. 1,5dp

C. 1,6dp

D. 1,7dp

Câu 20. Mắt một người cận thị có khoảng nhìn rõ từ 12cm đến 51cm. Người đó sửa tật

bằng cách đeo kính phân kì cách mắt 1cm. Biết năng suất phân li của mắt là 1’. Khoảng

cách nhỏ nhất giữa hai điểm trên vật mà mắt còn có thể phân biệt được là

A. 0,033mm

B. 0,045mm

C. 0,067mm

D. 0,041mm.
        ");
        return $service->parseQuiz($promt);
    } catch (\Throwable $e) {
        return response()->json([
            'message' => 'AI test failed',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
});

Route::post('/ocr/scan', [OcrController::class, 'scan']);

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/refresh', [AuthController::class, 'refresh']);

Route::middleware('auth:api')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/profile', [AuthController::class, 'updateProfile']);

    // Admin Only
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
    });

    // Protected Quiz Routes
    Route::post('/quizzes', [QuizController::class, 'store']);
    Route::put('/quizzes/{quiz}', [QuizController::class, 'update']);
    Route::patch('/quizzes/{quiz}', [QuizController::class, 'update']);
    Route::delete('/quizzes/{quiz}', [QuizController::class, 'destroy']);

    // Protected Question & Answer Routes
    Route::post('/quizzes/{quiz}/questions', [QuestionController::class, 'store']);
    Route::put('/questions/{question}', [QuestionController::class, 'update']);
    Route::patch('/questions/{question}', [QuestionController::class, 'update']);
    Route::delete('/questions/{question}', [QuestionController::class, 'destroy']);
    Route::post('/questions/{question}/answers', [AnswerController::class, 'store']);
    Route::put('/answers/{answer}', [AnswerController::class, 'update']);
    Route::patch('/answers/{answer}', [AnswerController::class, 'update']);
    Route::delete('/answers/{answer}', [AnswerController::class, 'destroy']);

    // Protected Quiz Attempt Routes
    Route::get('/quiz-attempts', [QuizAttemptController::class, 'index']);
    Route::get('/quiz-attempts/{quizAttempt}', [QuizAttemptController::class, 'show']);
    Route::post('/quizzes/{quiz}/attempts/start', [QuizAttemptController::class, 'start']);
    Route::post('/quizzes/{quiz}/attempts/submit', [QuizAttemptController::class, 'submit']);

    // Protected Payment Routes
    Route::post('/payments/create', [PaymentController::class, 'create']);
    Route::get('/payments/history', [PaymentController::class, 'history']);
});

// Public Webhooks & Callbacks for Payments
Route::post('/payments/webhook/momo', [PaymentController::class, 'webhookMomo']);
Route::get('/payments/callback', [PaymentController::class, 'callback']);

// Public Quiz Routes
Route::get('/quizzes', [QuizController::class, 'index']);
Route::get('/quizzes/{quiz}', [QuizController::class, 'show']);
Route::get('/quizzes/{quiz}/questions', [QuestionController::class, 'index']);
Route::get('/questions/{question}', [QuestionController::class, 'show']);
