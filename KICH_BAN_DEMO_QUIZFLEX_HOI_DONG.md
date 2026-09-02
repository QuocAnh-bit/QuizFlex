# 🏆 KỊCH BẢN DEMO DỰ ÁN TỐT NGHIỆP QUIZFLEX

## HỆ THỐNG QUẢN LÝ ÔN LUYỆN, TẠO ĐỀ THÔNG MINH (AI/OCR) & ĐẤU TRƯỜNG TRẮC NGHIỆM REALTIME

> **Thời lượng khuyến nghị:** 12 – 15 Phút  
> **Phong cách trình bày:** Chuyên nghiệp, gãy gọn, tập trung vào giải quyết bài toán thực tế, kiến trúc kỹ thuật sâu và khả năng thương mại hóa.

---

## 🧭 MỤC LỤC & PHÂN BỔ THỜI LƯỢNG (TIMELINE)

```
[00:00 - 01:30]  Phần 1: Giới thiệu dự án, Đặt vấn đề & Tổng quan Kiến trúc
[01:30 - 04:30]  Phần 2: Luồng Giảng viên/Creator - Tạo đề Đột phá (AI RAG + OCR bóc tách ảnh)
[04:30 - 06:30]  Phần 3: Trải nghiệm Học tập - Solo Test & Flashcard 3D Đa giác quan
[06:30 - 09:30]  Phần 4: Đấu trường Trực tiếp (Live Battle) qua WebSocket Reverb (2 Màn hình)
[09:30 - 11:30]  Phần 5: Quản lý Lớp học (Homework Room) & Đánh giá Năng lực (Analytics)
[11:30 - 13:30]  Phần 6: Mô hình Kinh doanh - Thuật toán Khấu trừ Nâng cấp Gói & Thanh toán
[13:30 - 14:30]  Phần 7: Quản trị Hệ thống (Admin Dashboard) & Kiểm duyệt Đa tầng
[14:30 - 15:00]  Phần 8: Tổng kết & Khai mạc Phiên Hỏi - Đáp (Q&A)
```

---

## 🛠 I. CHECKLIST CHUẨN BỊ KỸ THUẬT TRƯỚC GIỜ "G"

| Hạng mục                           | Chi tiết chuẩn bị                                                                                                                                                                                         | Trạng thái |
| :--------------------------------- | :-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | :--------: |
| **Backend Services**               | Đảm bảo 3 Terminal đang chạy ngầm: <br>1. `php artisan serve` (Port 8000)<br>2. `npm run dev` (Port 5173)<br>3. `php artisan reverb:start` (Realtime WebSocket)                                           |    [x]     |
| **Bố cục Màn hình (Side-by-Side)** | Mở sẵn 2 trình duyệt đặt song song:<br>- **Nửa Trái (Chrome):** Đăng nhập tài khoản `Giáo viên / Creator` (Gói Pro/Ultra).<br>- **Nửa Phải (Edge / Ẩn danh):** Đăng nhập tài khoản `Học sinh / Thí sinh`. |    [x]     |
| **Tab dự phòng**                   | 1 Tab đã đăng nhập sẵn tài khoản `Admin` (`/admin`).                                                                                                                                                      |    [x]     |
| **Tài nguyên có sẵn**              | - 1 ảnh chụp đề thi mẫu rõ nét trong máy (dùng cho demo OCR).<br>- 1 đoạn prompt ngắn chuẩn bị sẵn: _"5 câu trắc nghiệm Lịch sử CNTT"_.                                                                   |    [x]     |

---

## 🎬 II. KỊCH BẢN CHI TIẾT TỪNG PHÂN ĐOẠN

---

### 📍 PHẦN 1: MỞ ĐẦU & TỔNG QUAN HỆ THỐNG (00:00 - 01:30)

- **Giao diện hiển thị:** Trang chủ (`/`) - Giao diện hiện đại, chuẩn UI/UX với Navbar, Danh mục môn học, Khám phá đề thi, Bảng xếp hạng.
- **Thao tác:** Lướt nhẹ trang chủ, chỉ vào thanh tìm kiếm đề thi, danh mục phân cấp (Taxonomy/Curriculum).

