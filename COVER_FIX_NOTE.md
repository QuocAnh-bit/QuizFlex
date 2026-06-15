# Quiz cover fix note

Bản này sửa lỗi ảnh bìa không hiển thị/lưu đúng khi frontend chạy ở `localhost:5173` và backend Laravel chạy ở `127.0.0.1:8000`.

## Việc đã sửa

- Backend lưu ảnh upload bằng public disk và trả về URL đầy đủ cho `/storage/quiz-covers/...`.
- Backend tự chuyển các cover cũ dạng `/storage/...` thành URL đầy đủ trong response API.
- Frontend Vite proxy thêm `/storage` sang Laravel để ảnh public disk hiển thị trong môi trường dev.
- Migration đảm bảo bảng `quizzes` có đủ các cột: `cover`, `icon`, `badge`, `tag`, `room_code`.

## Cần chạy sau khi kéo source

```bash
cd be_quizflex
php artisan migrate
php artisan storage:link
php artisan serve
```

Frontend:

```bash
cd fe_quizflex
npm install
npm run dev
```

Nếu ảnh vẫn không hiện, kiểm tra trong DB cột `cover` của quiz phải có giá trị dạng:

```text
http://127.0.0.1:8000/storage/quiz-covers/ten-file.jpg
```

hoặc ít nhất:

```text
/storage/quiz-covers/ten-file.jpg
```
