# Hướng dẫn Chi Tiết Kiến Trúc Xác Thực JWT (JSON Web Token)

## Dự án: QuizFlex (Vue 3 Frontend ✦ Laravel API Backend)

Tài liệu này giải thích chi tiết, toàn diện và sâu sắc về **vai trò, cấu trúc kỹ thuật và cơ chế hoạt động của JWT (JSON Web Token)** trong dự án QuizFlex. 

Bạn có thể sử dụng các sơ đồ chuẩn hóa, ví dụ thực tế và kịch bản lập luận trong tài liệu này để thuyết trình trước **Giáo viên hướng dẫn và Hội đồng chấm đồ án tốt nghiệp**.

---

## 1. Tổng Quan & Vai Trò Của JWT Trong QuizFlex

### 1.1 JWT là gì?
**JWT (JSON Web Token)** là một chuẩn mở ([RFC 7519](https://tools.ietf.org/html/rfc7519)) định nghĩa phương thức truyền tải thông tin an toàn, nhỏ gọn giữa các bên dưới dạng một đối tượng **JSON**. Thông tin này có thể được kiểm chứng và tin tưởng tuyệt đối vì nó đã được **ký điện tử (Digitally Signed)** bằng một chuỗi khóa bí mật phía Server (`JWT_SECRET`).

### 1.2 Tại sao QuizFlex chọn JWT thay vì Session/Cookie truyền thống?

Hệ thống QuizFlex xây dựng theo mô hình **Decoupled Architecture** (Frontend Vue 3 độc lập hoàn toàn với Backend Laravel API). Sử dụng JWT mang lại 4 lợi ích vượt trội:

```mermaid
graph TD
    A[Kiến trúc Decoupled QuizFlex] --> B[1. Stateless - Không lưu Session ở Server]
    A --> C[2. Hiệu năng & Khả năng Mở rộng Scale]
    A --> D[3. Hỗ trợ Đa nền tảng Cross-Domain / Mobile]
    A --> E[4. Phân quyền tức thì qua Custom Claims]

    B --> B1[Server không tốn RAM lưu bộ nhớ phiên người dùng]
    C --> C1[Dễ dàng mở rộng ra nhiều Server API mà không lo mất Session]
    D --> D1[Frontend Vue 3, Mobile App hay PWA đều dùng chung 1 API]
    E --> E1[Đọc trực tiếp Role: Admin/Free/VIP ngay từ Token]
```

1. **Tính Không Lưu Trạng Thái (Stateless):** Server Laravel không phải duy trì dữ liệu Session trong RAM hay Database cho từng người dùng. Mỗi Request gửi lên từ Client đã mang theo đầy đủ "chứng minh thư" (Token) để tự xác thực.
2. **Tối Ưu Hiệu Năng & Khả Năng Mở Rộng (Scalability):** Nếu ứng dụng tăng từ 100 lên 100.000 người dùng, Server API không bị quá tải bộ nhớ Session. Nếu cần mở rộng thêm nhiều máy chủ Backend (Load Balancing), các máy chủ không cần phải chia sẻ bộ nhớ Session với nhau.
3. **Tương Thích Đa Nền Tảng (Cross-Platform / Cross-Origin):** JWT giải quyết hoàn toàn rào cản **CORS** và chính sách Cookie giữa các tên miền khác nhau. Trong tương lai, nếu QuizFlex phát triển thêm Mobile App (Flutter/React Native), ứng dụng mobile chỉ việc tái sử dụng 100% các API sẵn có này.
4. **Phân Quyền Ngay Tại Token (Self-contained Claims):** Chứa trực tiếp định danh User ID và Vai trò (`role`) ngay trong nội dung token. Backend có thể biết ngay người dùng là `admin`, `pro` hay `free` mà không bắt buộc phải thực thi câu lệnh SQL `SELECT * FROM users` ở mọi request.

---

## 2. Cấu Trúc Kỹ Thuật 3 Phần Của JWT Trong QuizFlex

Một chuỗi JWT Token trong QuizFlex có dạng 3 chuỗi Mã hóa Base64URL nối với nhau bằng 2 dấu chấm (`.`):  
`header.payload.signature`

```
eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0OjgwMDAvYXBpL2F1dGgvbG9naW4iLCJpYXQiOjE3NTQ2MDAwMDAsImV4cCI6MTc1NDY4NjQwMCwibmJmIjoxNzU0NjAwMDAwLCJqdGkiOiJ4WVo5ODc2NSIsInN1YiI6NDUsInJvbGUiOiJwcm8ifQ.K8zX9yW2aB3cD4eF5gH6iJ7kL8mN9oP0qR1sT2uV3wX
```

### Chi tiết 3 phần trong QuizFlex:

```mermaid
graph LR
    subgraph Header [1. Header - Màu Đỏ]
        H1["Algorithm: HS256"]
        H2["Type: JWT"]
    end

    subgraph Payload [2. Payload - Màu Tím]
        P1["sub: 45 (User ID)"]
        P2["role: 'pro' (Custom Claim)"]
        P3["exp: 1754686400 (Expires At)"]
        P4["jti: 'xYZ98765' (JWT ID)"]
    end

    subgraph Signature [3. Signature - Màu Xanh]
        S1["HMACSHA256("]
        S2["base64(Header) + '.' + base64(Payload),"]
        S3["JWT_SECRET_KEY )"]
    end
```

#### ① Phần Header (Tiêu đề):
Chứa loại token và thuật toán ký:
```json
{
  "alg": "HS256",
  "typ": "JWT"
}
```
- `alg`: Thuật toán mã hóa băm chữ ký là **HS256** (HMAC sử dụng SHA-256).

#### ② Phần Payload (Tải trọng dữ liệu):
Chứa các thông tin (Claims) về người dùng và hệ thống. Trong QuizFlex, Payload được cấu hình bao gồm:
```json
{
  "iss": "http://localhost:8000/api/auth/login",
  "iat": 1754600000,
  "exp": 1754686400,
  "nbf": 1754600000,
  "jti": "xYZ98765abcdef123456",
  "sub": 45,
  "role": "pro"
}
```
- **Các Claims Tiêu Chuẩn (Registered Claims):**
  - `sub` (Subject): ID duy nhất của người dùng trong bảng `users` (được định nghĩa qua hàm `getJWTIdentifier()` tại [User.php](file:///c:/laragon/www/QuizFlex/be_quizflex/app/Models/User.php#L138)).
  - `iss` (Issuer): Cổng API đã phát hành token này.
  - `iat` (Issued At): Thời điểm token được sinh ra (Unix timestamp).
  - `exp` (Expiration Time): Thời điểm token hết hạn.
  - `jti` (JWT ID): Mã định danh duy nhất cho bản ghi token này (dùng để cho vào Blacklist khi Logout/Refresh).
- **Custom Claim Dành Cho QuizFlex:**
  - `role`: Vai trò quyền hạn của người dùng (`free`, `pro`, `ultra`, `admin`) được chèn động từ hàm `getJWTCustomClaims()` tại [User.php](file:///c:/laragon/www/QuizFlex/be_quizflex/app/Models/User.php#L143).

#### ③ Phần Signature (Chữ ký bảo mật):
Được tạo ra bằng cách lấy phần Header đã mã hóa Base64 + phần Payload đã mã hóa Base64, kết hợp với chuỗi khóa bí mật `JWT_SECRET` lưu ẩn dưới file `.env` của Server Laravel và băm qua thuật toán HMAC-SHA256:

$$\text{Signature} = \text{HMACSHA256}\left(\text{base64UrlEncode}(Header) + "." + \text{base64UrlEncode}(Payload), \text{JWT\_SECRET}\right)$$

> [!IMPORTANT]
> **TẠI SAO HẮC CƠ (HACKER) KHÔNG THỂ GIẢ MẠO TOKEN?**  
> Nếu Hacker cố tình sửa `role` trong Payload từ `"free"` thành `"admin"` ở phía Client, khi gửi lên Server, Server sẽ tính toán lại chữ ký Signature bằng `JWT_SECRET` bí mật. Chữ ký mới tính ra sẽ **không khớp** với chữ ký bị sửa đổi ➔ Server lập tức từ chối và trả về lỗi `401 Unauthorized`.

---

## 3. Vòng Đời & Cơ Chế Hoạt Động Tuần Tự (JWT Lifecycle Flow)

Dưới đây là bức tranh toàn cảnh về luồng đi của JWT trong QuizFlex từ khi khởi tạo đến khi thực thi các yêu cầu bảo mật:

```mermaid
sequenceDiagram
    autonumber
    actor User as Người dùng
    participant Vue as Frontend (Vue 3 / Axios)
    participant LS as LocalStorage Browser
    participant Router as Vue Router Guard
    participant API as Laravel API (Controller)
    participant Guard as JWT Auth Guard ('api')
    participant DB as Database (MySQL)

    User->>Vue: Nhập Email/Password & Đăng nhập
    Vue->>API: HTTP POST /api/auth/login
    API->>DB: So khớp email & bcrypt password
    DB-->>API: Đúng mật khẩu & Email đã verify
    API->>Guard: Tạo Token đại diện cho User (Auth Guard 'api')
    Guard-->>API: Trả về chuỗi JWT Token (HS256)
    API-->>Vue: HTTP 200 OK (Chứa Token & User info)
    Vue->>LS: tokenStorage.set(token) -> Lưu vào localStorage
    
    note over Vue,API: KỂ TỪ ĐÂY: MỌI REQUEST ĐỀU ĐƯỢC ĐÓNG DẤU BỞI AXIOS INTERCEPTOR

    User->>Vue: Bấm vào trang "Danh sách bài thi" (/quizzes)
    Vue->>Router: Navigation Guard (router.beforeEach)
    Router->>LS: Đọc token từ localStorage -> Kiểm tra có tồn tại
    Router-->>Vue: Hợp lệ -> Cho phép render View
    Vue->>API: HTTP GET /api/quizzes (Header: Authorization: Bearer <token>)
    API->>Guard: Middleware 'auth:api' kiểm tra chữ ký & hạn sử dụng
    Guard->>Guard: Giải mã JWT -> Kiểm tra jti trong Blacklist -> Lấy User ID & Role
    Guard-->>API: Xác thực thành công (Authenticated User)
    API->>DB: SQL Query danh sách Quiz của User ID này
    DB-->>API: Trả về mảng dữ liệu Quiz
    API-->>Vue: HTTP 200 OK kèm dữ liệu Quiz JSON
    Vue-->>User: Hiển thị giao diện bài thi hoàn chỉnh
```

---

## 4. Chi Tiết Các Bước Kỹ Thuật Trong Mã Nguồn QuizFlex

### 🛠️ Bước 1: Cấu hình Backend & Sinh Token khi Đăng nhập

- **File cấu hình:** [config/auth.php](file:///c:/laragon/www/QuizFlex/be_quizflex/config/auth.php) & [config/jwt.php](file:///c:/laragon/www/QuizFlex/be_quizflex/config/jwt.php)
- **Model khai báo:** [User.php](file:///c:/laragon/www/QuizFlex/be_quizflex/app/Models/User.php) (implements `JWTSubject`)
- **Controller xử lý:** [AuthController.php](file:///c:/laragon/www/QuizFlex/be_quizflex/app/Http/Controllers/AuthController.php)

Trong `AuthController.php`, hàm `login()` thực hiện sinh token thông qua Guard `api`:

```php
// app/Http/Controllers/AuthController.php
$credentials = $request->only('email', 'password');

// Sinh token tự động nếu đúng tài khoản/mật khẩu
if (! $token = auth('api')->attempt($credentials)) {
    return response()->json(['error' => 'Email hoặc mật khẩu không đúng'], 401);
}

// Trả token về cho Frontend
return response()->json([
    'success' => true,
    'token' => $token,
    'data' => $this->formatUser(auth('api')->user())
]);
```

### 🛡️ Bước 2: Lưu trữ Token và Tự động "Đóng dấu" Request phía Frontend

- **File dịch vụ API:** [api.js](file:///c:/laragon/www/QuizFlex/fe_quizflex/src/services/api.js)

Khi Frontend nhận token, `tokenStorage.set(token)` lưu token vào `localStorage` dưới khóa `'quizflex_access_token'`.  
Để tránh việc phải tự viết lại dòng đính kèm Header ở hàng trăm hàm gọi API, ta sử dụng **Axios Request Interceptor** tại [api.js:L134-L140](file:///c:/laragon/www/QuizFlex/fe_quizflex/src/services/api.js#L134-L140):

```javascript
// fe_quizflex/src/services/api.js
api.interceptors.request.use((config) => {
  const token = tokenStorage.get()
  if (token) {
    // Tự động đóng dấu chứng minh thư vào mọi request gửi đi
    config.headers.Authorization = `Bearer ${token}`
  }
  return config;
}, (error) => Promise.reject(error));
```

### 🔒 Bước 3: Đóng vai "Người gác cổng" - Protection qua Navigation Guard & Middleware

- **Bảo vệ phía Frontend (Vue Router):** [router.js](file:///c:/laragon/www/QuizFlex/fe_quizflex/src/router/router.js#L69-L102)  
  Trước khi người dùng chuyển trang, Navigation Guard `router.beforeEach` đọc token từ `localStorage`. Nếu chuyển vào trang yêu cầu đăng nhập (`meta.requiresAuth`) mà không có token, hệ thống lập tức chặn lại và điều hướng về `/login`.

- **Bảo vệ phía Backend (Laravel Middleware):** [api.php](file:///c:/laragon/www/QuizFlex/be_quizflex/routes/api.php)  
  Các API quan trọng được bọc bởi middleware `auth:api`. Khi request tới, Middleware trích xuất token từ Header `Authorization: Bearer <token>`, giải mã, đối chiếu chữ ký và cấp quyền truy cập vào Controller.

---

## 5. Cơ Chế Làm Mới Token Ngầm (Silent Token Refresh)

### 5.1 Bài toán thời hạn sống của Token
Trong QuizFlex:
- **Access Token TTL (`ttl`):** Có thời gian sống ngắn là **24 giờ (1440 phút)** (cấu hình tại [config/jwt.php:L92](file:///c:/laragon/www/QuizFlex/be_quizflex/config/jwt.php#L92)).
- **Refresh TTL (`refresh_ttl`):** Cửa sổ cho phép đổi token cũ lấy token mới là **14 ngày (20160 phút)** (cấu hình tại [config/jwt.php:L121](file:///c:/laragon/www/QuizFlex/be_quizflex/config/jwt.php#L121)).

Nếu không có cơ chế làm mới ngầm, sau 24h học sinh đang làm dở bài thi sẽ bị văng ra trang Login rất khó chịu.

### 5.2 Giải pháp "Bắt lỗi 401 & Tự động cấp đổi Token" của Axios

QuizFlex giải quyết bài toán này bằng **Axios Response Interceptor** tại [api.js:L140-L216](file:///c:/laragon/www/QuizFlex/fe_quizflex/src/services/api.js#L140-L216):

```mermaid
sequenceDiagram
    autonumber
    participant App as Vue UI Component
    participant Axios as api.js (Axios Interceptor)
    participant Auth as AuthController (Laravel)

    App->>Axios: Gọi API lấy đề thi (Token đã hết hạn 24h)
    Axios->>Auth: HTTP GET /api/quizzes/123
    Auth-->>Axios: Trả về HTTP 401 Unauthorized (TokenExpiredException)

    note over Axios: Interceptor chặn lỗi 401 lại!<br/>Bật isRefreshing = true<br/>Xếp các request khác vào mảng chờ (failedQueue)

    Axios->>Auth: HTTP POST /api/auth/refresh (Gửi ngầm token cũ)
    Auth->>Auth: Hủy token cũ (vào Blacklist) & Sinh Access Token mới
    Auth-->>Axios: Trả về HTTP 200 OK { token: "NEW_JWT_TOKEN" }

    note over Axios: Cập nhật NEW_JWT_TOKEN vào localStorage<br/>Giải phóng failedQueue với Header mới

    Axios->>Auth: Thử lại HTTP GET /api/quizzes/123 (mang Token mới)
    Auth-->>Axios: HTTP 200 OK (Danh sách câu hỏi bài thi)
    Axios-->>App: Trả dữ liệu trơn tru (Người dùng không hề hay biết vừa có lỗi hết hạn)
```

---

## 6. Cơ Chế Danh Sách Đen (Token Blacklist & Invalidation)

### 6.1 Vì sao Stateless JWT vẫn cần đến Blacklist?
JWT là không trạng thái, điều này dẫn tới 1 điểm yếu bảo mật: **"Một khi Token đã phát hành, nó sẽ hợp lệ cho tới giây cuối cùng của thời hạn `exp`"**.

**Kịch bản nguy hiểm:** Nếu người dùng bấm **Đăng xuất (Logout)** hoặc Quản trị viên **Khóa tài khoản**, nếu không hủy token ngay lập tức, kẻ xấu lấy được chuỗi token đó vẫn có thể gọi API trong thời hạn còn lại.

### 6.2 Xử lý Blacklist tại QuizFlex
QuizFlex bật tính năng `'blacklist_enabled' => true` tại [config/jwt.php:L221](file:///c:/laragon/www/QuizFlex/be_quizflex/config/jwt.php#L221).

```mermaid
graph TD
    A[Sự kiện Logout / Token Refresh] --> B[Gọi auth'api'->logout / refresh]
    B --> C[Trích xuất mã định danh duy nhất jti từ Payload]
    C --> D[Lưu jti vào Cache Storage của Laravel]
    D --> E[Cài đặt thời gian sống cho jti = Thời gian còn lại của Token]
    
    F[Request mới gửi lên] --> G{Middleware auth:api đọc jti}
    G -- jti có trong Cache Blacklist --> H[Từ chối ngay! Trả về HTTP 401 Token Blacklisted]
    G -- jti không có trong Cache --> I[Cho phép đi tiếp vào Controller]
```

- **Grace Period (Thời gian ân hạn):** Cấu hình `'blacklist_grace_period' => 60` (60 giây) tại [config/jwt.php:L237](file:///c:/laragon/www/QuizFlex/be_quizflex/config/jwt.php#L237) giúp giải quyết triệt để vấn đề nghẽn mạng khi các request đồng thời (parallel requests) cùng gửi lên lúc token vừa refresh.

---

## 7. Bản Đồ Tóm Tắt Các Tệp Mã Nguồn Xử Lý JWT

| Tệp mã nguồn | Đường dẫn file | Vai trò xử lý JWT |
| :--- | :--- | :--- |
| **User Model** | [User.php](file:///c:/laragon/www/QuizFlex/be_quizflex/app/Models/User.php) | Định nghĩa `getJWTIdentifier()` (ID) & `getJWTCustomClaims()` (Gán `role`) |
| **Auth Config** | [auth.php](file:///c:/laragon/www/QuizFlex/be_quizflex/config/auth.php) | Cấu hình guard `'api'` sử dụng driver `'jwt'` |
| **JWT Config** | [jwt.php](file:///c:/laragon/www/QuizFlex/be_quizflex/config/jwt.php) | Cấu hình `secret`, thuật toán `HS256`, `ttl` (24h), `refresh_ttl` (14 ngày), `blacklist` |
| **Auth Controller** | [AuthController.php](file:///c:/laragon/www/QuizFlex/be_quizflex/app/Http/Controllers/AuthController.php) | Xử lý `login()`, `refresh()`, `logout()`, `me()` |
| **API Client** | [api.js](file:///c:/laragon/www/QuizFlex/fe_quizflex/src/services/api.js) | Lưu JWT vào `localStorage`, cài đặt Request Interceptor đóng dấu `Bearer` và Response Interceptor tự động refresh 401 |
| **Vue Router** | [router.js](file:///c:/laragon/www/QuizFlex/fe_quizflex/src/router/router.js) | Navigation Guard kiểm tra tính tồn tại của Token trước khi cho phép vào Router bảo vệ |

---

## 8. Bí Quyết Trình Bày Thuyết Phục Thầy Cô Giáo (Hội Đồng)

Khi thầy cô trong Hội đồng hỏi: **"Em hãy giải thích vai trò và cách thức làm việc của JWT trong ứng dụng của em?"**, bạn chỉ cần trả lời tự tin theo 5 bước sau:

1. **Khái quát vai trò:** *"Thưa thầy/cô, dự án QuizFlex của em sử dụng kiến trúc tách biệt hoàn toàn Frontend (Vue 3) và Backend (Laravel RESTful API). Em chọn **JWT (JSON Web Token)** để xác thực vì nó hoạt động theo cơ chế **Stateless**, giúp Server không phải tốn tài nguyên RAM lưu Session, dễ dàng mở rộng và hỗ trợ đa nền tảng."*
2. **Cấu trúc kỹ thuật:** *"Mỗi JWT Token của em gồm 3 phần: **Header** chỉ định thuật toán HS256, **Payload** chứa thông tin User ID và Custom Claim là `role` (Admin/Pro/Free), và **Signature** được băm mật mã bằng chuỗi bí mật `JWT_SECRET` phía server để đảm bảo không ai có thể giả mạo token."*
3. **Cơ chế truyền nhận:** *"Ở Frontend, em lưu Token vào `localStorage` và xây dựng một **Axios Request Interceptor**. Trợ lý này tự động đóng dấu Header `Authorization: Bearer <token>` vào mọi yêu cầu gửi lên API mà người dùng không cần thao tác thủ công."*
4. **Xử lý làm mới ngầm (Silent Refresh):** *"Để đảm bảo an toàn, Access Token chỉ có hạn sống ngắn 24 giờ. Em sử dụng **Axios Response Interceptor** để bắt lỗi `401 Unauthorized` khi token hết hạn, tự động gửi request ngầm đổi lấy token mới trong cửa sổ 14 ngày mà không làm gián đoạn trải nghiệm của học sinh khi đang làm bài thi."*
5. **Bảo mật với Blacklist:** *"Cuối cùng, để khắc phục điểm yếu của JWT khi người dùng Đăng xuất hoặc bị Khóa tài khoản, em kích hoạt cơ chế **Blacklist** bằng cách đưa mã định danh `jti` của token vào bộ nhớ Cache của Laravel, chặn đứng lập tức mọi truy cập sử dụng lại token cũ."*