🗣 **Lời thuyết trình:**

> \*"Kính thưa Thầy/Cô trong Hội đồng và các bạn.  
> Trong bối cảnh chuyển đổi số giáo dục, việc tự học, tạo đề kiểm tra và tổ chức thi trực tuyến vẫn đang gặp phải 3 rào cản lớn:
>
> 1. **Người dạy mất quá nhiều thời gian** để gõ lại đề từ sách vở hoặc tài liệu giấy.
> 2. **Người học nhanh chóng nhàm chán** khi làm các bài trắc nghiệm một chiều thiếu tính tương tác và phản hồi tức thì.
> 3. **Các nền tảng hiện nay thiếu sự kết nối đối kháng thời gian thực**, đồng thời chưa có giải pháp quản lý lớp học và tài chính bài bản.
>
> Để giải quyết trọn vẹn bài toán này, nhóm chúng em đã xây dựng **QuizFlex** — Nền tảng học tập, tạo đề thông minh ứng dụng AI/OCR và tổ chức đấu trường trắc nghiệm thời gian thực.
>
> Về mặt công nghệ, QuizFlex được xây dựng trên nền tảng **Vue 3 + Tailwind CSS** cho Frontend, **Laravel 11** cho Backend API, tích hợp **WebSocket Laravel Reverb** cho xử lý Realtime với độ trễ cực thấp, cùng động cơ AI và OCR để tự động hóa toàn diện quy trình tạo đề."\*

---

### 📍 PHẦN 2: ĐỘT PHÁ TẠO ĐỀ THI – AI RAG & OCR BÓC TÁCH (01:30 - 04:30)

- **Giao diện hiển thị:** Màn hình Tạo Quiz bằng AI (`/dashboard/questions/ai`) và OCR (`/dashboard/questions/ocr`).

#### 1. Tạo đề thông minh với AI (Kết hợp Phân cấp chương trình - RAG)

- **Thao tác:**
  1. Vào menu **Kho quiz của tôi** ➔ Chọn **"Tạo bằng AI"**.
  2. Chọn nhanh 4 bước theo cây kiến thức (Chương trình: _THPT_ ➔ Khối: _Lớp 12_ ➔ Môn: _Tin học_ ➔ Chủ đề: _Mạng máy tính và Internet_).
  3. Nhập số lượng: _5 câu_, mức độ: _Vận dụng_.
  4. Bấm **"Tạo câu hỏi bằng AI"** ➔ Màn hình hiển thị animation xử lý mượt mà và trả về danh sách 5 câu hỏi đầy đủ: Nội dung câu hỏi, 4 đáp án A/B/C/D, đáp án đúng và phần giải thích chi tiết.
  5. Bấm **"Tạo Quiz từ danh sách này"** để lưu vào hệ thống.

🗣 **Lời thuyết trình:**

> _"Thay vì sinh câu hỏi ngẫu nhiên như các công cụ chat thông thường, QuizFlex áp dụng quy trình 4 bước chuẩn hóa theo khung chương trình (Taxonomy). Thông tin này được truyền vào hệ thống Prompt Engine để định hình ngữ cảnh chính xác, đảm bảo câu hỏi sinh ra bám sát chuẩn kiến thức, có đầy đủ đáp án và lời giải thích khoa học chỉ trong vài giây."_

#### 2. Trích xuất đề thi từ hình ảnh (OCR + AI Review)

- **Thao tác:**
  1. Chuyển sang tab **"OCR Upload"** (`/dashboard/questions/ocr`).
  2. Kéo thả 1 ảnh chụp bài tập/đề thi có sẵn vào khung.
  3. Bấm **"Bắt đầu quét"** ➔ Hệ thống dùng OCR bóc tách văn bản tiếng Việt có dấu, sau đó AI tự động chuẩn hóa các dòng chữ thành các trường câu hỏi và đáp án độc lập.

🗣 **Lời thuyết trình:**

