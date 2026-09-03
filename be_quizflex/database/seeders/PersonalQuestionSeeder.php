<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Grade;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use App\Services\QuestionSnapshotService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PersonalQuestionSeeder extends Seeder
{
    /**
     * Khởi tạo kho câu hỏi cá nhân riêng biệt cho từng người dùng trong hệ thống
     */
    public function run(): void
    {
        $snapshotService = app(QuestionSnapshotService::class);
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('Chưa có người dùng nào. Vui lòng chạy UserSeeder trước!');
            return;
        }

        // Định nghĩa bộ câu hỏi riêng thiết kế riêng cho từng tài khoản
        $personalQuestionsByUser = [
            // =========================================================================
            // 1. THẦY HOÀNG MINH ĐỨC (Toán Chuyên THPT)
            // =========================================================================
            'thay.duchoang@quizflex.vn' => [
                [
                    'subject_code' => 'math',
                    'grade_code' => 'grade_12',
                    'topic_name' => 'Ứng dụng đạo hàm khảo sát hàm số',
                    'difficulty' => 'hard',
                    'content' => 'Tìm tất cả các giá trị thực của tham số $m$ để hàm số $y = \frac{x^3}{3} - mx^2 + (m^2 - m + 1)x + 1$ đạt cực đại tại $x = 1$.',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => '$m = 2$', 'is_correct' => true],
                        ['content' => '$m = 1$', 'is_correct' => false],
                        ['content' => '$m = 3$', 'is_correct' => false],
                        ['content' => '$m = -1$', 'is_correct' => false],
                    ],
                ],
                [
                    'subject_code' => 'math',
                    'grade_code' => 'grade_12',
                    'topic_name' => 'Số phức & Cực trị hình học',
                    'difficulty' => 'hard',
                    'content' => 'Cho số phức $z$ thỏa mãn $|z - 3 + 4i| = 2$. Giá trị lớn nhất của $|z|$ bằng:',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => '7', 'is_correct' => true],
                        ['content' => '5', 'is_correct' => false],
                        ['content' => '3', 'is_correct' => false],
                        ['content' => '9', 'is_correct' => false],
                    ],
                ],
                [
                    'subject_code' => 'math',
                    'grade_code' => 'grade_12',
                    'topic_name' => 'Khối đa diện & Thể tích khối chóp',
                    'difficulty' => 'medium',
                    'content' => 'Cho hình chóp $S.ABC$ có đáy $ABC$ là tam giác vuông cân tại $B$, $AB = a$. Cạnh bên $SA \perp (ABC)$ và $SA = a\sqrt{3}$. Thể tích khối chóp $S.ABC$ là:',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => '$\frac{a^3\sqrt{3}}{6}$', 'is_correct' => true],
                        ['content' => '$\frac{a^3\sqrt{3}}{3}$', 'is_correct' => false],
                        ['content' => '$\frac{a^3\sqrt{3}}{2}$', 'is_correct' => false],
                        ['content' => '$\frac{a^3}{6}$', 'is_correct' => false],
                    ],
                ],
                [
                    'subject_code' => 'math',
                    'grade_code' => 'grade_12',
                    'topic_name' => 'Tích phân từng phần',
                    'difficulty' => 'medium',
                    'content' => 'Biết $\int_0^1 (2x + 3)e^x dx = a \cdot e + b$ với $a, b \in \mathbb{Z}$. Tính tổng $S = a + b$.',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => '$S = 2$', 'is_correct' => true],
                        ['content' => '$S = 4$', 'is_correct' => false],
                        ['content' => '$S = -1$', 'is_correct' => false],
                        ['content' => '$S = 5$', 'is_correct' => false],
                    ],
                ],
                [
                    'subject_code' => 'math',
                    'grade_code' => 'grade_11',
                    'topic_name' => 'Tổ hợp - Xác suất',
                    'difficulty' => 'medium',
                    'content' => 'Một hộp chứa 5 quả cầu xanh và 4 quả cầu đỏ. Lấy ngẫu nhiên 3 quả cầu. Xác suất để lấy được ít nhất 1 quả cầu màu đỏ là:',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => '$\frac{37}{42}$', 'is_correct' => true],
                        ['content' => '$\frac{5}{42}$', 'is_correct' => false],
                        ['content' => '$\frac{25}{42}$', 'is_correct' => false],
                        ['content' => '$\frac{17}{42}$', 'is_correct' => false],
                    ],
                ],
                [
                    'subject_code' => 'math',
                    'grade_code' => 'grade_12',
                    'topic_name' => 'Phương trình mặt cầu trong Oxyz',
                    'difficulty' => 'easy',
                    'content' => 'Trong không gian $Oxyz$, mặt cầu $(S): (x-1)^2 + (y+2)^2 + (z-3)^2 = 16$ có tọa độ tâm $I$ và bán kính $R$ là:',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => '$I(1; -2; 3), R = 4$', 'is_correct' => true],
                        ['content' => '$I(-1; 2; -3), R = 4$', 'is_correct' => false],
                        ['content' => '$I(1; -2; 3), R = 16$', 'is_correct' => false],
                        ['content' => '$I(-1; 2; -3), R = 16$', 'is_correct' => false],
                    ],
                ],
            ],

            // =========================================================================
            // 2. CÔ PHẠM QUỲNH NGA (Tiếng Anh THPT & IELTS)
            // =========================================================================
            'co.quynhnga@quizflex.vn' => [
                [
                    'subject_code' => 'english',
                    'grade_code' => 'grade_12',
                    'topic_name' => 'Inversion & Advanced Grammar',
                    'difficulty' => 'hard',
                    'content' => 'Seldom ______ such a brilliant display of musical talent from young performers.',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => 'have we witnessed', 'is_correct' => true],
                        ['content' => 'we have witnessed', 'is_correct' => false],
                        ['content' => 'we witnessed', 'is_correct' => false],
                        ['content' => 'did we witnessed', 'is_correct' => false],
                    ],
                ],
                [
                    'subject_code' => 'english',
                    'grade_code' => 'grade_12',
                    'topic_name' => 'Collocations & Idioms',
                    'difficulty' => 'medium',
                    'content' => 'The manager decided to ______ the meeting short due to the unexpected power outage.',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => 'cut', 'is_correct' => true],
                        ['content' => 'make', 'is_correct' => false],
                        ['content' => 'take', 'is_correct' => false],
                        ['content' => 'drop', 'is_correct' => false],
                    ],
                ],
                [
                    'subject_code' => 'english',
                    'grade_code' => 'grade_12',
                    'topic_name' => 'Conditional Sentences & Mixed Conditionals',
                    'difficulty' => 'medium',
                    'content' => 'If you had taken my advice yesterday, you ______ in such deep trouble now.',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => 'would not be', 'is_correct' => true],
                        ['content' => 'would not have been', 'is_correct' => false],
                        ['content' => 'will not be', 'is_correct' => false],
                        ['content' => 'are not', 'is_correct' => false],
                    ],
                ],
                [
                    'subject_code' => 'english',
                    'grade_code' => 'grade_11',
                    'topic_name' => 'Phrasal Verbs',
                    'difficulty' => 'medium',
                    'content' => 'I can always ______ my best friend whenever I encounter serious challenges.',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => 'count on', 'is_correct' => true],
                        ['content' => 'take after', 'is_correct' => false],
                        ['content' => 'give up', 'is_correct' => false],
                        ['content' => 'look up', 'is_correct' => false],
                    ],
                ],
                [
                    'subject_code' => 'english',
                    'grade_code' => 'grade_12',
                    'topic_name' => 'Word Formation & Vocabulary',
                    'difficulty' => 'easy',
                    'content' => 'The rapid ______ of renewable energy has helped reduce carbon emissions globally.',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => 'development', 'is_correct' => true],
                        ['content' => 'developing', 'is_correct' => false],
                        ['content' => 'developed', 'is_correct' => false],
                        ['content' => 'developer', 'is_correct' => false],
                    ],
                ],
            ],

            // =========================================================================
            // 3. THẦY TRẦN QUỐC BẢO (Vật Lý Chuyên)
            // =========================================================================
            'thay.quocbao@quizflex.vn' => [
                [
                    'subject_code' => 'physics',
                    'grade_code' => 'grade_12',
                    'topic_name' => 'Dao động cơ & Con lắc lò xo',
                    'difficulty' => 'medium',
                    'content' => 'Một con lắc lò xo gồm vật nặng khối lượng $m = 100\text{ g}$ và lò xo có độ cứng $k = 100\text{ N/m}$. Chu kì dao động riêng của con lắc là (lấy $\pi^2 = 10$):',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => '$T = 0{,}2\text{ s}$', 'is_correct' => true],
                        ['content' => '$T = 0{,}1\text{ s}$', 'is_correct' => false],
                        ['content' => '$T = 2\text{ s}$', 'is_correct' => false],
                        ['content' => '$T = 1\text{ s}$', 'is_correct' => false],
                    ],
                ],
                [
                    'subject_code' => 'physics',
                    'grade_code' => 'grade_12',
                    'topic_name' => 'Dòng điện xoay chiều RLC nối tiếp',
                    'difficulty' => 'hard',
                    'content' => 'Đặt điện áp $u = U\sqrt{2}\cos(\omega t)$ vào hai đầu đoạn mạch RLC nối tiếp có $R = 50\ \Omega$, $Z_L = 100\ \Omega$, $Z_C = 50\ \Omega$. Hệ số công suất $\cos\varphi$ của mạch là:',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => '$\frac{\sqrt{2}}{2} \approx 0{,}707$', 'is_correct' => true],
                        ['content' => '$1$', 'is_correct' => false],
                        ['content' => '$0{,}5$', 'is_correct' => false],
                        ['content' => '$\frac{\sqrt{3}}{2}$', 'is_correct' => false],
                    ],
                ],
                [
                    'subject_code' => 'physics',
                    'grade_code' => 'grade_12',
                    'topic_name' => 'Sóng ánh sáng & Giao thoa Young',
                    'difficulty' => 'easy',
                    'content' => 'Trong thí nghiệm Young về giao thoa ánh sáng, khoảng vân $i$ được xác định theo công thức nào sau đây?',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => '$i = \frac{\lambda D}{a}$', 'is_correct' => true],
                        ['content' => '$i = \frac{\lambda a}{D}$', 'is_correct' => false],
                        ['content' => '$i = \frac{a D}{\lambda}$', 'is_correct' => false],
                        ['content' => '$i = \lambda D a$', 'is_correct' => false],
                    ],
                ],
                [
                    'subject_code' => 'physics',
                    'grade_code' => 'grade_12',
                    'topic_name' => 'Hạt nhân nguyên tử & Phóng xạ',
                    'difficulty' => 'medium',
                    'content' => 'Hạt nhân Poloni $^{210}_{84}\text{Po}$ phóng xạ $\alpha$ biến đổi thành hạt nhân Chì $^{206}_{82}\text{Pb}$. Hạt $\alpha$ phát ra có cấu tạo là:',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => 'Hạt nhân Heli $^{4}_{2}\text{He}$', 'is_correct' => true],
                        ['content' => 'Hạt proton $^{1}_{1}\text{p}$', 'is_correct' => false],
                        ['content' => 'Hạt electron $^{0}_{-1}\text{e}$', 'is_correct' => false],
                        ['content' => 'Hạt nơtron $^{1}_{0}\text{n}$', 'is_correct' => false],
                    ],
                ],
            ],

            // =========================================================================
            // 4. LÊ THANH HÀ (Hóa học & Sinh học Sư phạm)
            // =========================================================================
            'lethanhha@gmail.com' => [
                [
                    'subject_code' => 'chemistry',
                    'grade_code' => 'grade_12',
                    'topic_name' => 'Este - Lipit & Phản ứng xà phòng hóa',
                    'difficulty' => 'medium',
                    'content' => 'Thủy phân hoàn toàn $8{,}8\text{ g}$ etyl axetat ($\text{CH}_3\text{COOC}_2\text{H}_5$) trong dung dịch $\text{NaOH}$ dư, đun nóng. Khối lượng muối $\text{CH}_3\text{COONa}$ thu được là:',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => '$8{,}2\text{ gam}$', 'is_correct' => true],
                        ['content' => '$6{,}8\text{ gam}$', 'is_correct' => false],
                        ['content' => '$4{,}1\text{ gam}$', 'is_correct' => false],
                        ['content' => '$9{,}6\text{ gam}$', 'is_correct' => false],
                    ],
                ],
                [
                    'subject_code' => 'chemistry',
                    'grade_code' => 'grade_12',
                    'topic_name' => 'Cacbohiđrat & Glucozơ',
                    'difficulty' => 'easy',
                    'content' => 'Chất nào sau đây tác dụng với dung dịch $\text{AgNO}_3$ trong $\text{NH}_3$ đun nóng tạo thành kết tủa bạc trắng sáng (phản ứng tráng bạc)?',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => 'Glucozơ', 'is_correct' => true],
                        ['content' => 'Saccarozơ', 'is_correct' => false],
                        ['content' => 'Tinh bột', 'is_correct' => false],
                        ['content' => 'Xenlulozơ', 'is_correct' => false],
                    ],
                ],
                [
                    'subject_code' => 'biology',
                    'grade_code' => 'grade_12',
                    'topic_name' => 'Cơ chế di truyền ở cấp độ phân tử',
                    'difficulty' => 'medium',
                    'content' => 'Một phân tử $\text{mARN}$ dài $5100\ \text{Å}$. Số nuclêôtit của phân tử $\text{mARN}$ này là:',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => '1500 nuclêôtit', 'is_correct' => true],
                        ['content' => '3000 nuclêôtit', 'is_correct' => false],
                        ['content' => '1000 nuclêôtit', 'is_correct' => false],
                        ['content' => '2500 nuclêôtit', 'is_correct' => false],
                    ],
                ],
            ],

            // =========================================================================
            // 5. NGUYỄN DUY ANH (Tin học & Công nghệ Thông tin)
            // =========================================================================
            'nguyenduyanh@gmail.com' => [
                [
                    'subject_code' => 'informatics',
                    'grade_code' => 'grade_12',
                    'topic_name' => 'Cơ sở dữ liệu quan hệ SQL',
                    'difficulty' => 'medium',
                    'content' => 'Trong ngôn ngữ SQL, câu lệnh nào được sử dụng để lọc nhóm bản ghi dựa trên điều kiện tổng hợp (aggregate functions) sau GROUP BY?',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => 'HAVING', 'is_correct' => true],
                        ['content' => 'WHERE', 'is_correct' => false],
                        ['content' => 'ORDER BY', 'is_correct' => false],
                        ['content' => 'FILTER', 'is_correct' => false],
                    ],
                ],
                [
                    'subject_code' => 'informatics',
                    'grade_code' => 'grade_11',
                    'topic_name' => 'Giải thuật sắp xếp',
                    'difficulty' => 'medium',
                    'content' => 'Thuật toán sắp xếp QuickSort có độ phức tạp thời gian trung bình (average-case time complexity) là:',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => '$O(N \log N)$', 'is_correct' => true],
                        ['content' => '$O(N^2)$', 'is_correct' => false],
                        ['content' => '$O(N)$', 'is_correct' => false],
                        ['content' => '$O(\log N)$', 'is_correct' => false],
                    ],
                ],
                [
                    'subject_code' => 'technology',
                    'grade_code' => 'grade_10',
                    'topic_name' => 'Bản vẽ kỹ thuật & Hình chiếu',
                    'difficulty' => 'easy',
                    'content' => 'Hình chiếu đứng của một vật thể được nhìn từ hướng nào?',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => 'Từ trước tới', 'is_correct' => true],
                        ['content' => 'Từ trên xuống', 'is_correct' => false],
                        ['content' => 'Từ trái sang', 'is_correct' => false],
                        ['content' => 'Từ phải sang', 'is_correct' => false],
                    ],
                ],
            ],

            // =========================================================================
            // 6. NGUYỄN KHÁNH LINH (Kỹ năng mềm & Tiếng Anh)
            // =========================================================================
            'nguyenkhanhlinh@gmail.com' => [
                [
                    'subject_code' => 'skills',
                    'grade_code' => 'other_gen',
                    'topic_name' => 'Kỹ năng quản trị thời gian Pomodoro',
                    'difficulty' => 'easy',
                    'content' => 'Nguyên tắc chuẩn của phương pháp quản lý thời gian Pomodoro là làm việc tập trung trong bao nhiêu phút trước mỗi lần nghỉ ngắn?',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => '25 phút', 'is_correct' => true],
                        ['content' => '45 phút', 'is_correct' => false],
                        ['content' => '15 phút', 'is_correct' => false],
                        ['content' => '60 phút', 'is_correct' => false],
                    ],
                ],
                [
                    'subject_code' => 'english',
                    'grade_code' => 'university_gen',
                    'topic_name' => 'IELTS Reading Skills',
                    'difficulty' => 'medium',
                    'content' => 'In academic IELTS Reading, which reading technique involves quickly searching for specific words, numbers, or key phrases?',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => 'Scanning', 'is_correct' => true],
                        ['content' => 'Skimming', 'is_correct' => false],
                        ['content' => 'Intensive reading', 'is_correct' => false],
                        ['content' => 'Proofreading', 'is_correct' => false],
                    ],
                ],
            ],

            // =========================================================================
            // 7. PHAN MINH KHÔI (Hóa học & Sinh học 12)
            // =========================================================================
            'phanminhkhoi@gmail.com' => [
                [
                    'subject_code' => 'chemistry',
                    'grade_code' => 'grade_12',
                    'topic_name' => 'Kim loại kiềm và kiềm thổ',
                    'difficulty' => 'easy',
                    'content' => 'Kim loại nào sau đây có tính khử mạnh nhất và có thể cắt dễ dàng bằng dao thí nghiệm?',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => 'Natri (Na)', 'is_correct' => true],
                        ['content' => 'Sắt (Fe)', 'is_correct' => false],
                        ['content' => 'Đồng (Cu)', 'is_correct' => false],
                        ['content' => 'Nhôm (Al)', 'is_correct' => false],
                    ],
                ],
                [
                    'subject_code' => 'biology',
                    'grade_code' => 'grade_12',
                    'topic_name' => 'Quy luật phân ly độc lập Mendel',
                    'difficulty' => 'medium',
                    'content' => 'Phép lai giữa hai cá thể dị hợp 2 cặp gen $AaBb \times AaBb$ (phân ly độc lập) cho tỉ lệ kiểu hình đời con là:',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => '9 : 3 : 3 : 1', 'is_correct' => true],
                        ['content' => '3 : 1', 'is_correct' => false],
                        ['content' => '1 : 2 : 1', 'is_correct' => false],
                        ['content' => '9 : 7', 'is_correct' => false],
                    ],
                ],
            ],

            // =========================================================================
            // 8. VŨ MINH QUÂN (Học sinh 12A1)
            // =========================================================================
            'vuminhquan@gmail.com' => [
                [
                    'subject_code' => 'math',
                    'grade_code' => 'grade_12',
                    'topic_name' => 'Logarit & Phương trình mũ',
                    'difficulty' => 'easy',
                    'content' => 'Nghiệm của phương trình $\log_2(x - 3) = 3$ là:',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => '$x = 11$', 'is_correct' => true],
                        ['content' => '$x = 9$', 'is_correct' => false],
                        ['content' => '$x = 6$', 'is_correct' => false],
                        ['content' => '$x = 8$', 'is_correct' => false],
                    ],
                ],
                [
                    'subject_code' => 'physics',
                    'grade_code' => 'grade_12',
                    'topic_name' => 'Sóng âm & Đặc trưng sinh lý',
                    'difficulty' => 'easy',
                    'content' => 'Độ cao của âm là một đặc trưng sinh lý gắn liền trực tiếp với đặc trưng vật lý nào sau đây của sóng âm?',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => 'Tần số âm', 'is_correct' => true],
                        ['content' => 'Mức cường độ âm', 'is_correct' => false],
                        ['content' => 'Đồ thị dao động âm', 'is_correct' => false],
                        ['content' => 'Tốc độ truyền âm', 'is_correct' => false],
                    ],
                ],
            ],

            // =========================================================================
            // 9. ĐẶNG THÙY LINH (Học sinh 12A3 - Văn Sử Địa)
            // =========================================================================
            'dangthuylinh@gmail.com' => [
                [
                    'subject_code' => 'literature',
                    'grade_code' => 'grade_12',
                    'topic_name' => 'Vợ chồng A Phủ - Tô Hoài',
                    'difficulty' => 'medium',
                    'content' => 'Trong tác phẩm "Vợ chồng A Phủ", âm thanh nào trong đêm tình mùa xuân đã đánh thức sức sống tiềm tàng và khát vọng tự do của Mị?',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => 'Tiếng sáo gọi bạn tình', 'is_correct' => true],
                        ['content' => 'Tiếng khèn Mông', 'is_correct' => false],
                        ['content' => 'Tiếng suối chảy róc rách', 'is_correct' => false],
                        ['content' => 'Tiếng gõ mõ cúng ma', 'is_correct' => false],
                    ],
                ],
                [
                    'subject_code' => 'history',
                    'grade_code' => 'grade_12',
                    'topic_name' => 'Chiến dịch Điện Biên Phủ 1954',
                    'difficulty' => 'easy',
                    'content' => 'Tổng tư lệnh chỉ huy trực tiếp chiến dịch Điện Biên Phủ năm 1954 của Quân đội nhân dân Việt Nam là ai?',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => 'Đại tướng Võ Nguyên Giáp', 'is_correct' => true],
                        ['content' => 'Đại tướng Nguyễn Chí Thanh', 'is_correct' => false],
                        ['content' => 'Đại tướng Văn Tiến Dũng', 'is_correct' => false],
                        ['content' => 'Trung tướng Hoàng Văn Thái', 'is_correct' => false],
                    ],
                ],
            ],

            // =========================================================================
            // 10. HOÀNG VIỆT ANH (Học sinh 11B2)
            // =========================================================================
            'hoangvietanh@gmail.com' => [
                [
                    'subject_code' => 'physics',
                    'grade_code' => 'grade_11',
                    'topic_name' => 'Định luật Cu-lông & Điện tích',
                    'difficulty' => 'easy',
                    'content' => 'Khi khoảng cách giữa hai điện tích điểm trong chân không tăng lên 3 lần thì lực tương tác tĩnh điện giữa chúng sẽ:',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => 'Giảm 9 lần', 'is_correct' => true],
                        ['content' => 'Tăng 9 lần', 'is_correct' => false],
                        ['content' => 'Giảm 3 lần', 'is_correct' => false],
                        ['content' => 'Không đổi', 'is_correct' => false],
                    ],
                ],
                [
                    'subject_code' => 'informatics',
                    'grade_code' => 'grade_11',
                    'topic_name' => 'Lập trình Python cơ bản',
                    'difficulty' => 'easy',
                    'content' => 'Trong Python, kết quả của biểu thức `len(["Toan", "Ly", "Hoa"])` là:',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => '3', 'is_correct' => true],
                        ['content' => '10', 'is_correct' => false],
                        ['content' => '2', 'is_correct' => false],
                        ['content' => '4', 'is_correct' => false],
                    ],
                ],
            ],

            // =========================================================================
            // 11. ĐỖ HUYỀN MY (Học sinh 10A2)
            // =========================================================================
            'dohuyenmy@gmail.com' => [
                [
                    'subject_code' => 'civics',
                    'grade_code' => 'grade_10',
                    'topic_name' => 'Hiến pháp nước CHXHCN Việt Nam',
                    'difficulty' => 'easy',
                    'content' => 'Văn bản quy phạm pháp luật có hiệu lực pháp lý cao nhất trong hệ thống pháp luật Việt Nam là:',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => 'Hiến pháp', 'is_correct' => true],
                        ['content' => 'Bộ luật Dân sự', 'is_correct' => false],
                        ['content' => 'Nghị định của Chính phủ', 'is_correct' => false],
                        ['content' => 'Thông tư của Bộ trưởng', 'is_correct' => false],
                    ],
                ],
            ],

            // =========================================================================
            // 12. NGUYỄN THẾ PHONG (Học sinh 12 Chuyên Tin)
            // =========================================================================
            'nguyenthephong@gmail.com' => [
                [
                    'subject_code' => 'informatics',
                    'grade_code' => 'grade_12',
                    'topic_name' => 'Quy hoạch động & Đồ thị',
                    'difficulty' => 'hard',
                    'content' => 'Thuật toán Dijkstra được sử dụng để tìm đường đi ngắn nhất từ một đỉnh nguồn đến các đỉnh còn lại trên đồ thị có đặc điểm:',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => 'Trọng số các cạnh không âm', 'is_correct' => true],
                        ['content' => 'Đồ thị không có chu trình', 'is_correct' => false],
                        ['content' => 'Trọng số các cạnh có thể âm', 'is_correct' => false],
                        ['content' => 'Đồ thị hai phía', 'is_correct' => false],
                    ],
                ],
            ],

            // =========================================================================
            // 13. BÙI TRUNG KIÊN (Học sinh 11A1)
            // =========================================================================
            'buitrungkien@gmail.com' => [
                [
                    'subject_code' => 'math',
                    'grade_code' => 'grade_11',
                    'topic_name' => 'Cấp số cộng và Cấp số nhân',
                    'difficulty' => 'medium',
                    'content' => 'Cho cấp số cộng $(u_n)$ có số hạng đầu $u_1 = 3$ và công sai $d = 4$. Số hạng thứ 10 của cấp số cộng là:',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => '$u_{10} = 39$', 'is_correct' => true],
                        ['content' => '$u_{10} = 43$', 'is_correct' => false],
                        ['content' => '$u_{10} = 36$', 'is_correct' => false],
                        ['content' => '$u_{10} = 40$', 'is_correct' => false],
                    ],
                ],
            ],

            // =========================================================================
            // 14. TRẦN HOÀNG LONG (ADMIN)
            // =========================================================================
            'admin@quizflex.vn' => [
                [
                    'subject_code' => 'informatics',
                    'grade_code' => 'other_gen',
                    'topic_name' => 'Kiến trúc hệ thống & An toàn thông tin',
                    'difficulty' => 'medium',
                    'content' => 'Giao thức bảo mật mạng nào mã hóa toàn bộ dữ liệu trao đổi giữa trình duyệt web và máy chủ bằng chứng chỉ TLS/SSL?',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => 'HTTPS (HTTP over TLS)', 'is_correct' => true],
                        ['content' => 'HTTP 1.1', 'is_correct' => false],
                        ['content' => 'FTP', 'is_correct' => false],
                        ['content' => 'Telnet', 'is_correct' => false],
                    ],
                ],
                [
                    'subject_code' => 'skills',
                    'grade_code' => 'other_gen',
                    'topic_name' => 'Tư duy phản biện & Giải quyết vấn đề',
                    'difficulty' => 'easy',
                    'content' => 'Mô hình SMART trong xác lập mục tiêu bao gồm 5 tiêu chí: Cụ thể (Specific), Đo lường được (Measurable), Khả thi (Achievable), Phù hợp (Relevant) và:',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => 'Có thời hạn xác định (Time-bound)', 'is_correct' => true],
                        ['content' => 'Đơn giản (Simple)', 'is_correct' => false],
                        ['content' => 'Tự động (Systematic)', 'is_correct' => false],
                        ['content' => 'Mở rộng (Scalable)', 'is_correct' => false],
                    ],
                ],
            ],

            // =========================================================================
            // 15. NGUYỄN ANH TUẤN (ADMIN KHẢO THÍ)
            // =========================================================================
            'admin@quizflex.local' => [
                [
                    'subject_code' => 'literature',
                    'grade_code' => 'high_school',
                    'topic_name' => 'Khảo thí đề thi môn Ngữ văn',
                    'difficulty' => 'medium',
                    'content' => 'Theo ma trận đề thi tốt nghiệp THPT chuẩn Bộ GD&ĐT, phần Đọc hiểu văn bản thường chiếm bao nhiêu điểm trên thang điểm 10?',
                    'type' => 'single_choice',
                    'points' => 10,
                    'answers' => [
                        ['content' => '3.0 điểm', 'is_correct' => true],
                        ['content' => '2.0 điểm', 'is_correct' => false],
                        ['content' => '4.0 điểm', 'is_correct' => false],
                        ['content' => '5.0 điểm', 'is_correct' => false],
                    ],
                ],
            ],
        ];

        $totalPersonalCount = 0;

        foreach ($personalQuestionsByUser as $email => $questionsList) {
            $user = User::where('email', $email)->first();
            if (!$user) {
                continue;
            }

            foreach ($questionsList as $item) {
                $subject = Subject::where('code', $item['subject_code'])->first();
                $grade = Grade::where('code', $item['grade_code'])->first();

                if (!$subject) {
                    $subject = Subject::first();
                }

                $fingerprint = $snapshotService->computeFingerprintFromSnapshot(
                    $item['content'],
                    $item['type'],
                    $item['answers']
                );

                // Tạo hoặc cập nhật câu hỏi cá nhân trong kho riêng của người dùng
                $question = Question::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'fingerprint' => $fingerprint,
                        'origin_question_id' => null,
                        'quiz_id' => null,
                    ],
                    [
                        'is_public' => false,
                        'bank_submission_status' => 'none',
                        'content' => $item['content'],
                        'type' => $item['type'],
                        'difficulty' => $item['difficulty'],
                        'education_level_id' => $grade?->education_level_id ?? $subject->education_level_id,
                        'grade_id' => $grade?->id,
                        'subject_id' => $subject->id,
                        'topic_name' => $item['topic_name'],
                        'points' => $item['points'] ?? 10,
                        'order' => 0,
                    ]
                );

                // Đồng bộ các câu trả lời
                $question->answers()->delete();
                foreach ($item['answers'] as $idx => $ans) {
                    Answer::create([
                        'question_id' => $question->id,
                        'content' => $ans['content'],
                        'is_correct' => (bool)$ans['is_correct'],
                        'order' => $idx,
                    ]);
                }

                $totalPersonalCount++;
            }
        }

        $this->command->info("Đã khởi tạo thành công {$totalPersonalCount} câu hỏi trong kho câu hỏi riêng cho các người dùng!");
    }
}
