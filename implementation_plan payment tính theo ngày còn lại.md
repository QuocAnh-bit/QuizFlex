# Tính năng Nâng cấp Gói dịch vụ (Subscription Upgrade) Bù trừ Chênh lệch

Tài liệu này trình bày kế hoạch triển khai nâng cấp tài khoản VIP bằng cách bù trừ chênh lệch giá giữa gói hiện tại và gói mới theo ý kiến đóng góp của giáo viên hướng dẫn.

## Đánh giá Ý kiến Giáo viên & Đề xuất Giải pháp

Ý kiến của giáo viên về việc **cho phép nâng cấp gói dịch vụ bằng cách lấy giá gói mới trừ đi giá gói hiện tại** là **RẤT HỢP LÝ** và là tiêu chuẩn của các hệ thống SaaS chuyên nghiệp. Tuy nhiên, nếu chỉ trừ thẳng giá mà không tính tới thời gian sử dụng sẽ phát sinh lỗi logic thương mại (loophole).

Dưới đây là 2 phương án triển khai cụ thể:

### Phương án 1: Trừ thẳng giá gốc (Đơn giản)
* **Cách tính:** Số tiền = `Giá gói mới` - `Giá gói hiện tại`. 
* **Thời hạn:** Reset lại thời gian sử dụng về 30 ngày mới của gói mới kể từ lúc thanh toán thành công.
* **Đánh giá:**
  * **Ưu điểm:** Cực kỳ dễ hiểu đối với người dùng và dễ lập trình.
  * **Nhược điểm (Kẽ hở trục lợi):** Người dùng có thể mua gói Plus (50k), dùng hết 29 ngày (chỉ còn 1 ngày hết hạn), sau đó bấm nâng cấp lên Pro. Họ chỉ cần trả thêm 70k (120k - 50k) để có 30 ngày gói Pro mới. Như vậy, họ được dùng 29 ngày Plus + 30 ngày Pro chỉ với 120k (đúng ra Pro 30 ngày đã là 120k).

### Phương án 2: Tính toán theo giá trị sử dụng còn lại (Prorated Upgrade - Khuyên dùng)
* **Cách tính:**
  1. Số ngày còn lại của gói cũ: `$remainingDays = ngày hết hạn - ngày hiện tại`.
  2. Giá trị còn dư của gói cũ: `$unusedValue = (Giá gói cũ / 30 ngày) * $remainingDays`.
  3. Số tiền cần thanh toán thực tế: `$upgradeCost = Giá gói mới - $unusedValue` (có thể làm tròn đến hàng nghìn).
* **Thời hạn:** Reset hạn sử dụng của gói mới thành 30 ngày kể từ ngày nâng cấp thành công.
* **Đánh giá:**
  * **Ưu điểm:** Tuyệt đối công bằng cho cả người dùng và hệ thống. Người dùng dùng bao nhiêu ngày thì trả tiền bấy nhiêu, không thể lách luật trục lợi. Rất chuyên nghiệp và ghi điểm cực kỳ cao với giáo viên chấm điểm.
  * **Nhược điểm:** Code logic tính ngày tháng phức tạp hơn một chút.

---

## User Review Required

> [!IMPORTANT]
> **Vui lòng phản hồi xem bạn muốn triển khai theo phương án nào:**
> 1. **Phương án 1 (Trừ thẳng giá trị gốc)**: Đơn giản, dễ giải thích trực quan nhưng có kẽ hở.
> 2. **Phương án 2 (Prorated - Tính theo ngày còn lại)**: Chuyên nghiệp, tối ưu, ghi điểm cao với thầy giáo (Được khuyên dùng).

> [!WARNING]
> Quy tắc nâng cấp chỉ áp dụng cho việc **nâng cấp lên gói cao hơn** (Downgrade/Hạ cấp gói từ Pro xuống Plus sẽ không được tính giảm trừ và nút chọn gói thấp hơn trên Frontend sẽ bị vô hiệu hóa hoặc đổi thành "Không thể hạ cấp").

---

## Open Questions

> [!NOTE]
> * **Về hạn mức AI (AI Quota remaining):** Khi nâng cấp từ Plus lên Pro, số lượng lượt dùng AI cũ có được cộng dồn với lượt của gói mới không? (Hiện tại hệ thống đang cộng dồn: `$user->ai_quota_remaining += $quota`).

---

## Proposed Changes

Chúng ta sẽ chỉnh sửa các file thuộc cấu trúc Backend (Laravel) và Frontend (Vue.js).

### Backend (Laravel)

#### [MODIFY] [PaymentService.php](file:///c:/laragon/www/QuizFlex/be_quizflex/app/Services/Payment/PaymentService.php)
* Cập nhật hàm `getPlans()` để lưu trữ thêm thông tin giá trị gói (để thuận tiện so sánh gói nào cao hơn gói nào).
* Thêm hàm tính toán số tiền nâng cấp thực tế dựa vào gói hiện tại của User, gói muốn mua và số ngày VIP còn lại (áp dụng công thức Prorated hoặc Trừ thẳng tùy bạn chọn).

#### [MODIFY] [PaymentController.php](file:///c:/laragon/www/QuizFlex/be_quizflex/app/Http/Controllers/PaymentController.php)
* Trong hàm `create()`, kiểm tra xem User hiện tại có đang sử dụng gói VIP nào còn hạn hay không.
* Nếu có và họ chọn gói cao hơn, gọi `PaymentService` để tính toán số tiền đã khấu trừ thay vì lấy giá gốc của gói mới.
* Cập nhật số tiền cần thanh toán thực tế khi tạo giao dịch ở MoMo/PayOS.

---

### Frontend (Vue.js)

#### [MODIFY] [Upgrade.vue](file:///c:/laragon/www/QuizFlex/fe_quizflex/src/views/user/Upgrade.vue)
* Cập nhật giao diện trang nâng cấp:
  * Nếu người dùng chưa đăng nhập hoặc đang dùng gói Free: Hiển thị giá gốc của các gói.
  * Nếu người dùng đang dùng gói VIP (ví dụ: Plus):
    * Gói Plus: Hiển thị trạng thái "Đang sử dụng" (không thể mua lại hoặc hạ cấp).
    * Gói Pro / Ultra: Hiển thị giá đã được khấu trừ chênh lệch (kèm thông tin giải thích trực quan: *"Giá nâng cấp từ gói Plus: X.000đ"*).

---

## Verification Plan

### Manual Verification
1. Đăng nhập tài khoản User thường, tiến hành mua gói **Plus** (50k) và thanh toán thành công.
2. Kiểm tra tài khoản đã lên gói **Plus** và hạn dùng là 30 ngày sau.
3. Quay lại trang Nâng cấp, kiểm tra xem gói **Pro** (120k) và **Ultra** (250k) có hiển thị giá đã khấu trừ hay không. Gói **Plus** có hiển thị trạng thái "Đang sử dụng" và không cho phép click nâng cấp không.
4. Chọn nâng cấp lên gói **Pro**, kiểm tra số tiền gửi sang MoMo/PayOS Sandbox có đúng là số tiền đã khấu trừ hay không.
5. Thanh toán thử nghiệm thành công gói **Pro** và xác nhận tài khoản chuyển đổi vai trò thành **PRO** với hạn dùng 30 ngày mới.
