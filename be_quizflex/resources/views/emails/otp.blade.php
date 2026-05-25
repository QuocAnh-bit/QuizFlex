<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mã xác thực OTP QuizFlex</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 24px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
        }
        .logo {
            font-size: 28px;
            font-weight: 900;
            color: #7c3aed;
            margin-bottom: 24px;
            letter-spacing: -0.05em;
        }
        .logo span {
            color: #a78bfa;
        }
        h1 {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 8px;
            color: #ffffff;
        }
        p {
            font-size: 15px;
            line-height: 1.6;
            color: #94a3b8;
            margin-bottom: 32px;
        }
        .otp-box {
            background-color: #0f172a;
            border: 2px dashed #7c3aed;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 32px;
            display: inline-block;
        }
        .otp-code {
            font-size: 36px;
            font-weight: 900;
            letter-spacing: 8px;
            color: #38bdf8;
            margin: 0;
        }
        .warning {
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #334155;
            padding-top: 24px;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">Quiz<span>Flex</span></div>
        <h1>Mã Xác Thực OTP Đăng Ký</h1>
        <p>Chào mừng bạn đến với <b>QuizFlex</b>! Để hoàn tất việc thiết lập tài khoản và kích hoạt đầy đủ tính năng, vui lòng sử dụng mã OTP dưới đây để xác thực:</p>
        
        <div class="otp-box">
            <h2 class="otp-code">{{ $otpCode }}</h2>
        </div>
        
        <p style="margin-top: 0; font-size: 13px; color: #a78bfa;">* Mã OTP này chỉ có hiệu lực trong vòng <b>10 phút</b> và chỉ sử dụng được 1 lần.</p>
        
        <div class="warning">
            Nếu bạn không thực hiện yêu cầu đăng ký này, vui lòng bỏ qua email này hoặc liên hệ hỗ trợ.
            <br>
            © {{ date('Y') }} QuizFlex Team. All rights reserved.
        </div>
    </div>
</body>
</html>
