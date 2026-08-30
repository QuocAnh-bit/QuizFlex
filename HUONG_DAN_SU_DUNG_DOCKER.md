# 📘 Hướng Dẫn Cài Đặt & Sử Dụng Docker Cho Dự Án QuizFlex (Dành Cho Windows)

Hướng dẫn này giúp bạn cài đặt **Docker Desktop** trên Windows và khởi chạy toàn bộ hệ thống QuizFlex (Laravel, Vue 3, MySQL, Qdrant AI, WebSocket Reverb, Queue Worker) bằng **chỉ 1 lệnh đơn giản**.

---

## 🚀 BƯỚC 1: TẢI & CÀI ĐẶT DOCKER DESKTOP TRÊN WINDOWS

### 1. Tải phần mềm:
- Truy cập trang chủ Docker: [https://www.docker.com/products/docker-desktop/](https://www.docker.com/products/docker-desktop/)
- Bấm nút **"Download for Windows"** để tải file cài đặt `Docker Desktop Installer.exe`.

### 2. Cài đặt:
- Mở file `.exe` vừa tải về.
- Đánh dấu chọn hai ô:
  - `[x] Use WSL 2 instead of Hyper-V (recommended)` *(Tính năng giúp Docker chạy siêu nhanh trên Windows)*.
  - `[x] Add shortcut to desktop`.
- Bấm **Ok** và chờ chương trình cài đặt hoàn tất (khoảng 2-3 phút).
- Khi kết thúc, bấm **Close and restart** để khởi động lại máy tính (nếu được yêu cầu).

---

## 🟢 BƯỚC 2: KHỞI ĐỘNG DOCKER DESKTOP

1. Sau khi máy khởi động lại, mở biểu tượng **Docker Desktop** ngoài màn hình.
2. Khi giao diện màn hình Docker mở ra và dưới góc trái hiển thị màu xanh lá: **`Engine running`** -> Docker đã sẵn sàng hoạt động!

---

## ⚡ BƯỚC 3: KHỞI CHẠY DỰ ÁN QUIZFLEX BẰNG DOCKER

Mở Terminal (VS Code Terminal hoặc PowerShell) tại thư mục gốc `C:\laragon\www\QuizFlex` và chạy các lệnh sau:

### 1. Khởi chạy toàn bộ hệ thống ngầm:
```bash
docker compose up -d
```
*(Lần đầu tiên chạy, Docker sẽ tự tải các Image như MySQL, Qdrant, Nginx và build Laravel/Vue. Quá trình này mất khoảng 2-4 phút)*.

### 2. Truy cập ứng dụng:
Sau khi chạy xong:
* 🌐 **Website QuizFlex (Frontend):** Truy cập [http://localhost](http://localhost)
* ⚙️ **Backend API (Laravel):** [http://localhost:8000/api/test](http://localhost:8000/api/test)
* 🧠 **Qdrant Vector DB (RAG AI):** [http://localhost:6333/dashboard](http://localhost:6333/dashboard)
* 📡 **WebSocket Server (Reverb):** Port `8090`

---

## 📊 BƯỚC 4: TẠO DỮ LIỆU MẪU (SEEDER) VÀ KHỞI TẠO CSDL

Để nạp toàn bộ bộ câu hỏi mẫu và tài khoản Admin/User vào CSDL Docker, bạn chạy 2 lệnh sau:

```bash
# 1. Chạy Migration tạo bảng
docker exec -it quizflex_backend php artisan migrate --force

# 2. Nạp dữ liệu mẫu
docker exec -it quizflex_backend php artisan db:seed
```

---

## 📋 CHEAT SHEET - CÁC LỆNH DOCKER THƯỜNG DÙNG

| Thao tác | Lệnh thực hiện |
| :--- | :--- |
| **Bật tất cả hệ thống** | `docker compose up -d` |
| **Tắt tất cả hệ thống** | `docker compose down` |
| **Xem trạng thái các container** | `docker compose ps` |
| **Xem log lỗi real-time** | `docker compose logs -f` |
| **Xem log dịch vụ cụ thể** | `docker logs quizflex_backend` |
| **Re-build lại khi sửa Dockerfile** | `docker compose build --no-cache` |

---

🎉 **Chúc mừng! Giờ đây hệ thống QuizFlex của bạn đã hoàn toàn chuẩn hóa với Docker!**