> _"Đối với các tài liệu giấy hoặc hình ảnh bài tập chụp từ sách vở, tính năng OCR kết hợp AI của QuizFlex sẽ tự động bóc tách ký tự tiếng Việt, bóc tách cấu trúc câu và điền sẵn vào form biên soạn, giúp thầy cô số hóa toàn bộ đề thi chỉ bằng một thao tác tải ảnh."_

---

### 📍 PHẦN 3: ÔN TẬP ĐA GIÁC QUAN & MA TRẬN NĂNG LỰC (04:30 - 06:30)

- **Giao diện hiển thị:** Trang Chi tiết Quiz (`/quizzes/:id`) ➔ Chế độ Flashcard 3D (`/quizzes/:id/flashcards`) và Solo Test (`/quizzes/:id/play`).

#### 1. Thẻ ghi nhớ Flashcard 3D tương tác

- **Thao tác:**
  1. Mở bộ đề vừa tạo ➔ Bấm **"Ôn tập Flashcard"**.
  2. Click lật thẻ với hiệu ứng 3D trực quan (Mặt trước: Câu hỏi; Mặt sau: Đáp án & Giải thích).
  3. Bấm biểu tượng Loa phát âm **Text-to-Speech (TTS)** với các tùy chọn tốc độ (1.0x, 0.7x, 0.45x).
  4. Chọn đánh giá: Câu 1 chọn _"Đã thuộc"_, Câu 2 chọn _"Chưa thuộc"_.
  5. Khi kết thúc lượt học ➔ Màn hình tổng kết hiển thị tỷ lệ ghi nhớ ➔ Bấm **"Chỉ ôn lại các thẻ chưa thuộc"** để hệ thống tự động lọc ra các câu yếu.

🗣 **Lời thuyết trình:**

> _"Hệ thống không ép buộc người học phải làm bài thi căng thẳng. Với Flashcard 3D, học sinh được tiếp cận kiến thức đa giác quan qua hình ảnh, âm thanh đọc đề tự động và cơ chế phân loại 'Đã thuộc / Chưa thuộc'. Đặc biệt, thuật toán ôn thẻ yếu giúp người học tập trung lặp lại các lỗ hổng kiến thức thay vì ôn dàn trải."_

#### 2. Làm bài Solo & Phân tích Năng lực (Analytics)

- **Thao tác:**
  1. Chuyển sang chế độ **"Làm bài kiểm tra"** (`/quizzes/:id/play`) ➔ Chọn đáp án nhanh ➔ Bấm **"Nộp bài"**.
  2. Trang **Kết quả bài làm** (`/results/:id`) hiển thị điểm số, kinh nghiệm XP nhận được, huy hiệu đạt được (Gamification).
  3. Mở trang **Phân tích năng lực** (`/analytics`) để xem biểu đồ thống kê điểm mạnh/điểm yếu theo từng môn và từng chủ đề kiến thức.

---

### 📍 PHẦN 4: ĐẤU TRƯỜNG REALTIME (LIVE BATTLE ROOM) (06:30 - 09:30) 🔥 _(ĐIỂM NHẤN CAO TRÀO NHẤT)_

- **Giao diện hiển thị:** 2 Cửa sổ trình duyệt đặt song song:
  - **Bên Trái (Chrome - Host / Giáo viên):** `/live-rooms/:id/host`
  - **Bên Phải (Edge - Player / Học sinh):** `/live-rooms/join`

#### 1. Tạo phòng & Sảnh chờ thời gian thực

- **Thao tác:**
  1. Host chọn bộ đề ➔ Bấm **"Tạo phòng thi đấu"** ➔ Màn hình Host hiển thị **Mã PIN 6 số** và **Mã QR Code**.
  2. Player ở màn hình bên phải: Nhập mã PIN và Tên hiển thị ➔ Bấm **"Tham gia"**.
  3. **Quan sát:** Tên Player xuất hiện ngay lập tức trên màn hình Host bên trái **trong tích tắc mà không cần reload trang** (nhờ Laravel Reverb).

🗣 **Lời thuyết trình:**

