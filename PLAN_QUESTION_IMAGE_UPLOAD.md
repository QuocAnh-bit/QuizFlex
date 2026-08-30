# KẾ HOẠCH TRIỂN KHAI TÍNH NĂNG ĐÍNH KÈM HÌNH ẢNH CHO CÂU HỎI (QUIZFLEX)

> **Mục tiêu:** Cho phép người dùng và giáo viên tải lên hình ảnh minh họa khi **Tạo câu hỏi** hoặc **Chỉnh sửa câu hỏi** (phục vụ các bài toán hình học, đồ thị hàm số, sơ đồ mạch điện, sơ đồ sinh học, tranh từ vựng tiếng Anh,...).

---

## I. ĐÁNH GIÁ HIỆN TRẠNG HỆ THỐNG

1. **Cơ sở dữ liệu (Database):**
   * Bảng `questions` **đã có sẵn** cột `image_url` (kiểu dữ liệu `TEXT / LONGTEXT NULL`).
   * Việc thêm tính năng này là an toàn $100\%$, không làm ảnh hưởng đến dữ liệu câu hỏi cũ và không yêu cầu chạy migration mới.
2. **API & Controllers:**
   * Các Model & Resource (`Question`, `QuestionResource`) đã hỗ trợ map trường `image_url`.
   * Cần bổ sung thêm Endpoint xử lý upload file ảnh vào Storage của Laravel (nếu chưa có endpoint chung).
3. **Frontend (Vue 3):**
   * Một số component đã hỗ trợ hiển thị ảnh sẵn nếu `question.image_url` tồn tại (VD: `AdminQuestionDetail.vue`, `QuestionPickerModal.vue`).
   * Cần thêm khối giao diện Upload / Drag-and-Drop vào:
     * `fe_quizflex/src/views/user/CreateQuestionView.vue`
     * `fe_quizflex/src/views/user/EditQuestionView.vue`
     * `fe_quizflex/src/views/admin/AdminQuestionManager.vue` (nếu có modal tạo/sửa)

---

## II. KẾ HOẠCH TRIỂN KHAI CHI TIẾT

### Bước 1: Xử lý Backend (Laravel Storage & Upload API)

1. **Đảm bảo liên kết Storage:**
   * Chạy lệnh tạo symlink nếu chưa có:
     ```bash
     php artisan storage:link
     ```
2. **Endpoint Upload ảnh:**
   * Tạo API `POST /api/upload/image` hoặc tích hợp trực tiếp vào `QuestionController`:
     * **Validation:** Tối đa `3MB`, định dạng `jpeg, png, jpg, gif, webp`.
     * **Xử lý:** Lưu vào thư mục `storage/app/public/questions/`.
     * **Trả về:** URL công khai (VD: `/storage/questions/question_123456789.webp`).
3. **Bảo mật & Tối ưu:**
   * Tự động xóa file ảnh cũ khi người dùng cập nhật câu hỏi và đổi sang ảnh mới hoặc xóa ảnh.

---

### Bước 2: Xây dựng Giao diện Upload phía Frontend

1. **Vị trí đặt trong Form Tạo/Sửa câu hỏi:**
   * Đặt ngay dưới ô nhập nội dung câu hỏi `textarea` (Mục 1. Nội dung câu hỏi).
2. **Tính năng của ô Upload:**
   * **Kéo thả / Bấm chọn file:** Hỗ trợ kéo thả trực tiếp hoặc click để chọn ảnh từ máy tính.
   * **Xem trước (Image Preview):** Hiển thị ảnh vừa chọn rõ nét, có nút **Xóa ảnh** (`Trash2` icon) nếu chọn nhầm.
   * **Nhập bằng URL:** Hỗ trợ cả 2 chế độ: *Tải file từ máy tính* HOẶC *Dán đường link ảnh có sẵn (URL)*.
   * **Trạng thái Uploading:** Thanh tiến trình nhỏ hoặc icon spinner khi đang tải ảnh lên server.

---

### Bước 3: Đồng bộ hiển thị trên tất cả các màn hình làm bài

Khi câu hỏi có kèm ảnh (`image_url`), cần đảm bảo ảnh hiển thị đẹp mắt, tự động co giãn theo màn hình (Responsive) tại:
1. **Làm bài thi / Trắc nghiệm ([Quiz.vue](fe_quizflex/src/views/user/Quiz.vue))**
2. **Đấu trường trực tiếp ([LiveRoomPlay.vue](fe_quizflex/src/views/user/LiveRoomPlay.vue))**
3. **Làm bài tập về nhà ([HomeworkAssignmentTake.vue](fe_quizflex/src/views/user/HomeworkAssignmentTake.vue))**
4. **Học Flashcard ([Flashcards.vue](fe_quizflex/src/views/user/Flashcards.vue))**

> **CSS chuẩn cho hiển thị ảnh câu hỏi:**
> ```html
> <div v-if="question.image_url" class="my-3 flex justify-center">
>   <img
>     :src="question.image_url"
>     alt="Minh họa câu hỏi"
>     class="max-h-64 sm:max-h-72 w-auto object-contain rounded-xl border border-slate-200 shadow-sm"
>     loading="lazy"
>   />
> </div>
> ```

