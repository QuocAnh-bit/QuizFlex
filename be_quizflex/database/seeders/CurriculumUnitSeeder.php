<?php

namespace Database\Seeders;

use App\Models\CurriculumDocument;
use App\Models\CurriculumUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurriculumUnitSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        CurriculumUnit::truncate();
        CurriculumDocument::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $documents = [];
        $subjects = ['Toán', 'Ngữ văn', 'Tiếng Anh', 'Khoa học tự nhiên', 'Vật lí', 'Hóa học', 'Sinh học', 'Lịch sử', 'Địa lí', 'Lịch sử và Địa lí', 'Giáo dục công dân'];
        foreach ($subjects as $s) {
            $documents[$s] = CurriculumDocument::create([
                'subject' => $s,
                'title' => "Chương trình Giáo dục Phổ thông 2018 - Môn {$s}",
                'file_path' => "curriculum/{$s}_GDPT2018.pdf",
                'publisher' => 'Bộ Giáo dục và Đào tạo',
                'legal_document' => '32/2018/TT-BGDĐT',
                'curriculum_version' => 'GDPT2018',
                'status' => 'embedded',
            ]);
        }

        $units = [
            // ==================== TIỂU HỌC: TOÁN HỌC ====================
            // Lớp 1
            [
                'subject' => 'Toán',
                'grade_min' => 1,
                'grade_max' => 1,
                'education_level' => 'primary',
                'topic' => 'Số và phép tính',
                'title' => 'Các số từ 0 đến 10 và phép cộng, phép trừ trong phạm vi 10',
                'content' => 'Nhận biết, đọc, viết, đếm các số từ 0 đến 10. So sánh số. Phép cộng và trừ trong phạm vi 10. Bảng cộng, bảng trừ trong phạm vi 10.',
                'learning_outcomes' => ['Đọc, viết, so sánh được các số trong phạm vi 10', 'Thực hiện được phép cộng, trừ trong phạm vi 10'],
            ],
            [
                'subject' => 'Toán',
                'grade_min' => 1,
                'grade_max' => 1,
                'education_level' => 'primary',
                'topic' => 'Số và phép tính',
                'title' => 'Các số trong phạm vi 100 và phép cộng, trừ không nhớ',
                'content' => 'Đọc, viết, đếm các số đến 100. So sánh các số đến 100. Phép cộng, phép trừ không nhớ trong phạm vi 100.',
                'learning_outcomes' => ['Biết đếm, đọc, viết các số có hai chữ số đến 100', 'Cộng, trừ không nhớ trong phạm vi 100'],
            ],
            [
                'subject' => 'Toán',
                'grade_min' => 1,
                'grade_max' => 1,
                'education_level' => 'primary',
                'topic' => 'Hình học và Đo lường',
                'title' => 'Hình phẳng, hình khối đơn giản và đo độ dài (cm)',
                'content' => 'Nhận biết hình vuông, hình tròn, hình tam giác, hình chữ nhật. Nhận biết khối lập phương, khối hộp chữ nhật. Đơn vị đo độ dài xăng-ti-mét (cm). Đọc giờ đúng trên đồng hồ.',
                'learning_outcomes' => ['Nhận dạng được hình phẳng và hình khối cơ bản', 'Biết dùng thước đo độ dài xăng-ti-mét và xem giờ đúng'],
            ],

            // Lớp 2
            [
                'subject' => 'Toán',
                'grade_min' => 2,
                'grade_max' => 2,
                'education_level' => 'primary',
                'topic' => 'Số và phép tính',
                'title' => 'Phép cộng, phép trừ có nhớ trong phạm vi 100',
                'content' => 'Phép cộng có nhớ trong phạm vi 100. Phép trừ có nhớ trong phạm vi 100. Giải bài toán nhiều hơn, ít hơn.',
                'learning_outcomes' => ['Thực hiện thành thạo phép cộng trừ có nhớ đến 100', 'Giải toán có lời văn về nhiều hơn, ít hơn'],
            ],
            [
                'subject' => 'Toán',
                'grade_min' => 2,
                'grade_max' => 2,
                'education_level' => 'primary',
                'topic' => 'Số và phép tính',
                'title' => 'Phép nhân, phép chia (Bảng nhân, chia 2 và 5)',
                'content' => 'Khái niệm phép nhân, phép chia. Bảng nhân 2, bảng nhân 5. Bảng chia 2, bảng chia 5. 1/2, 1/5 của một số.',
                'learning_outcomes' => ['Hiểu ý nghĩa phép nhân, chia', 'Thuộc bảng nhân chia 2 và 5'],
            ],
            [
                'subject' => 'Toán',
                'grade_min' => 2,
                'grade_max' => 2,
                'education_level' => 'primary',
                'topic' => 'Hình học và Đo lường',
                'title' => 'Đo lường (dm, m, km, kg, lít) và Hình học',
                'content' => 'Đơn vị đo độ dài: Đề-xi-mét (dm), Mét (m), Ki-lô-mét (km). Đơn vị đo khối lượng: Ki-lô-gam (kg). Đơn vị dung tích: Lít (l). Điểm, đoạn thẳng, đường gấp khúc, tính độ dài đường gấp khúc.',
                'learning_outcomes' => ['Chuyển đổi và tính toán với các đơn vị đo', 'Tính được độ dài đường gấp khúc'],
            ],

            // Lớp 3
            [
                'subject' => 'Toán',
                'grade_min' => 3,
                'grade_max' => 3,
                'education_level' => 'primary',
                'topic' => 'Số và phép tính',
                'title' => 'Bảng nhân, chia từ 3, 4, 6, 7, 8, 9 và Các số trong phạm vi 100 000',
                'content' => 'Hoàn thiện bảng nhân chia từ 2 đến 9. Các số trong phạm vi 10 000 và 100 000. Cộng, trừ, nhân, chia trong phạm vi 100 000.',
                'learning_outcomes' => ['Thuộc bảng nhân chia 1-9', 'Thực hiện 4 phép tính trong phạm vi 100 000'],
            ],
            [
                'subject' => 'Toán',
                'grade_min' => 3,
                'grade_max' => 3,
                'education_level' => 'primary',
                'topic' => 'Hình học và Đo lường',
                'title' => 'Chu vi hình tam giác, tứ giác, hình chữ nhật, hình vuông',
                'content' => 'Góc vuông, góc không vuông. Chu vi hình tam giác, hình tứ giác. Công thức tính chu vi hình chữ nhật, hình vuông. Diện tích hình chữ nhật, diện tích hình vuông (cm2).',
                'learning_outcomes' => ['Biết tính chu vi và diện tích hình chữ nhật, hình vuông'],
            ],

            // Lớp 4
            [
                'subject' => 'Toán',
                'grade_min' => 4,
                'grade_max' => 4,
                'education_level' => 'primary',
                'topic' => 'Số và phép tính',
                'title' => 'Phân số và các phép tính với phân số',
                'content' => 'Khái niệm phân số, phân số bằng nhau, rút gọn và quy đồng mẫu số. Cộng, trừ, nhân, chia phân số. Tìm phân số của một số.',
                'learning_outcomes' => ['Thành thạo rút gọn, quy đồng phân số', 'Thực hiện 4 phép tính với phân số'],
            ],
            [
                'subject' => 'Toán',
                'grade_min' => 4,
                'grade_max' => 4,
                'education_level' => 'primary',
                'topic' => 'Hình học và Đo lường',
                'title' => 'Góc, đường thẳng song song, vuông góc, hình bình hành, hình thoi',
                'content' => 'Góc nhọn, góc tù, góc bẹt. Hai đường thẳng vuông góc, song song. Diện tích hình bình hành, diện tích hình thoi. Đơn vị đo diện tích: dm2, m2, mm2.',
                'learning_outcomes' => ['Nhận biết tính chất hình bình hành, hình thoi', 'Tính diện tích hình bình hành, hình thoi'],
            ],

            // Lớp 5
            [
                'subject' => 'Toán',
                'grade_min' => 5,
                'grade_max' => 5,
                'education_level' => 'primary',
                'topic' => 'Số và phép tính',
                'title' => 'Số thập phân và các phép tính với số thập phân',
                'content' => 'Khái niệm số thập phân, hàng của số thập phân. Cộng, trừ, nhân, chia số thập phân. Tỉ số phần trăm và bài toán về tỉ số phần trăm.',
                'learning_outcomes' => ['Thành thạo tính toán với số thập phân', 'Giải bài toán tỉ số phần trăm thực tế'],
            ],
            [
                'subject' => 'Toán',
                'grade_min' => 5,
                'grade_max' => 5,
                'education_level' => 'primary',
                'topic' => 'Hình học và Đo lường',
                'title' => 'Hình tam giác, hình thang, hình tròn và Thể tích hình hộp',
                'content' => 'Diện tích hình tam giác, diện tích hình thang. Chu vi và diện tích hình tròn. Diện tích xung quanh, toàn phần và thể tích hình hộp chữ nhật, hình lập phương.',
                'learning_outcomes' => ['Tính diện tích hình tam giác, hình thang, hình tròn', 'Tính diện tích và thể tích hình hộp'],
            ],
            [
                'subject' => 'Toán',
                'grade_min' => 5,
                'grade_max' => 5,
                'education_level' => 'primary',
                'topic' => 'Toán chuyển động',
                'title' => 'Vận tốc, Quãng đường, Thời gian (Toán chuyển động đều)',
                'content' => 'Công thức tính vận tốc, quãng đường, thời gian (s = v * t). Hai chuyển động cùng chiều, ngược chiều.',
                'learning_outcomes' => ['Giải bài toán chuyển động đều và các bài toán thực tế'],
            ],

            // ==================== TIỂU HỌC: TIẾNG VIỆT ====================
            // Lớp 1
            [
                'subject' => 'Ngữ văn',
                'grade_min' => 1,
                'grade_max' => 1,
                'education_level' => 'primary',
                'topic' => 'Âm, vần và Tập đọc cơ bản',
                'title' => 'Bảng chữ cái, âm vần Tiếng Việt và ghép tiếng, từ',
                'content' => 'Nhận biết các nguyên âm, phụ âm, thanh điệu (huyền, sắc, hỏi, ngã, nặng). Ghép vần, đánh vần từ ngữ và câu văn ngắn.',
                'learning_outcomes' => ['Đọc đúng âm, vần, tiếng, từ ngữ', 'Viết đúng chính tả chữ cái và từ ngữ đơn giản'],
            ],
            // Lớp 2-5
            [
                'subject' => 'Ngữ văn',
                'grade_min' => 2,
                'grade_max' => 2,
                'education_level' => 'primary',
                'topic' => 'Từ và câu',
                'title' => 'Từ chỉ sự vật, hoạt động, đặc điểm và các kiểu câu',
                'content' => 'Phân biệt từ chỉ người, đồ vật, con vật, cây cối. Từ chỉ hoạt động, trạng thái, đặc điểm. Mẫu câu: Ai là gì? Ai làm gì? Ai thế nào?',
                'learning_outcomes' => ['Nhận biết và đặt câu theo các mẫu câu cơ bản', 'Mở rộng vốn từ ngữ'],
            ],
            [
                'subject' => 'Ngữ văn',
                'grade_min' => 3,
                'grade_max' => 3,
                'education_level' => 'primary',
                'topic' => 'Biện pháp tu từ',
                'title' => 'Biện pháp so sánh và nhân hóa trong Tiếng Việt',
                'content' => 'Nhận diện hình ảnh so sánh (từ so sánh: là, như, giống như). Nhận diện các cách nhân hóa. Tác dụng của so sánh và nhân hóa trong miêu tả.',
                'learning_outcomes' => ['Tìm và phân tích được hình ảnh so sánh, nhân hóa', 'Vận dụng viết câu có so sánh, nhân hóa'],
            ],
            [
                'subject' => 'Ngữ văn',
                'grade_min' => 4,
                'grade_max' => 4,
                'education_level' => 'primary',
                'topic' => 'Tập làm văn',
                'title' => 'Văn miêu tả đồ vật, cây cối, con vật',
                'content' => 'Cấu tạo bài văn miêu tả (Mở bài, Thân bài, Kết bài). Miêu tả bao quát và chi tiết. Quan sát bằng nhiều giác quan.',
                'learning_outcomes' => ['Lập dàn ý và viết đoạn văn/bài văn miêu tả sinh động'],
            ],
            [
                'subject' => 'Ngữ văn',
                'grade_min' => 5,
                'grade_max' => 5,
                'education_level' => 'primary',
                'topic' => 'Từ ngữ và Tập làm văn',
                'title' => 'Từ đồng nghĩa, trái nghĩa, đồng âm, nhiều nghĩa và Văn tả cảnh, tả người',
                'content' => 'Phân biệt từ đồng nghĩa, trái nghĩa, từ đồng âm và từ nhiều nghĩa. Dàn ý và phương pháp viết bài văn tả cảnh, văn tả người.',
                'learning_outcomes' => ['Phân biệt chính xác hiện tượng từ trong Tiếng Việt', 'Viết bài văn tả cảnh, tả người giàu cảm xúc'],
            ],

            // ==================== TIỂU HỌC & TRUNG HỌC: TIẾNG ANH ====================
            [
                'subject' => 'Tiếng Anh',
                'grade_min' => 1,
                'grade_max' => 2,
                'education_level' => 'primary',
                'topic' => 'Basic Vocabulary & Greetings',
                'title' => 'Alphabet, Numbers, Colors, Animals and Daily Greetings',
                'content' => 'English alphabet, numbers 1-20, colors (red, blue, green...), animals, family members. Greetings (Hello, Goodbye, How are you?).',
                'learning_outcomes' => ['Recognize alphabet and basic vocabulary', 'Use simple daily greetings'],
            ],
            [
                'subject' => 'Tiếng Anh',
                'grade_min' => 3,
                'grade_max' => 5,
                'education_level' => 'primary',
                'topic' => 'Grammar & Daily Topics',
                'title' => 'Present Simple, Likes & Dislikes, School Subjects and Daily Routines',
                'content' => 'Present simple tense (to be, ordinary verbs), Wh-questions (What, Where, When, Who). Describing hobbies, school subjects, daily routines, time.',
                'learning_outcomes' => ['Form simple sentences in Present Simple', 'Talk about daily routines and hobbies'],
            ],
            [
                'subject' => 'Tiếng Anh',
                'grade_min' => 6,
                'grade_max' => 9,
                'education_level' => 'secondary',
                'topic' => 'Tenses & Grammar Structures',
                'title' => 'Tenses (Present, Past, Future, Continuous), Comparisons & Modal Verbs',
                'content' => 'Present Continuous, Past Simple, Future Simple, Present Perfect. Comparative and Superlative adjectives. Modal verbs (can, must, should). Conditional Sentences Type 1.',
                'learning_outcomes' => ['Master fundamental English tenses', 'Use comparison and modal verbs accurately'],
            ],
            [
                'subject' => 'Tiếng Anh',
                'grade_min' => 10,
                'grade_max' => 12,
                'education_level' => 'high_school',
                'topic' => 'Advanced Grammar & Reading Comprehension',
                'title' => 'Passive Voice, Relative Clauses, Conditionals & Reported Speech',
                'content' => 'Passive Voice all tenses, Defining and Non-defining Relative Clauses, Conditional Sentences Type 1, 2, 3 and Mixed, Reported Speech, Subjunctive mood, Inversion.',
                'learning_outcomes' => ['Master advanced grammatical structures', 'Prepare for national high school graduation exam'],
            ],

            // ==================== THCS & THPT: TOÁN HỌC ====================
            // Lớp 6
            [
                'subject' => 'Toán',
                'grade_min' => 6,
                'grade_max' => 6,
                'education_level' => 'secondary',
                'topic' => 'Số học và Hình học trực quan',
                'title' => 'Số tự nhiên, Số nguyên, Phân số và Hình học trực quan',
                'content' => 'Tập hợp số tự nhiên, ước và bội, số nguyên tố. Tập hợp số nguyên, cộng trừ nhân chia số nguyên. Phân số, số thập phân. Tam giác đều, hình vuông, lục giác đều, hình chữ nhật, hình thoi.',
                'learning_outcomes' => ['Tính toán thành thạo với số nguyên và phân số', 'Nhận biết các hình hình học trực quan'],
            ],
            // Lớp 7
            [
                'subject' => 'Toán',
                'grade_min' => 7,
                'grade_max' => 7,
                'education_level' => 'secondary',
                'topic' => 'Đại số và Hình học',
                'title' => 'Số hữu tỉ, Số thực, Biểu thức đại số và Tam giác bằng nhau',
                'content' => 'Số hữu tỉ, số vô tỉ, số thực. Tỉ lệ thức và dãy tỉ số bằng nhau. Biểu thức đại số, đa thức một biến. Hai góc đối đỉnh, định lý đường thẳng song song. Các trường hợp bằng nhau của tam giác (c-c-c, c-g-c, g-c-g).',
                'learning_outcomes' => ['Giải bài toán số thực, tỉ lệ thức', 'Chứng minh hai tam giác bằng nhau'],
            ],
            // Lớp 8
            [
                'subject' => 'Toán',
                'grade_min' => 8,
                'grade_max' => 8,
                'education_level' => 'secondary',
                'topic' => 'Đại số và Hình học',
                'title' => 'Hằng đẳng thức đáng nhớ, Phân thức đại số và Định lý Thalès, Tứ giác',
                'content' => '7 hằng đẳng thức đáng nhớ, phân tích đa thức thành nhân tử. Phân thức đại số và các phép tính. Tứ giác (hình thang cân, hình bình hành, hình chữ nhật, hình thoi, hình vuông). Định lý Thalès và tam giác đồng dạng.',
                'learning_outcomes' => ['Thành thạo biến đổi hằng đẳng thức và phân thức', 'Chứng minh hình học tứ giác và tam giác đồng dạng'],
            ],
            // Lớp 9
            [
                'subject' => 'Toán',
                'grade_min' => 9,
                'grade_max' => 9,
                'education_level' => 'secondary',
                'topic' => 'Đại số và Hình học',
                'title' => 'Căn bậc hai, Hệ phương trình bậc nhất, Hàm số bậc nhất và Đường tròn',
                'content' => 'Căn bậc hai, căn bậc ba và rút gọn biểu thức chứa căn. Phương trình và hệ hai phương trình bậc nhất hai ẩn. Hàm số bậc nhất y = ax + b, hàm số bậc hai y = ax2 và phương trình bậc hai (Viète). Hệ thức lượng trong tam giác vuông, đường tròn và góc với đường tròn.',
                'learning_outcomes' => ['Giải hệ phương trình và phương trình bậc hai', 'Giải bài toán hình học đường tròn luyện thi vào 10'],
            ],
            // Lớp 10
            [
                'subject' => 'Toán',
                'grade_min' => 10,
                'grade_max' => 10,
                'education_level' => 'high_school',
                'topic' => 'Đại số & Hình học',
                'title' => 'Mệnh đề, Tập hợp, Bất phương trình, Vectơ và Hệ thức lượng trong tam giác',
                'content' => 'Mệnh đề và tập hợp. Bất phương trình và hệ bất phương trình bậc nhất hai ẩn. Hàm số bậc hai và tam thức bậc hai. Hệ thức lượng trong tam giác (Định lý sin, cosin, công thức diện tích). Vectơ và các phép toán vectơ, tích vô hướng của hai vectơ.',
                'learning_outcomes' => ['Giải bất phương trình và hàm số bậc hai', 'Vận dụng tích vô hướng vectơ và hệ thức lượng tam giác'],
            ],
            // Lớp 11
            [
                'subject' => 'Toán',
                'grade_min' => 11,
                'grade_max' => 11,
                'education_level' => 'high_school',
                'topic' => 'Giải tích & Hình học không gian',
                'title' => 'Hàm số lượng giác, Dãy số - Cấp số, Giới hạn, Đạo hàm & Quan hệ song song, vuông góc',
                'content' => 'Hàm số lượng giác và phương trình lượng giác cơ bản. Dãy số, cấp số cộng, cấp số nhân. Giới hạn dãy số và giới hạn hàm số. Đạo hàm và ứng dụng đạo hàm. Đường thẳng và mặt phẳng song song, vuông góc trong không gian.',
                'learning_outcomes' => ['Giải phương trình lượng giác, tính giới hạn và đạo hàm', 'Chứng minh quan hệ song song và vuông góc trong không gian'],
            ],
            // Lớp 12
            [
                'subject' => 'Toán',
                'grade_min' => 12,
                'grade_max' => 12,
                'education_level' => 'high_school',
                'topic' => 'Giải tích & Hình học không gian Oxyz',
                'title' => 'Khảo sát hàm số, Mũ - Logarit, Nguyên hàm - Tích phân & Tọa độ Oxyz',
                'content' => 'Ứng dụng đạo hàm để khảo sát và vẽ đồ thị hàm số (tính đơn điệu, cực trị, GTLN-GTNN, tiệm cận). Hàm số lũy thừa, hàm số mũ và logarit, phương trình bất phương trình mũ - logarit. Nguyên hàm, tích phân và ứng dụng tính diện tích, thể tích. Khối đa diện, khối tròn xoay. Phương pháp tọa độ trong không gian Oxyz (mặt cầu, mặt phẳng, đường thẳng).',
                'learning_outcomes' => ['Thành thạo khảo sát hàm số, giải tích phân và hình học Oxyz', 'Ôn luyện thi tốt nghiệp THPT Quốc gia môn Toán'],
            ],

            // ==================== THCS: KHOA HỌC TỰ NHIÊN ====================
            [
                'subject' => 'Khoa học tự nhiên',
                'grade_min' => 6,
                'grade_max' => 6,
                'education_level' => 'secondary',
                'topic' => 'Chất và sự biến đổi, Vật sống, Năng lượng',
                'title' => 'Các phép đo, Các thể của chất, Tế bào - Đơn vị cơ bản của sự sống, Lực và Năng lượng',
                'content' => 'Đo lường cơ bản (chiều dài, khối lượng, thời gian, nhiệt độ). Chất tinh khiết và hỗn hợp, dung dịch. Tế bào thực vật, tế bào động vật, cơ thể đơn bào và đa bào. Đa dạng thế giới sống. Lực tiếp xúc, lực không tiếp xúc, năng lượng và sự bảo toàn năng lượng.',
                'learning_outcomes' => ['Nhận biết cấu trúc tế bào và đa dạng sinh học', 'Hiểu bản chất của lực, năng lượng và các trạng thái của chất'],
            ],
            [
                'subject' => 'Khoa học tự nhiên',
                'grade_min' => 7,
                'grade_max' => 7,
                'education_level' => 'secondary',
                'topic' => 'Nguyên tử, Trao đổi chất, Âm thanh và Ánh sáng',
                'title' => 'Nguyên tử, Bảng tuần hoàn, Trao đổi chất & Chuyển hóa năng lượng, Tốc độ & Âm thanh, Ánh sáng',
                'content' => 'Cấu tạo nguyên tử, bảng tuần hoàn các nguyên tố hóa học, liên kết hóa học. Quang hợp, hô hấp tế bào ở sinh vật. Tốc độ chuyển động, sóng âm và độ to, độ cao của âm. Định luật phản xạ ánh sáng.',
                'learning_outcomes' => ['Hiểu cấu tạo nguyên tử và bảng tuần hoàn', 'Phân tích quá trình quang hợp, hô hấp và định luật phản xạ ánh sáng'],
            ],
            [
                'subject' => 'Khoa học tự nhiên',
                'grade_min' => 8,
                'grade_max' => 8,
                'education_level' => 'secondary',
                'topic' => 'Phản ứng hóa học, Điện & Nhiệt, Cơ thể người',
                'title' => 'Biến đổi hóa học, Acid - Base - Muối, Áp suất, Dòng điện & Hệ cơ quan ở người',
                'content' => 'Định luật bảo toàn khối lượng, mol và tỉ khối chất khí. Dung dịch Acid, Base, pH, Muối, Oxide. Khối lượng riêng và áp suất. Hiện tượng nhiễm điện, mạch điện, tác dụng của dòng điện. Cấu tạo và chức năng các hệ cơ quan trong cơ thể người.',
                'learning_outcomes' => ['Viết phương trình hóa học và tính toán theo phương trình', 'Hiểu về áp suất, dòng điện và giải phẫu cơ thể người'],
            ],
            [
                'subject' => 'Khoa học tự nhiên',
                'grade_min' => 9,
                'grade_max' => 9,
                'education_level' => 'secondary',
                'topic' => 'Kim loại, Hợp chất hữu cơ, Năng lượng điện & Di truyền học',
                'title' => 'Kim loại & Phi kim, Hóa học hữu cơ (Hydrocarbon, Alcohol, Acid acetic), Điện năng & Di truyền - Biến dị',
                'content' => 'Dãy hoạt động hóa học của kim loại. Hydrocarbon (Methane, Ethylene, Acetylene), Rượu etylic và Axit axetic. Định luật Ohm, công suất điện, định luật Joule-Lenz. Khúc xạ ánh sáng, thấu kính hội tụ/phân kì. Các quy luật di truyền của Mendel, ADN, ARN và Protein.',
                'learning_outcomes' => ['Giải bài tập định luật Ohm và thấu kính', 'Nắm vững quy luật di truyền Mendel và cấu trúc ADN'],
            ],

            // ==================== THPT: VẬT LÍ ====================
            [
                'subject' => 'Vật lí',
                'grade_min' => 10,
                'grade_max' => 10,
                'education_level' => 'high_school',
                'topic' => 'Cơ học',
                'title' => 'Động học chất điểm, Động lực học chất điểm (Định luật Newton) & Năng lượng',
                'content' => 'Chuyển động thẳng đều, biến đổi đều, rơi tự do. 3 định luật Newton, các lực cơ học (hấp dẫn, đàn hồi, ma sát). Động lượng, định luật bảo toàn động lượng. Công, công suất, động năng, thế năng, cơ năng và định luật bảo toàn cơ năng.',
                'learning_outcomes' => ['Vận dụng các phương trình chuyển động và định luật Newton', 'Giải bài toán bảo toàn cơ năng và động lượng'],
            ],
            [
                'subject' => 'Vật lí',
                'grade_min' => 11,
                'grade_max' => 11,
                'education_level' => 'high_school',
                'topic' => 'Dao động & Sóng, Điện từ trường',
                'title' => 'Dao động điều hòa, Sóng cơ, Điện trường & Dòng điện không đổi',
                'content' => 'Phương trình dao động điều hòa (con lắc lò xo, con lắc đơn). Năng lượng trong dao động điều hòa. Sóng cơ và sự truyền sóng, giao thoa sóng, sóng dừng. Điện tích, định luật Coulomb, điện trường, điện thế, tụ điện. Định luật Ohm cho toàn mạch.',
                'learning_outcomes' => ['Giải bài toán dao động điều hòa và giao thoa sóng', 'Tính toán mạch điện chứa nguồn và tụ điện'],
            ],
            [
                'subject' => 'Vật lí',
                'grade_min' => 12,
                'grade_max' => 12,
                'education_level' => 'high_school',
                'topic' => 'Vật lí nhiệt, Khí lý tưởng & Vật lí hạt nhân',
                'title' => 'Nhiệt học, Thuyết động học phân tử chất khí, Từ trường & Hạt nhân nguyên tử',
                'content' => 'Nhiệt độ, nhiệt dung riêng, nhiệt nóng chảy, nhiệt hóa hơi. Các định luật chất khí (Boyle, Charles), phương trình trạng thái khí lý tưởng. Từ trường, cảm ứng điện từ. Cấu tạo hạt nhân, độ hụt khối, năng lượng liên kết, phóng xạ và phản ứng hạt nhân.',
                'learning_outcomes' => ['Giải bài toán nhiệt học và phương trình khí lý tưởng', 'Tính năng lượng phản ứng hạt nhân và độ phóng xạ'],
            ],

            // ==================== THPT: HÓA HỌC ====================
            [
                'subject' => 'Hóa học',
                'grade_min' => 10,
                'grade_max' => 10,
                'education_level' => 'high_school',
                'topic' => 'Cấu tạo chất & Phản ứng oxi hóa - khử',
                'title' => 'Cấu tạo nguyên tử, Bảng tuần hoàn, Liên kết hóa học & Tốc độ phản ứng',
                'content' => 'Hạt nhân, lớp vỏ electron, cấu hình electron. Quy luật biến đổi trong bảng tuần hoàn. Liên kết ion, liên kết cộng hóa trị, liên kết hydrogen. Số oxi hóa, cân bằng phản ứng oxi hóa - khử. Tốc độ phản ứng và hằng số cân bằng.',
                'learning_outcomes' => ['Viết cấu hình electron và xác định vị trí trong bảng tuần hoàn', 'Cân bằng phản ứng oxi hóa - khử bằng phương pháp thăng bằng electron'],
            ],
            [
                'subject' => 'Hóa học',
                'grade_min' => 11,
                'grade_max' => 11,
                'education_level' => 'high_school',
                'topic' => 'Cân bằng hóa học, Hóa học vô cơ & Hóa học hữu cơ đại cương',
                'title' => 'Sự điện li, pH, Nitrogen & Sulfur, Đại cương hóa học hữu cơ, Hydrocarbon',
                'content' => 'Cân bằng trong dung dịch nước, thuyết Bronsted-Lowry, tính pH. Đơn chất và hợp chất của Nitrogen, Sulfur. Công thức phân tử, cấu tạo phân tử hợp chất hữu cơ. Alkane, Alkene, Alkyne, Arene.',
                'learning_outcomes' => ['Tính pH của dung dịch acid, base', 'Nắm vững tính chất hóa học đặc trưng của Hydrocarbon'],
            ],
            [
                'subject' => 'Hóa học',
                'grade_min' => 12,
                'grade_max' => 12,
                'education_level' => 'high_school',
                'topic' => 'Hóa học hữu cơ nâng cao & Kim loại',
                'title' => 'Ester - Lipid, Carbohydrate, Amine - Amino acid - Peptide, Kim loại chuyển tiếp',
                'content' => 'Este, chất béo, xà phòng. Glucose, Fructose, Saccharose, Tinh bột, Cellulose. Amine, Amino acid, Peptide, Protein, Enzyme. Đại cương kim loại, dãy điện hóa, phương pháp điều chế kim loại và ăn mòn kim loại. Phức chất và kim loại chuyển tiếp.',
                'learning_outcomes' => ['Giải bài toán thủy phân este, peptit và carbohydrate', 'Vận dụng dãy điện hóa kim loại và chống ăn mòn'],
            ],

            // ==================== THPT: SINH HỌC ====================
            [
                'subject' => 'Sinh học',
                'grade_min' => 10,
                'grade_max' => 10,
                'education_level' => 'high_school',
                'topic' => 'Sinh học tế bào & Vi sinh vật',
                'title' => 'Thành phần hóa học của tế bào, Cấu trúc tế bào, Chuyển hóa vật chất & Phân bào',
                'content' => 'Các đại phân tử sinh học (Carbohydrate, Lipid, Protein, Axit nucleic). Cấu tạo màng sinh chất, bào quan. Vận chuyển các chất qua màng. Chu kỳ tế bào, nguyên phân, giảm phân. Vi sinh vật và virus.',
                'learning_outcomes' => ['Nhận biết cấu trúc và chức năng các bào quan tế bào', 'Phân biệt cơ chế nguyên phân và giảm phân'],
            ],
            [
                'subject' => 'Sinh học',
                'grade_min' => 11,
                'grade_max' => 11,
                'education_level' => 'high_school',
                'topic' => 'Sinh học cơ thể',
                'title' => 'Trao đổi chất & Năng lượng ở thực vật, động vật, Cảm ứng, Sinh trưởng & Sinh sản',
                'content' => 'Quang hợp và hô hấp ở thực vật C3, C4, CAM. Tiêu hóa, tuần hoàn, hô hấp, bài tiết ở động vật. Cảm ứng ở thực vật (hướng động, ứng động) và động vật (hệ thần kinh, phản xạ). Sinh trưởng, phát triển và sinh sản ở sinh vật.',
                'learning_outcomes' => ['Hiểu sâu cơ chế quang hợp, tuần hoàn và thần kinh', 'Phân tích các hình thức sinh sản ở sinh vật'],
            ],
            [
                'subject' => 'Sinh học',
                'grade_min' => 12,
                'grade_max' => 12,
                'education_level' => 'high_school',
                'topic' => 'Di truyền học, Tiến hóa & Sinh thái học',
                'title' => 'Cơ chế di truyền và biến dị, Quy luật di truyền, Di truyền quần thể & Sinh thái học',
                'content' => 'Nhân đôi ADN, phiên mã, dịch mã, đột biến gen. Nhiễm sắc thể và đột biến NST. Các quy luật di truyền Mendel, Morgan (liên kết gen, hoán vị gen), tương tác gen. Di truyền quần thể (Định luật Hardy-Weinberg). Thuyết tiến hóa hiện đại. Quần thể, quần xã, hệ sinh thái và sinh quyển.',
                'learning_outcomes' => ['Giải bài tập di truyền phân tử và quy luật di truyền', 'Vận dụng định luật Hardy-Weinberg và nguyên lý sinh thái học'],
            ],

            // ==================== THCS & THPT: LỊCH SỬ & ĐỊA LÍ ====================
            [
                'subject' => 'Lịch sử và Địa lí',
                'grade_min' => 6,
                'grade_max' => 9,
                'education_level' => 'secondary',
                'domain' => 'Lịch sử',
                'topic' => 'Lịch sử Việt Nam và Thế giới',
                'title' => 'Lịch sử Việt Nam từ thời nguyên thủy đến hiện đại & Lịch sử văn minh thế giới',
                'content' => 'Thời tiền sử, các quốc gia cổ đại trên đất nước Việt Nam (Văn Lang, Âu Lạc, Chăm Pa, Phù Nam). Các cuộc kháng chiến chống ngoại xâm qua các triều đại Ngô, Đinh, Tiền Lê, Lý, Trần, Lê Sơ. Cách mạng tháng Tám 1945 và các cuộc kháng chiến vệ quốc.',
                'learning_outcomes' => ['Nắm các mốc lịch sử trọng đại của dân tộc Việt Nam', 'Phân tích ý nghĩa lịch sử các triều đại'],
            ],
            [
                'subject' => 'Lịch sử và Địa lí',
                'grade_min' => 6,
                'grade_max' => 9,
                'education_level' => 'secondary',
                'domain' => 'Địa lí',
                'topic' => 'Địa lí tự nhiên và Dân cư',
                'title' => 'Bản đồ, Địa hình, Khí hậu, Thủy văn & Dân cư - Kinh tế Việt Nam',
                'content' => 'Hệ thống kinh vĩ tuyến, tọa độ địa lí và bản đồ. Cấu tạo Trái Đất, các mảng kiến tạo. Khí hậu nhiệt đới gió mùa ẩm của Việt Nam, sông ngòi và cảnh quan tự nhiên. Cơ cấu dân số, phân bố dân cư và các vùng kinh tế trọng điểm Việt Nam.',
                'learning_outcomes' => ['Đọc và khai thác thông tin từ Atlat địa lí', 'Hiểu đặc trưng tự nhiên và kinh tế xã hội Việt Nam'],
            ],
            [
                'subject' => 'Lịch sử',
                'grade_min' => 10,
                'grade_max' => 12,
                'education_level' => 'high_school',
                'topic' => 'Lịch sử chuyên sâu',
                'title' => 'Các nền văn minh thế giới, Lịch sử Việt Nam hiện đại & Quan hệ quốc tế',
                'content' => 'Văn minh Đông phương và Tây phương cổ - trung đại. Lịch sử Việt Nam từ 1858 đến nay (Phong trào Cần Vương, Đảng Cộng sản Việt Nam ra đời, Kháng chiến chống Pháp và chống Mỹ, Đổi mới). Trật tự thế giới hai cực Ianta và xu thế toàn cầu hóa.',
                'learning_outcomes' => ['Đánh giá các sự kiện lịch sử theo quan điểm biện chứng', 'Chuẩn bị kiến thức thi tốt nghiệp THPT môn Lịch sử'],
            ],
            [
                'subject' => 'Địa lí',
                'grade_min' => 10,
                'grade_max' => 12,
                'education_level' => 'high_school',
                'topic' => 'Địa lí tự nhiên & Địa lí kinh tế - xã hội',
                'title' => 'Quy luật địa lý tổng hợp, Địa lí các ngành kinh tế & 7 Vùng kinh tế Việt Nam',
                'content' => 'Quy luật đai cao, quy luật địa đới và phi địa đới. Địa lí nông nghiệp, công nghiệp, dịch vụ. Phân tích chi tiết 7 vùng kinh tế Việt Nam: Trung du và miền núi Bắc Bộ, Đồng bằng sông Hồng, Bắc Trung Bộ, Duyên hải Nam Trung Bộ, Tây Nguyên, Đông Nam Bộ, Đồng bằng sông Cửu Long. Vấn đề chủ quyền biển đảo (Hoàng Sa, Trường Sa).',
                'learning_outcomes' => ['Phân tích bảng số liệu, biểu đồ địa lí', 'Nắm vững đặc điểm kinh tế xã hội của 7 vùng kinh tế'],
            ],

            // ==================== GIÁO DỤC CÔNG DÂN ====================
            [
                'subject' => 'Giáo dục công dân',
                'grade_min' => 6,
                'grade_max' => 12,
                'education_level' => 'secondary',
                'topic' => 'Đạo đức và Pháp luật',
                'title' => 'Chuẩn mực đạo đức, Pháp luật và Đời sống công dân',
                'content' => 'Các chuẩn mực đạo đức xã hội (trung thực, tôn trọng kỉ luật, yêu thương con người). Quyền và nghĩa vụ cơ bản của công dân. Pháp luật về quyền con người, quyền bình đẳng giới, bảo vệ môi trường và an toàn thông tin mạng.',
                'learning_outcomes' => ['Nhận thức và thực hiện đúng quyền, nghĩa vụ công dân', 'Có thái độ và hành vi chuẩn mực trong cuộc sống'],
            ],
        ];

        foreach ($units as $unit) {
            $docId = $documents[$unit['subject']]->id ?? $documents['Toán']->id;
            CurriculumUnit::create(array_merge($unit, [
                'document_id' => $docId,
                'type' => 'curriculum_content',
                'is_verified' => true,
            ]));
        }
    }
}