> _"Bây giờ, em xin trình diễn tính năng Đấu trường trực tiếp (Live Quiz Battle) vận hành hoàn toàn trên nền tảng WebSocket của Laravel Reverb. Khi thí sinh nhập mã PIN bên phải, sảnh chờ của giáo viên bên trái sẽ bắt được sự kiện tham gia và cập nhật danh sách thí sinh tức thì."_

#### 2. Tranh tài trực tiếp & Bảng xếp hạng Realtime (Live Leaderboard)

- **Thao tác:**
  1. Host bấm nút **"Bắt đầu trò chơi"**:
     - Cả 2 màn hình cùng đếm ngược 3..2..1 đồng bộ.
     - Câu hỏi xuất hiện đồng thời trên cả 2 thiết bị.
  2. Player chọn đáp án ➔ Màn hình phản hồi đúng/sai kèm hiệu ứng streak điểm.
  3. Màn hình Host cập nhật biểu đồ cột thống kê số lượng người chọn từng đáp án A/B/C/D theo thời gian thực.
  4. Host bấm **"Câu hỏi tiếp theo"** ➔ Hệ thống nhảy sang **Bảng xếp hạng trực tiếp (Live Leaderboard)** vinh danh thí sinh dẫn đầu dựa trên độ chính xác và tốc độ bấm máy.
  5. Kết thúc phòng thi ➔ Màn hình chuyển sang Podium vinh danh Top 1, Top 2, Top 3 rực rỡ.

🗣 **Lời thuyết trình:**

> _"Tất cả các hành động: phát đề, đếm ngược thời gian, khóa đáp án, thống kê biểu đồ lớp học và tính điểm tốc độ đều được xử lý đồng bộ qua WebSocket Channel với độ trễ cực thấp. Điều này biến giờ học trực tuyến thành một sàn đấu kiến thức hấp dẫn như một gameshow truyền hình."_

---

### 📍 PHẦN 5: QUẢN LÝ LỚP HỌC (HOMEWORK ROOM) & ĐÁNH GIÁ (09:30 - 11:30)

- **Giao diện hiển thị:** Phòng Bài tập về nhà (`/homework-rooms/:roomId`).

- **Thao tác:**
  1. Mở phòng bài tập đã tạo.
  2. Chỉ vào danh sách thành viên: Có cơ chế **Duyệt thành viên** hoặc thêm nhanh qua **Whitelist Email (Allowed Members)**.
  3. Xem mục **Giao bài tập (Assignment)**: Đặt hạn chót nộp bài (Deadline), giới hạn số lần làm bài.
  4. Mở **Sổ điểm (Gradebook)**: Hiển thị bảng tổng hợp điểm của cả lớp, danh sách bài nộp và form để giáo viên **nhận xét, đánh giá từng học sinh** (`Member Evaluation`).

🗣 **Lời thuyết trình:**

> _"Khác với Live Room dành cho thi đấu tức thì, Homework Room được thiết kế cho mô hình lớp học chính quy. Giáo viên có thể kiểm soát quyền vào phòng, giao đề kiểm tra về nhà có deadline tự động khóa bài, theo dõi sổ điểm tập trung và gửi nhận xét cá nhân hóa tới từng học sinh."_

---

### 📍 PHẦN 6: MÔ HÌNH THƯƠNG MẠI & THUẬT TOÁN KHẤU TRỪ NÂNG CẤP GÓI (11:30 - 13:30) 🔥 _(ĐIỂM CỘNG NGHIỆP VỤ)_

- **Giao diện hiển thị:** Trang Nâng cấp tài khoản (`/upgrade`) & Lịch sử giao dịch (`/admin/payments`).

