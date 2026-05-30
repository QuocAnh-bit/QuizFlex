# QuizFlex AI Fix Notes

## Lỗi vừa sửa

Màn hình báo:

```txt
Không tạo được AI quiz: Missing Authentication header
```

Nguyên nhân nằm ở backend khi gọi AI provider. Source cũ dùng thư viện OpenAI client với custom base URI, trong một số môi trường request gửi sang OpenRouter không mang đúng `Authorization: Bearer ...`, nên provider trả về `Missing Authentication header`.

Bản này đã đổi `app/Services/AI/AIService.php` sang gọi trực tiếp bằng Guzzle để kiểm soát header rõ ràng:

```http
Authorization: Bearer <OPENROUTER_API_KEY hoặc DEEPSEEK_API_KEY>
Content-Type: application/json
Accept: application/json
HTTP-Referer: <APP_URL>
X-Title: QuizFlex
```

## Cấu hình backend cần có trong `be_quizflex/.env`

Dùng OpenRouter:

```env
OPENROUTER_API_KEY=your_openrouter_key
OPENROUTER_BASE_URI=https://openrouter.ai/api/v1
OPENROUTER_MODEL=deepseek/deepseek-chat-v3-0324
QUEUE_CONNECTION=sync
FRONTEND_URL=http://localhost:5173
```

Hoặc dùng DeepSeek trực tiếp:

```env
DEEPSEEK_API_KEY=your_deepseek_key
DEEPSEEK_BASE_URI=https://api.deepseek.com
DEEPSEEK_MODEL=deepseek-chat
QUEUE_CONNECTION=sync
FRONTEND_URL=http://localhost:5173
```

Không cần cấu hình cả hai. Nếu có `DEEPSEEK_API_KEY`, hệ thống ưu tiên DeepSeek trực tiếp. Nếu không có `DEEPSEEK_API_KEY` nhưng có `OPENROUTER_API_KEY`, hệ thống dùng OpenRouter.

## Lệnh bắt buộc sau khi sửa `.env`

```bash
cd be_quizflex
php artisan optimize:clear
php artisan config:clear
php artisan serve
```

Nếu không clear cache, Laravel có thể vẫn dùng config cũ. Vâng, cache là thứ sinh ra để tăng tốc và cũng để hành hạ con người đúng lúc cần demo.

## Chạy frontend

```bash
cd fe_quizflex
npm install
npm run dev
```

Frontend đang trỏ API về:

```env
VITE_API_BASE_URL=http://127.0.0.1:8000/api
```

## Kiểm tra đã sửa

Đã kiểm tra:

```bash
php -l app/Services/AI/AIService.php
npm run build
```

Backend artisan trong môi trường kiểm tra này chưa chạy được vì PHP thiếu extension `DOMDocument` (`php-xml` hoặc `ext-dom`). Đây là lỗi môi trường PHP, không phải lỗi source AI.
