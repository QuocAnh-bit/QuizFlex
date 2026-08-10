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
                        'answers' => ['Dấu hai chấm (:)', 'Dấu chấm phẩy (;)', 'Dấu chấm (.)', 'Dấu phẩy (,)'],
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
                'title' => 'IELTS Academic Vocabulary & Phrasal Verbs',
                'description' => 'Chinh phục các từ vựng học thuật băng nhóm C1/C2 và các phrasal verbs nâng cao trong đề thi IELTS.',
                'category' => 'Ngoại ngữ',
                'tag' => 'IELTS',
                'difficulty' => 'hard',
                'is_public' => true,
                'status' => 'published',
                'time_limit_seconds' => 720,
                'icon' => 'IELTS',
                'badge' => 'ENG',
                'questions' => [
                    [
                        'content' => 'Which word is a synonym of "Substantial" in academic writing?',
                        'correct' => 'A',
                        'answers' => ['Significant', 'Tiny', 'Slight', 'Negligible'],
                    ],
                    [
                        'content' => 'Complete the phrasal verb: "The researchers need to ______ out more experiments before reaching a conclusion."',
                        'correct' => 'C',
                        'answers' => ['bring', 'take', 'carry', 'put'],
                    ],
                    [
                        'content' => 'Choose the word that means "to make a bad situation worse":',
                        'correct' => 'B',
                        'answers' => ['Ameliorate', 'Exacerbate', 'Mitigate', 'Alleviate'],
                    ],
                    [
                        'content' => 'What is the meaning of the idiom "To call it a day"?',
                        'correct' => 'D',
                        'answers' => ['To start a new project', 'To celebrate a holiday', 'To work overtime', 'To stop working on something'],
                    ],
                    [
                        'content' => 'Select the correct word: "The new policy had a profound ______ on the local economy."',
                        'correct' => 'A',
                        'answers' => ['effect', 'affect', 'effective', 'affection'],
                    ],
                    [
                        'content' => 'Which of the following means "unbelievable or hard to credit"?',
                        'correct' => 'B',
                        'answers' => ['Credible', 'Incredible', 'Credulous', 'Incredulous'],
                    ],
                ],
            ],
            [
                'title' => 'English Grammar Masterclass: Tenses & Conditionals',
                'description' => 'Luyện tập chuyên sâu các thì phức hợp, câu điều kiện hỗn hợp và đảo ngữ trong tiếng Anh.',
                'category' => 'Ngoại ngữ',
                'tag' => 'Ngữ pháp',
                'difficulty' => 'medium',
                'is_public' => true,
                'status' => 'published',
                'time_limit_seconds' => 600,
                'icon' => 'GRAM',
                'badge' => 'EN',
                'questions' => [
                    [
                        'content' => 'If I ______ harder at university, I would have got a better job.',
                        'correct' => 'C',
                        'answers' => ['studied', 'study', 'had studied', 'have studied'],
                    ],
                    [
                        'content' => 'By the time you arrive tomorrow, we ______ the entire project.',
                        'correct' => 'B',
                        'answers' => ['will finish', 'will have finished', 'finished', 'are finishing'],
                    ],
                    [
                        'content' => 'Hardly ______ home when the electricity went out.',
                        'correct' => 'A',
                        'answers' => ['had I arrived', 'I arrived', 'did I arrive', 'I had arrived'],
                    ],
                    [
                        'content' => 'She suggested that he ______ a doctor immediately.',
                        'correct' => 'D',
                        'answers' => ['sees', 'saw', 'is seeing', 'see'],
                    ],
                    [
                        'content' => 'Unless you ______ your reservation in advance, you won\'t get a table.',
                        'correct' => 'B',
                        'answers' => ['don\'t make', 'make', 'made', 'will make'],
                    ],
                ],
            ],
            [
                'title' => 'Business English & Workplace Communication',
                'description' => 'Từ vựng và mẫu câu giao tiếp tiếng Anh công sở, viết email chuyên nghiệp và đàm phán thương mại.',
                'category' => 'Ngoại ngữ',
                'tag' => 'Thương mại',
                'difficulty' => 'medium',
                'is_public' => true,
                'status' => 'published',
                'time_limit_seconds' => 600,
                'icon' => 'BIZ',
                'badge' => 'ENG',
                'questions' => [
                    [
                        'content' => 'What is the formal opening phrase commonly used in business emails when you don\'t know the recipient\'s name?',
                        'correct' => 'A',
                        'answers' => ['Dear Sir or Madam,', 'Hey there,', 'Hi friend,', 'To my boss,'],
                    ],
                    [
                        'content' => 'Choose the professional phrase for "Tôi muốn hoãn cuộc họp lại":',
                        'correct' => 'C',
                        'answers' => ['I want to destroy the meeting', 'I stop the meeting now', 'I would like to postpone the meeting', 'I kick the meeting away'],
                    ],
                    [
                        'content' => 'What does "KPI" stand for in business operations?',
                        'correct' => 'B',
                        'answers' => ['Key Person Index', 'Key Performance Indicator', 'Knowledge Process Integration', 'Key Product Investment'],
                    ],
                    [
                        'content' => 'Fill in the blank: "Please find attached our latest price ______ for your review."',
                        'correct' => 'D',
                        'answers' => ['quote-less', 'quoting', 'quoted', 'quotation'],
                    ],
                    [
                        'content' => 'Which phrase means "to reach an agreement during negotiation"?',
                        'correct' => 'A',
                        'answers' => ['To strike a deal', 'To break the bank', 'To call the shots', 'To bite the bullet'],
                    ],
                ],
            ],
            [
                'title' => 'TOEIC Test Prep: Essential Practice & Collocations',
                'description' => 'Bộ đề luyện tập từ vựng, ngữ pháp Part 5 & 6 thường gặp trong kỳ thi TOEIC quốc tế.',
                'category' => 'Ngoại ngữ',
                'tag' => 'TOEIC',
                'difficulty' => 'easy',
                'is_public' => true,
                'status' => 'published',
                'time_limit_seconds' => 500,
                'icon' => 'TOEIC',
                'badge' => 'EN',
                'questions' => [
                    [
                        'content' => 'The manager requested that all employees submit their monthly reports ______ Friday afternoon.',
                        'correct' => 'B',
                        'answers' => ['until', 'by', 'since', 'for'],
                    ],
                    [
                        'content' => 'Due to severe weather conditions, flight departures have been ______ delayed.',
                        'correct' => 'A',
                        'answers' => ['temporarily', 'temporary', 'temporize', 'temporariness'],
                    ],
                    [
                        'content' => 'All passengers are reminded to keep their personal belongings ______ at all times.',
                        'correct' => 'C',
                        'answers' => ['attend', 'attending', 'attended', 'attendant'],
                    ],
                    [
                        'content' => 'The company offers an attractive salary package ______ comprehensive healthcare benefits.',
                        'correct' => 'D',
                        'answers' => ['along', 'instead of', 'except', 'along with'],
                    ],
                    [
                        'content' => 'Customer satisfaction is our top ______ at QuizFlex Corporation.',
                        'correct' => 'A',
                        'answers' => ['priority', 'prior', 'prioritize', 'priorities'],
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
            [
                'title' => 'Ôn thi THPT Quốc Gia - Hóa Học Chuyên sâu',
                'description' => 'Bộ câu hỏi tổng hợp kiến thức Hóa học hữu cơ, vô cơ và các dạng bài tập este, kim loại.',
                'category' => 'Hóa học',
                'tag' => 'THPT QG',
                'difficulty' => 'hard',
                'is_public' => true,
                'status' => 'published',
                'time_limit_seconds' => 900,
                'icon' => 'CHEM',
                'badge' => 'HOA',
                'questions' => [
                    [
                        'content' => 'Chất nào sau đây là este có mùi thơm đặc trưng của chuối chín?',
                        'correct' => 'A',
                        'answers' => ['Isoamyl acetat', 'Ethyl acetat', 'Benzyl acetat', 'Methyl fomat'],
                    ],
                    [
                        'content' => 'Kim loại nào sau đây có tính dẫn điện và dẫn nhiệt tốt nhất trong tất cả các kim loại?',
                        'correct' => 'C',
                        'answers' => ['Vàng (Au)', 'Đồng (Cu)', 'Bạc (Ag)', 'Nhôm (Al)'],
                    ],
                    [
                        'content' => 'Phương pháp làm mềm nước cứng tạm thời đơn giản nhất bằng cách đun nóng là dựa trên phản ứng phân hủy muối nào?',
                        'correct' => 'B',
                        'answers' => ['Muối Clorua', 'Muối Bicarbonat (HCO3-)', 'Muối Sulfat', 'Muối Nitrat'],
                    ],
                    [
                        'content' => 'Thủy ngân (Hg) rơi vãi khi nhiệt kế vỡ có thể được thu gom an toàn bằng cách rắc chất bột nào sau đây?',
                        'correct' => 'D',
                        'answers' => ['Bột vôi sống', 'Bột cát', 'Bột muối ăn', 'Bột lưu huỳnh (S)'],
                    ],
                    [
                        'content' => 'Dung dịch làm quỳ tím chuyển sang màu đỏ là dung dịch nào sau đây?',
                        'correct' => 'A',
                        'answers' => ['Axit Axetic (CH3COOH)', 'Amoniac (NH3)', 'Anilin (C6H5NH2)', 'Glucozơ'],
                    ],
                ],
            ],
            [
                'title' => 'Kinh tế Học & Tài chính Doanh nghiệp',
                'description' => 'Kiểm tra hiểu biết về các nguyên lý kinh tế vi mô, vĩ mô và tài chính quản trị.',
                'category' => 'Kinh tế',
                'tag' => 'Tài chính',
                'difficulty' => 'medium',
                'is_public' => true,
                'status' => 'published',
                'time_limit_seconds' => 600,
                'icon' => 'ECO',
                'badge' => 'FIN',
                'questions' => [
                    [
                        'content' => 'Khi giá của một hàng hóa tăng lên, theo Quy luật Cung Cầu, lượng cầu của người tiêu dùng đối với hàng hóa đó thường sẽ biến động thế nào?',
                        'correct' => 'B',
                        'answers' => ['Tăng lên', 'Giảm đi', 'Không thay đổi', 'Bằng 0'],
                    ],
                    [
                        'content' => 'Thuật ngữ GDP trong kinh tế học vĩ mô là viết tắt của cụm từ tiếng Anh nào?',
                        'correct' => 'A',
                        'answers' => ['Gross Domestic Product', 'General Domestic Performance', 'Gross Development Price', 'Global Domestic Product'],
                    ],
                    [
                        'content' => 'Hiện tượng mức giá chung của nền kinh tế gia tăng liên tục theo thời gian làm giảm sức mua của đồng tiền gọi là gì?',
                        'correct' => 'C',
                        'answers' => ['Suy thoái', 'Thâm hụt', 'Lạm phát', 'Khủng hoảng'],
                    ],
                    [
                        'content' => 'Ngân hàng Trung ương điều chỉnh lãi suất tái chiết khấu nhằm mục đích chính nào?',
                        'correct' => 'D',
                        'answers' => ['Thu thuế thu nhập', 'Quản lý giá xăng dầu', 'Tăng số lượng công ty', 'Điều tiết cung tiền và kiểm soát lạm phát'],
                    ],
                ],
            ],
            [
                'title' => 'Văn học Việt Nam Hiện đại & Kinh điển',
                'description' => 'Cùng ôn lại các tác phẩm văn học xuất sắc trong chương trình ngữ văn và nền văn học nước nhà.',
                'category' => 'Văn học',
                'tag' => 'Ngữ văn',
                'difficulty' => 'easy',
                'is_public' => true,
                'status' => 'published',
                'time_limit_seconds' => 600,
                'icon' => 'LIT',
                'badge' => 'VAN',
                'questions' => [
                    [
                        'content' => 'Tác phẩm kiệt tác "Truyện Kiều" của Đại thi hào Nguyễn Du ban đầu có tên gốc là gì?',
                        'correct' => 'A',
                        'answers' => ['Đoạn Trường Tân Thanh', 'Kim Vân Kiều Truyện', 'Thanh Hiên Thi Tập', 'Nam Âm Tuyệt Xướng'],
                    ],
                    [
                        'content' => 'Hình ảnh nhân vật Tràng đưa cô vợ nhặt về nhà trong cảnh đói thê thảm năm 1945 thuộc tác phẩm nào của nhà văn Kim Lân?',
                        'correct' => 'C',
                        'answers' => ['Làng', 'Chí Phèo', 'Vợ Nhặt', 'Tắt Đèn'],
                    ],
                    [
                        'content' => 'Bài thơ "Tây Tiến" khắc họa hình ảnh người lính hào hoa, dũng cảm là sáng tác nổi tiếng của nhà thơ nào?',
                        'correct' => 'A',
                        'answers' => ['Quang Dũng', 'Tố Hữu', 'Chế Lan Viên', 'Huy Cận'],
                    ],
                    [
                        'content' => 'Nhân vật nghệ sĩ phếp ảnh Phùng và chiếc thuyền ngoài xa rực rỡ trong sương sớm là nhân vật chính trong tác phẩm của ai?',
                        'correct' => 'D',
                        'answers' => ['Nam Cao', 'Nguyễn Tuân', 'Nguyễn Minh Châu', 'Nguyễn Thi'],
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