- **Thao tác:**
  1. Đăng nhập tài khoản hiện tại đang ở gói **Plus** (còn 15 ngày sử dụng).
  2. Bấm chọn nâng cấp lên gói **Pro** (hoặc **Ultra**).
  3. **Chỉ vào bảng tính tiền:** Hệ thống gọi API `/payments/upgrade-costs` và hiển thị chi tiết:
     - Giá gốc gói Pro.
     - **Số tiền khấu trừ từ 15 ngày Plus còn lại**.
     - **Số tiền thực tế khách chỉ cần thanh toán chênh lệch**.
  4. Chọn phương thức thanh toán **VietQR (PayOS)** ➔ Hệ thống sinh mã QR động chuẩn Napas247 kèm mã đơn hàng duy nhất.
  5. _(Mô phỏng Webhook thành công)_ ➔ Trang web lập tức kích hoạt trạng thái VIP/Pro cho tài khoản mà không cần can thiệp thủ công.

🗣 **Lời thuyết trình:**

> _"Về mặt thương mại hóa, QuizFlex triển khai mô hình SaaS hoàn chỉnh với 4 gói cước (Free, Plus, Pro, Ultra). Điểm đặc biệt là chúng em đã giải quyết bài toán tài chính: **Thuật toán khấu trừ nâng cấp gói theo ngày lẻ (Proration Algorithm)**. Khi người dùng muốn lên gói cao hơn giữa chu kỳ, hệ thống sẽ tự động quy đổi giá trị thời gian còn lại của gói cũ để trừ thẳng vào đơn hàng mới, đảm bảo tính công bằng và chuyên nghiệp chuẩn quốc tế."_

---

### 📍 PHẦN 7: ADMIN DASHBOARD & KIỂM DUYỆT TOÀN DIỆN (13:30 - 14:30)

- **Giao diện hiển thị:** Admin Workspace (`/admin`).

- **Thao tác:**
  1. **Dashboard Tổng quan (`/admin`)**:
     - Biểu đồ doanh thu theo Ngày / Tháng / Năm.
     - Thống kê tỷ lệ giao dịch thành công của cổng thanh toán.
     - Phân tích tỷ lệ đề thi tạo bằng AI so với tạo thủ công.
     - Bảng xếp hạng Top Creator và Top Paying Users.
  2. **Kiểm duyệt Đa tầng**:
     - **Duyệt câu hỏi đóng góp (`/admin/question-bank`)**: Duyệt câu hỏi từ người dùng gửi lên ngân hàng câu hỏi chung hệ thống.
     - **Duyệt Quiz công khai (`/admin/quizzes`)**: Kiểm duyệt nội dung trước khi xuất hiện trên trang chủ.
     - **Quản lý Báo cáo vi phạm (`/admin/reports`)** & **Mở khóa tài khoản kháng cáo (`/admin/unlock-requests`)**.

🗣 **Lời thuyết trình:**

> _"Cuối cùng, hệ thống Admin của QuizFlex cung cấp một bảng điều khiển trung tâm: vừa theo dõi sức khỏe tài chính và hoạt động người dùng theo thời gian thực, vừa duy trì chất lượng cộng đồng thông qua cơ chế kiểm duyệt 2 tầng (Duyệt câu hỏi ngân hàng và Duyệt Quiz công khai), cùng hệ thống xử lý vé báo cáo vi phạm minh bạch."_

---

### 📍 PHẦN 8: TỔNG KẾT & KẾT THÚC (14:30 - 15:00)

🗣 **Lời thuyết trình:**

> \*"Kính thưa Hội đồng, tổng kết lại dự án QuizFlex:
>
> 1. **Về tính năng:** Đáp ứng toàn diện vòng đời học tập: từ Soạn đề tự động (AI/OCR) ➔ Ôn tập đa giác quan (Flashcard 3D) ➔ Kiểm tra đánh giá (Homework & Live Battle).
> 2. **Về công nghệ:** Ứng dụng thành công Laravel Reverb WebSocket cho độ trễ thời gian thực tối ưu, kiến trúc JWT an toàn, bảo vệ dữ liệu với cơ chế Snapshot câu hỏi chống sửa đề khi đang thi.
> 3. **Về tính ứng dụng:** Mô hình thanh toán tự động, thuật toán khấu trừ chi phí minh bạch sẵn sàng đưa vào thương mại hóa thực tế.
>
> Em xin chân thành cảm ơn Quý Thầy/Cô đã lắng nghe và kính mời Thầy/Cô đưa ra những nhận xét, câu hỏi phản biện ạ!"\*

