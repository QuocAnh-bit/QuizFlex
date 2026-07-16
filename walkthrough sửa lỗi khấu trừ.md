# Tài liệu Hướng dẫn & Bàn giao Tính năng Nâng cấp Gói dịch vụ (Prorated Upgrade)

Tôi đã hoàn thành triển khai **Phương án 2 (Prorated Upgrade - Tính giá nâng cấp theo số ngày thực tế còn lại)** cho hệ thống. Dưới đây là các phần code đã được sửa đổi và hướng dẫn bạn cách kiểm thử chi tiết.

---

## Các phần đã được sửa đổi

### 1. Phía Backend (Laravel)

*   **[routes/api.php](file:///c:/laragon/www/QuizFlex/be_quizflex/routes/api.php#L64)**:
    *   Đăng ký thêm route API `GET /payments/upgrade-costs` trong middleware `auth:api` để hệ thống lấy giá nâng cấp động cho người dùng.
*   **[PaymentService.php](file:///c:/laragon/www/QuizFlex/be_quizflex/app/Services/Payment/PaymentService.php#L33-L121)**:
    *   Thêm trường `tier` (Plus: 1, Pro: 2, Ultra: 3) vào hàm `getPlans()` để so sánh cấp bậc.
    *   Viết hàm `getPlanIdFromRole()` để ánh xạ nhanh từ role CSDL sang ID gói cước.
    *   Viết hàm **`calculateUpgradeCost()`** để tính giá trị còn dư của gói cũ theo ngày (`unused_value = (giá_gói_cũ / 30) * ngày_còn_lại`), từ đó trừ bớt vào giá gói mới và làm tròn đến hàng nghìn đồng. Đồng thời cấm hạ cấp gói (downgrade).
    *   **Chặn khấu trừ đối với gói dùng thử (Free Trial)**: Hàm `calculateUpgradeCost()` được bổ sung kiểm tra giao dịch gần nhất của người dùng. Nếu là gói dùng thử (giao dịch có `provider === 'trial'` hoặc số tiền thanh toán thực tế là `0đ`), hệ thống sẽ đặt số tiền khấu trừ bằng **0đ**. Người dùng bắt buộc phải trả đủ giá gốc gói mới để nâng cấp và nút bấm nâng cấp sẽ hoạt động bình thường mà không có giảm trừ.
    *   Cập nhật `createMomoPayment()` để lấy số tiền thực tế sau khấu trừ khi thanh toán qua MoMo, đồng thời lưu trữ trước gói cước mục tiêu vào trường JSON `provider_response` (`['target_plan_id' => $planId]`).
    *   **Sửa lỗi Cộng dồn ngày sử dụng khi Nâng cấp (Fix Exploit)**: Trước đây khi nâng cấp thành công, hệ thống luôn cộng dồn ngày sử dụng của gói cũ vào gói mới (`$currentExpiry->addDays($days)`). Điều này không hợp lý vì người dùng đã được **khấu trừ giảm giá gói mới** (đã "đổi" ngày cũ thành tiền giảm giá). 
        Tôi đã sửa lại:
        *   Nếu là **Nâng cấp (Upgrade)** lên gói cao hơn: Reset hạn dùng về **30 ngày của gói mới** kể từ thời điểm nâng cấp thành công (không cộng dồn ngày của gói cũ đã quy đổi thành tiền).
        *   Nếu là **Gia hạn (Renew)** cùng cấp hoặc **Mua mới**: Vẫn giữ nguyên cơ chế cộng dồn hạn dùng cũ bình thường.
    *   **Sửa lỗi Logic đối chiếu Gói VIP thành công (`processSuccessPayment`)**: Trước đây hệ thống tự tìm gói dựa trên **số tiền thanh toán**. Khi nâng cấp khấu trừ, số tiền thanh toán thực tế (ví dụ: 70k) không khớp với giá trị mặc định của bất kỳ gói nào (50k, 120k, 250k) nên bị fallback về Plus. Tôi đã sửa lại để hệ thống **đọc trực tiếp `target_plan_id` từ CSDL** để nâng cấp chính xác gói Pro hoặc Ultra mà người dùng đăng ký.
*   **[PaymentController.php](file:///c:/laragon/www/QuizFlex/be_quizflex/app/Http/Controllers/PaymentController.php#L57-L79)**:
    *   Cập nhật luồng tạo thanh toán PayOS trong hàm `create()` để tự động gọi logic khấu trừ nâng cấp thay vì lấy giá gốc, và lưu gói cước mục tiêu vào `provider_response`.
    *   Viết thêm hàm **`getUpgradeCosts()`** (line 280) để trả về danh sách các gói kèm thông tin giá nâng cấp động của riêng user đang đăng nhập.

### 2. Phía Frontend (Vue.js)

*   **[api.js](file:///c:/laragon/www/QuizFlex/fe_quizflex/src/services/api.js#L894-L897)**:
    *   Thêm phương thức kết nối API `getUpgradeCosts()` vào đối tượng `paymentsApi`.
*   **[Upgrade.vue](file:///c:/laragon/www/QuizFlex/fe_quizflex/src/views/user/Upgrade.vue)**:
    *   Chuyển hằng số `plans` thành biến phản xạ (`ref(plans)`) và thêm hàm `fetchUpgradeCosts()` gọi API trên để cập nhật thông tin nâng cấp khi tải trang.
    *   Viết hàm helper `isUserCurrentPlan(planId)` để kiểm tra nhanh gói cước hiện tại của người dùng.
    *   **Giao diện bảng giá**:
        *   Hiển thị giá nâng cấp động mới thay vì giá gốc.
        *   **Sửa lỗi hiển thị giá 0đ của gói đang dùng/hạ cấp**: Cập nhật logic hiển thị giá cước để chỉ áp dụng giá nâng cấp động khi gói đó được phép nâng cấp (`plan.upgradeInfo.allowed === true`). Đối với gói đang sử dụng hoặc hạ cấp, hệ thống giữ nguyên hiển thị giá gốc ban đầu (ví dụ: hiển thị 50.000đ thay vì 0đ) để giao diện trực quan và tránh nhầm lẫn cho người dùng.
        *   Hiển thị thêm nhãn xanh lá bắt mắt: **`🎉 Khấu trừ gói cũ: [Giá gốc] ➔ -[Khấu trừ]`** để giải thích rõ số tiền tiết kiệm được.
        *   Nếu là gói đang dùng: Nút mua đổi thành **`✓ Đang sử dụng`** và bị disabled.
        *   Nếu là gói thấp hơn hoặc bằng gói hiện tại (Hạ cấp): Nút mua đổi thành **`🔒 [Lý do không được hạ cấp]`** và bị disabled.
    *   **Giao diện Modal xác nhận & Chờ quét mã QR**:
        *   Hiển thị số tiền thực tế cần đóng và hiển thị chi tiết dòng: **`Khấu trừ gói cũ: -X.000đ`** trong hóa đơn tóm tắt trước khi người dùng click thanh toán.

---

## Hướng dẫn Kiểm thử (Manual Verification)

Bạn có thể chạy kiểm thử luồng nghiệp vụ này trực tiếp theo các bước sau:

### Bước 1: Tài khoản Free dùng thử gói Plus 7 ngày
1. Đăng nhập vào một tài khoản mới (vai trò **Free**, chưa dùng thử).
2. Vào trang Nâng cấp tài khoản (`/upgrade`), bạn sẽ thấy giá gốc:
   * **Gói Plus**: 50.000đ
   * **Gói Pro**: 120.000đ
   * **Gói Ultra**: 250.000đ
3. Click "Kích hoạt dùng thử Plus 7 ngày" hoàn toàn miễn phí (0đ).
4. Tài khoản của bạn sẽ có vai trò **PLUS** và hạn dùng còn 7 ngày.

### Bước 2: Kiểm tra giá nâng cấp khi đang dùng thử (Không cho phép khấu trừ)
1. F5 hoặc truy cập lại trang `/upgrade`. Lúc này bạn sẽ thấy:
   * **Gói Plus**: Hiển thị **`✓ Đang sử dụng`** (Nút bị khóa) và giá hiển thị **giữ nguyên là 50.000đ** (Không bị đổi thành 0đ gây nhầm lẫn).
   * **Gói Pro**:
     * Giá hiển thị vẫn là: **120.000đ** (Không bị khấu trừ 12k xuống còn 108k nữa, vì hệ thống kiểm tra thấy giao dịch trước là gói dùng thử miễn phí 0đ).
     * Không có dòng nhãn "Khấu trừ gói cũ".
   * **Gói Ultra**:
     * Giá hiển thị vẫn là: **250.000đ**.
2. Nhấp chọn nâng cấp lên gói **Pro** ➔ Hóa đơn trong Modal hiển thị đúng giá gốc **120.000đ**.
3. Tiến hành thanh toán thành công ➔ Tài khoản của bạn được chuyển đổi sang vai trò **PRO** đúng hạn 30 ngày mới.

### Bước 3: Kiểm thử Khấu trừ gói Plus chính thức mua bằng tiền
1. Tạo một tài khoản Free khác.
2. Tiến hành mua thẳng gói **Plus** bằng tiền (trả 50.000đ qua MoMo/PayOS).
3. Sau khi thanh toán thành công tài khoản lên gói **Plus** chính thức.
4. Truy cập lại `/upgrade` ➔ Bạn sẽ thấy:
   * **Gói Plus**: Hiển thị **`✓ Đang sử dụng`** (Nút bị khóa), giá hiển thị **giữ nguyên là 50.000đ**.
   * **Gói Pro**: Hiển thị giá đã khấu trừ là **70.000đ** (120k - 50k = 70k, vì gói Plus này của bạn được mua bằng 50k thật) và dòng nhãn khấu trừ xuất hiện đầy đủ.
   * **Gói Ultra**: Hiển thị giá đã khấu trừ là **200.000đ** (250k - 50k = 200k).
5. Tiến hành nâng cấp và xác nhận giá trị khấu trừ hoạt động chuẩn xác.