---

## III. CHECKLIST KIỂM THỬ KHI THỰC HIỆN

- [ ] Tạo thử câu hỏi **Có ảnh** $\rightarrow$ Lưu thành công và hiển thị ảnh đúng trong danh sách câu hỏi.
- [ ] Tạo thử câu hỏi **Không có ảnh** $\rightarrow$ Lưu bình thường, không bị lỗi `null`.
- [ ] Chỉnh sửa câu hỏi: Đổi ảnh khác hoặc Xóa bỏ ảnh hiện tại $\rightarrow$ Cập nhật chính xác.
- [ ] Kiểm tra hiển thị trên điện thoại (Mobile Responsive) $\rightarrow$ Ảnh không bị vỡ khung hay tràn viền.
- [ ] Kiểm tra dung lượng file: Báo lỗi thân thiện nếu người dùng tải file quá 3MB hoặc sai định dạng.

---
*Tài liệu này được tạo tự động để lưu trữ kế hoạch phát triển cho QuizFlex.*

Dưới đây là đánh giá chuyên môn chi tiết về việc cho phép thêm hình ảnh vào câu hỏi và phân tích các rủi ro kỹ thuật (nếu có) đối với toàn bộ hệ thống QuizFlex:

1. Có NÊN cho thêm ảnh vào khi tạo / chỉnh sửa câu hỏi không?
👉 RẤT NÊN (Đây là tính năng gần như bắt buộc đối với một nền tảng EdTech/Quiz chuyên nghiệp):

Mở rộng phạm vi môn học:
Các môn tự nhiên (Toán, Lý, Hóa, Sinh, Địa lý): Rất nhiều câu hỏi yêu cầu đồ thị hàm số, hình học không gian, sơ đồ mạch điện, cấu trúc phân tử, biểu đồ hoặc bản đồ. Nếu không có ảnh, người dùng không thể soạn được các câu hỏi này.
Môn Ngoại ngữ (Tiếng Anh, Tiếng Nhật): Cần hình ảnh cho dạng bài "Nhìn tranh chọn đáp án đúng".
Tăng tính hấp dẫn & trực quan: Đề thi có hình ảnh minh họa sẽ sinh động hơn rất nhiều, tránh gây nhàm chán khi chỉ toàn văn bản.
2. Nếu thay đổi có xảy ra vấn đề gì lớn ảnh hưởng đến website không?
👉 Tin rất mừng: Cơ sở dữ liệu và Backend của QuizFlex ĐÃ ĐƯỢC CHUẨN BỊ SẴN trường image_url từ trước!

Trong Database hiện tại:

Bảng questions đã có sẵn cột image_url (kiểu LONGTEXT / TEXT nullable).
Một số view như 

AdminQuestionDetail.vue
 và 

QuestionPickerModal.vue
 đã viết sẵn v-if="question.image_url" để render ảnh nếu có.
Do đó, việc bổ sung tính năng này hoàn toàn KHÔNG làm hỏng dữ liệu cũ, KHÔNG gây lỗi hệ thống vì trường ảnh là tùy chọn (nullable - có cũng được, không có cũng không sao).

3. Những điểm kỹ thuật cần lưu ý để website luôn nhanh và mượt:
Nếu bạn quyết định bổ sung tính năng tải ảnh, chỉ cần tuân thủ 3 nguyên tắc sau:

Phương thức lưu trữ (Storage):

Nên: Upload file ảnh lên thư mục storage/app/public/questions của Laravel (hoặc Cloudinary/S3) và chỉ lưu đường link URL ngắn (VD: /storage/questions/abc.png) vào Database.
Không nên: Lưu toàn bộ chuỗi mã hóa Base64 khổng lồ trực tiếp vào Database vì sẽ làm Database phình to và API tải câu hỏi bị chậm.
Giới hạn kích thước file (File Validation):

Giới hạn tối đa $2\text{MB} - 3\text{MB}$/ảnh, định dạng .jpg, .png, .webp.
Thêm nút "Xóa ảnh" khi người dùng chọn nhầm hoặc muốn đổi ảnh khác.
Giao diện hiển thị (Responsive Image):

Khi render ảnh ở các màn hình làm bài (Làm Quiz, Live Room, Bài tập về nhà, Flashcard), chỉ cần dùng class CSS gọn gàng: max-h-64 sm:max-h-72 w-auto object-contain rounded-xl để ảnh luôn vừa vặn, không bị vỡ giao diện trên điện thoại.
🎯 Kết luận & Đề xuất:
Tính năng này rất an toàn và mang lại giá trị sử dụng rất lớn.
Nếu bạn muốn, tôi có thể tích hợp ngay ô chọn ảnh (Upload/Drag & Drop ảnh kèm bản xem trước Preview trực quan) vào giao diện Tạo câu hỏi (

CreateQuestionView.vue
) và Chỉnh sửa câu hỏi (

EditQuestionView.vue
).