---

## 🧠 III. BỘ CÂU HỎI PHẢN BIỆN "ĐẮT GIÁ" & CÂU TRẢ LỜI MẪU

### ❓ 1. Cơ chế Realtime của hệ thống chịu tải thế nào? Nếu người chơi bị rớt mạng hoặc F5 lại thì sao?

- **Trả lời:**
  > _"Dạ thưa Thầy/Cô, hệ thống sử dụng **Laravel Reverb** chạy trên nền Event Loop phi đồng bộ nên tối ưu hóa bộ nhớ và kết nối đồng thời rất tốt.  
  > Đối với trường hợp thí sinh rớt mạng hoặc F5: Trạng thái phòng thi (`live_room`), câu hỏi hiện tại và điểm số được đồng bộ trong Database. Khi thí sinh tải lại trang, router guard và component sẽ kiểm tra Token và `liveRoomId`, tự động fetch lại `current-question` và kết nối lại đúng WebSocket Channel mà không bị mất tiến trình thi."_

### ❓ 2. Khi giáo viên sửa hoặc xóa một câu hỏi trong ngân hàng đề, các bài thi đã làm hoặc phòng thi đang mở có bị sai lệch không?

- **Trả lời:**
  > _"Dạ không ạ. Nhóm đã thiết kế kiến trúc **Bank Snapshot Isolation**. Khi một Quiz được tạo hoặc một phiên làm bài (`QuizAttempt`) được bắt đầu, hệ thống sẽ snapshot (cố định) toàn bộ nội dung câu hỏi và đáp án tại thời điểm đó vào bảng snapshot. Mọi thao tác chỉnh sửa, xóa mềm ở ngân hàng gốc sau đó hoàn toàn không ảnh hưởng đến dữ liệu lịch sử bài làm của học sinh."_

### ❓ 3. Thuật toán khấu trừ nâng cấp gói (Proration) được tính toán như thế nào?

- **Trả lời:**
  > _"Dạ, công thức được xử lý ở backend:  
  > `Số tiền hoàn lại = (Giá gói hiện tại / 30 ngày) _ Số ngày còn lại chưa sử dụng`.  
`Số tiền cần thanh toán = Max(0, Giá gói mới - Số tiền hoàn lại)`.  
  > Nhờ vậy, người dùng không phải trả trùng lặp chi phí và có thể thoải mái nâng cấp gói bất kỳ lúc nào."\*

### ❓ 4. Làm thế nào để đảm bảo Webhook thanh toán từ PayOS / MoMo không bị lỗi cộng quyền VIP 2 lần khi mạng lag (Idempotency)?

- **Trả lời:**
  > _"Dạ, Backend sử dụng cơ chế **Database Transaction** và kiểm tra trạng thái đơn hàng (`order_status`). Khi Webhook gửi đến, hệ thống kiểm tra nếu đơn hàng đã chuyển sang trạng thái `PAID`, hệ thống sẽ trả về mã HTTP 200 ngay lập tức mà không thực hiện cộng thêm ngày VIP, đảm bảo tính bất biến (Idempotency) tuyệt đối."_

---

## 🎯 IV. LỜI KHUYÊN KHI TRÌNH BÀY (PRO TIPS)

1. **Chuẩn bị kịch bản phân vai:** Nếu nhóm có 2 người, 1 bạn thuyết trình nhìn thẳng Hội đồng diễn giải bài toán, 1 bạn chuyên thao tác máy tính bấm chuột đúng từng nhịp lời nói.
2. **Không để khoảng lặng (Dead Air):** Trong lúc bấm tạo đề bằng AI (mất 2-3s), hãy chủ động thuyết minh: _"Hệ thống đang gửi dữ liệu qua AI Engine để chuẩn hóa cấu trúc JSON..."_.
3. **Phong thái:** Tự tin, dứt khoát, dùng các thuật ngữ chuyên môn chính xác (_WebSocket, Proration, Snapshot Isolation, RAG Taxonomy, JWT Auto-refresh_).
