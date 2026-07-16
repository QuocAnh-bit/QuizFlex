<?php
 
namespace Database\Seeders;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('role', 'ADMIN')->first() ?? User::first();
        if (!$user) {
            $user = User::create([
                'name' => 'QuizFlex Admin',
                'email' => 'admin@quizflex.local',
                'password' => bcrypt('password'),
                'role' => 'ADMIN',
                'ai_quota_remaining' => 999,
            ]);
        }

        $quizzes = [
            [
                'title' => 'Địa lý Việt Nam kỳ thú',
                'description' => 'Bộ trắc nghiệm tìm hiểu về các địa danh, sông ngòi và kỷ lục địa lý của đất nước Việt Nam.',
                'category' => 'Địa lý',
                'tag' => 'Việt Nam',
                'difficulty' => 'medium',
                'is_public' => true,
                'status' => 'published',
                'time_limit_seconds' => 600,
                'icon' => 'GEO',
                'badge' => 'VN',
                'questions' => [
                    [
                        'content' => 'Đỉnh núi cao nhất Việt Nam và Đông Dương tên là gì?',
                        'correct' => 'A',
                        'answers' => ['Fansipan (Phan Xi Păng)', 'Tây Côn Lĩnh', 'Bạch Mã', 'Puxailaileng'],
                    ],
                    [
                        'content' => 'Sông Mê Kông đổ ra biển Đông qua bao nhiêu cửa sông lớn tại lãnh thổ Việt Nam?',
                        'correct' => 'B',
                        'answers' => ['7 cửa', '9 cửa (Cửu Long)', '5 cửa', '3 cửa'],
                    ],
                    [
                        'content' => 'Tỉnh nào có diện tích lớn nhất Việt Nam hiện nay?',
                        'correct' => 'A',
                        'answers' => ['Nghệ An', 'Thanh Hóa', 'Sơn La', 'Lâm Đồng'],
                    ],
                    [
                        'content' => 'Quần đảo Hoàng Sa về mặt hành chính thuộc quyền quản lý của tỉnh/thành phố nào?',
                        'correct' => 'C',
                        'answers' => ['Khánh Hòa', 'Quảng Nam', 'Thành phố Đà Nẵng', 'Bình Thuận'],
                    ],
                    [
                        'content' => 'Vườn quốc gia Phong Nha - Kẻ Bàng nằm ở tỉnh nào của nước ta?',
                        'correct' => 'D',
                        'answers' => ['Quảng Trị', 'Thừa Thiên Huế', 'Hà Tĩnh', 'Quảng Bình'],
                    ],
                    [
                        'content' => 'Hồ nước ngọt tự nhiên lớn nhất Việt Nam là hồ nào?',
                        'correct' => 'A',
                        'answers' => ['Hồ Ba Bể', 'Hồ Tây', 'Hồ Hoàn Kiếm', 'Hồ Trị An'],
                    ],
                    [
                        'content' => 'Tỉnh nào của Việt Nam sở hữu đường bờ biển dài nhất?',
                        'correct' => 'B',
                        'answers' => ['Quảng Ninh', 'Khánh Hòa', 'Cà Mau', 'Bình Thuận'],
                    ],
                    [
                        'content' => 'Mũi Cực Nam trên đất liền của nước Việt Nam nằm ở địa phận tỉnh nào?',
                        'correct' => 'C',
                        'answers' => ['Kiên Giang', 'Bạc Liêu', 'Cà Mau', 'Sóc Trăng'],
                    ],
                ],
            ],
            [
                'title' => 'Lập trình và Khoa học Máy tính',
                'description' => 'Kiểm tra kiến thức cơ bản về các ngôn ngữ lập trình, giao thức và kiến trúc máy tính.',
                'category' => 'Công nghệ',
                'tag' => 'Lập trình',
                'difficulty' => 'easy',
                'is_public' => true,
                'status' => 'published',
                'time_limit_seconds' => 600,
                'icon' => 'TECH',
                'badge' => 'IT',
                'questions' => [
                    [
                        'content' => 'Ngôn ngữ lập trình nào được sử dụng phổ biến nhất để xây dựng tương tác và logic động trên giao diện trình duyệt Web?',
                        'correct' => 'C',
                        'answers' => ['Python', 'C++', 'JavaScript', 'SQL'],
                    ],
                    [
                        'content' => 'Trong phát triển web, cụm từ viết tắt "HTML" có nghĩa là gì?',
                        'correct' => 'A',
                        'answers' => ['HyperText Markup Language', 'Hyperlinks and Text Markup Language', 'Home Tool Markup Language', 'HyperText Machine Language'],
                    ],
                    [
                        'content' => 'Ký hiệu nào thường được dùng để kết thúc một câu lệnh trong các ngôn ngữ như PHP, C, C++ và Java?',
                        'correct' => 'B',
                        'answers' => ['Dấu hai chấm (:)','Dấu chấm phẩy (;)', 'Dấu chấm (.)', 'Dấu phẩy (,)'],
                    ],
                    [
                        'content' => 'Đâu không phải là một hệ quản trị cơ sở dữ liệu quan hệ (RDBMS)?',
                        'correct' => 'D',
                        'answers' => ['MySQL', 'PostgreSQL', 'Microsoft SQL Server', 'MongoDB'],
                    ],
                    [
                        'content' => 'Giao thức bảo mật tiêu chuẩn giúp mã hóa truyền tải dữ liệu giữa trình duyệt và máy chủ web là gì?',
                        'correct' => 'A',
                        'answers' => ['HTTPS', 'HTTP', 'FTP', 'SMTP'],
                    ],
                    [
                        'content' => 'Framework phát triển ứng dụng Web phổ biến Laravel được viết bằng ngôn ngữ lập trình nào?',
                        'correct' => 'B',
                        'answers' => ['Ruby', 'PHP', 'Python', 'JavaScript'],
                    ],
                ],
            ],
            [
                'title' => 'Toán học vui & Tư duy Logic',
                'description' => 'Rèn luyện trí não với các câu hỏi toán học nhanh và câu đố tư duy logic thú vị.',
                'category' => 'Toán học',
                'tag' => 'Logic',
                'difficulty' => 'medium',
                'is_public' => true,
                'status' => 'published',
                'time_limit_seconds' => 720,
                'icon' => 'MATH',
                'badge' => 'LOG',
                'questions' => [
                    [
                        'content' => 'Số nào là số tiếp theo hợp quy luật trong dãy số sau: 2, 4, 8, 16, ...?',
                        'correct' => 'D',
                        'answers' => ['20', '24', '28', '32'],
                    ],
                    [
                        'content' => 'Tổng số đo các góc trong của một hình tam giác phẳng luôn bằng bao nhiêu độ?',
                        'correct' => 'A',
                        'answers' => ['180 độ', '90 độ', '360 độ', '270 độ'],
                    ],
                    [
                        'content' => 'Nếu 3 con mèo bắt được 3 con chuột trong vòng 3 phút, thì cần bao nhiêu phút để 100 con mèo bắt được 100 con chuột?',
                        'correct' => 'B',
                        'answers' => ['100 phút', '3 phút', '1 phút', '33 phút'],
                    ],
                    [
                        'content' => 'Một gia đình có 5 người con trai, mỗi người con trai lại có đúng 1 người em gái út. Hỏi gia đình đó có tổng cộng bao nhiêu người con?',
                        'correct' => 'C',
                        'answers' => ['10 người con', '5 người con', '6 người con', '8 người con'],
                    ],
                    [
                        'content' => 'Giá trị của số Pi (π) dùng trong hình học thường được làm tròn xấp xỉ bằng bao nhiêu?',
                        'correct' => 'A',
                        'answers' => ['3.14', '3.12', '3.16', '3.20'],
                    ],
                ],
            ],
            [
                'title' => 'Tiếng Anh giao tiếp cơ bản',
                'description' => 'Trắc nghiệm nhanh về từ vựng, ngữ pháp thông dụng và cách phản xạ tiếng Anh hàng ngày.',
                'category' => 'Ngoại ngữ',
                'tag' => 'Tiếng Anh',
                'difficulty' => 'easy',
                'is_public' => true,
                'status' => 'published',
                'time_limit_seconds' => 450,
                'icon' => 'ENG',
                'badge' => 'EN',
                'questions' => [
                    [
                        'content' => 'Từ nào dưới đây là từ trái nghĩa chính xác của từ "Beautiful" (Xinh đẹp)?',
                        'correct' => 'B',
                        'answers' => ['Nice', 'Ugly', 'Pretty', 'Attractive'],
                    ],
                    [
                        'content' => 'Hãy điền giới từ thích hợp vào chỗ trống: "I am interested ______ learning English."',
                        'correct' => 'A',
                        'answers' => ['in', 'at', 'on', 'with'],
                    ],
                    [
                        'content' => 'Đâu là câu chào hỏi lịch sự và phù hợp nhất khi bạn lần đầu tiên được giới thiệu gặp gỡ một ai đó?',
                        'correct' => 'C',
                        'answers' => ['What\'s up?', 'Hello bro!', 'Nice to meet you', 'How\'s it going?'],
                    ],
                    [
                        'content' => 'Thì nào trong tiếng Anh được sử dụng để diễn tả một hành động lặp đi lặp lại như một thói quen ở thời điểm hiện tại?',
                        'correct' => 'D',
                        'answers' => ['Thì Hiện tại tiếp diễn', 'Thì Quá khứ đơn', 'Thì Hiện tại hoàn thành', 'Thì Hiện tại đơn'],
                    ],
                    [
                        'content' => 'Chọn từ viết đúng chính tả tiếng Anh trong các phương án sau:',
                        'correct' => 'A',
                        'answers' => ['Necessary', 'Neccessary', 'Necesasry', 'Neccesary'],
                    ],
                    [
                        'content' => 'Từ "Vocabulary" dịch sang nghĩa tiếng Việt chính xác là gì?',
                        'correct' => 'B',
                        'answers' => ['Ngữ pháp', 'Từ vựng', 'Phát âm', 'Bài đọc'],
                    ],
                    [
                        'content' => 'Điền dạng động từ đúng vào chỗ trống: "She ______ to school by bus every day."',
                        'correct' => 'C',
                        'answers' => ['go', 'going', 'goes', 'went'],
                    ],
                ],
            ],
            [
                'title' => 'Khám phá Vũ trụ & Hệ Mặt Trời',
                'description' => 'Hành trình thú vị khám phá các hành tinh, các vì sao và những sự kiện thiên văn kỳ vĩ.',
                'category' => 'Thiên văn học',
                'tag' => 'Vũ trụ',
                'difficulty' => 'hard',
                'is_public' => true,
                'status' => 'published',
                'time_limit_seconds' => 600,
                'icon' => 'SPACE',
                'badge' => 'UNIVERSE',
                'questions' => [
                    [
                        'content' => 'Hành tinh nào trong Hệ Mặt Trời được mệnh danh là "Hành tinh đỏ"?',
                        'correct' => 'D',
                        'answers' => ['Sao Kim', 'Sao Thủy', 'Sao Mộc', 'Sao Hỏa'],
                    ],
                    [
                        'content' => 'Hành tinh nào có kích thước và khối lượng lớn nhất trong Hệ Mặt Trời?',
                        'correct' => 'A',
                        'answers' => ['Sao Mộc', 'Sao Thổ', 'Sao Thiên Vương', 'Sao Hải Vương'],
                    ],
                    [
                        'content' => 'Thiên thể nào nằm ở vị trí trung tâm, cung cấp nhiệt lượng và ánh sáng duy trì sự sống cho Hệ Mặt Trời?',
                        'correct' => 'B',
                        'answers' => ['Trái Đất', 'Mặt Trời', 'Mặt Trăng', 'Sao Bắc Cực'],
                    ],
                    [
                        'content' => 'Trái Đất của chúng ta mất khoảng bao nhiêu ngày để hoàn thành một chu kỳ quay quanh Mặt Trời?',
                        'correct' => 'C',
                        'answers' => ['30 ngày', '360 ngày', '365 ngày (hoặc 366 ngày năm nhuận)', '24 ngày'],
                    ],
                    [
                        'content' => 'Nhà du hành vũ trụ nào là người đầu tiên đặt chân lên bề mặt Mặt Trăng vào năm 1969?',
                        'correct' => 'A',
                        'answers' => ['Neil Armstrong', 'Yuri Gagarin', 'Buzz Aldrin', 'Alan Shepard'],
                    ],
                    [
                        'content' => 'Hiện tượng thiên văn xảy ra khi Mặt Trăng đi vào giữa Trái Đất và Mặt Trời, che khuất một phần hoặc toàn bộ ánh sáng Mặt Trời gọi là gì?',
                        'correct' => 'B',
                        'answers' => ['Nguyệt thực', 'Nhật thực', 'Sao băng', 'Hố đen'],
                    ],
                ],
            ],
        ];

        foreach ($quizzes as $quizData) {
            $questions = $quizData['questions'];
            unset($quizData['questions']);

            $quiz = Quiz::updateOrCreate(
                ['title' => $quizData['title']],
                array_merge($quizData, ['user_id' => $user->id])
            );

            // Xóa các câu hỏi cũ để tránh trùng lặp nếu chạy seeder nhiều lần
            $quiz->questions()->delete();

            foreach ($questions as $questionIndex => $questionData) {
                $answers = $questionData['answers'];
                $correct = $questionData['correct'];

                $question = $quiz->questions()->create([
                    'content' => $questionData['content'],
                    'type' => 'single_choice',
                    'order' => $questionIndex,
                    'points' => 10,
                ]);

                foreach ($answers as $answerIndex => $answerContent) {
                    $question->answers()->create([
                        'content' => $answerContent,
                        'is_correct' => chr(65 + $answerIndex) === $correct,
                        'order' => $answerIndex,
                    ]);
                }
            }
        }
    }
}
