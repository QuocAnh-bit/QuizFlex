# FE QuizFlex da tich hop lay JSON tu backend Laravel

## Cac file chinh da tich hop API JSON

- `src/services/api.js`: cau hinh axios, goi JSON tu backend.
- `vite.config.js`: proxy `/api` sang `http://127.0.0.1:8000`.
- `.env.example`: bien `VITE_API_BASE_URL=/api`.
- `src/views/user/Home.vue`: lay danh sach quiz tu `GET /api/quizzes`.
- `src/views/user/Quiz.vue`: lay chi tiet quiz tu `GET /api/quizzes/{id}`, nop bai qua `POST /api/quizzes/{id}/attempts`.
- `src/views/admin/Question.vue`: lay, xoa, loc quiz tu API.
- `src/views/admin/CreateQuiz.vue`: tao/sua quiz qua `POST /api/quizzes` va `PUT /api/quizzes/{id}`.
- `src/views/admin/OcrUpload.vue`: upload anh OCR qua `POST /api/ocr/scan`.

## Cach chay FE

```bash
cd fe_quizflex_connected_json
npm install
cp .env.example .env
npm run dev
```

Mo trinh duyet:

```txt
http://localhost:5173
```

## Dieu kien de FE lay duoc JSON

Backend Laravel phai dang chay o:

```txt
http://127.0.0.1:8000
```

Chay backend:

```bash
cd be_quizflex
php artisan serve --host=127.0.0.1 --port=8000
```

Test API:

```bash
curl http://127.0.0.1:8000/api/quizzes
```

Neu tra ve JSON la FE se lay duoc du lieu.

## Doi URL API neu can

Sua file `.env` cua frontend:

```env
VITE_API_BASE_URL=/api
```

Neu khong dung Vite proxy, co the doi thanh:

```env
VITE_API_BASE_URL=http://127.0.0.1:8000/api
```

Nhung cach proxy `/api` dang on hon khi chay local.
