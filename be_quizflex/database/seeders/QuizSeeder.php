<?php

namespace Database\Seeders;

use App\Models\Grade;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuizSeeder extends Seeder
{
    /**
     * Khởi tạo các bài Quiz phong phú cho từng người dùng, kết hợp (mix) câu hỏi
     * giữa Ngân hàng câu hỏi công khai và Kho câu hỏi cá nhân của tác giả.
     */
    public function run(): void
    {
        $users = User::all()->keyBy('email');
        if ($users->isEmpty()) {
            $this->command->warn('Chưa có người dùng nào. Vui lòng chạy UserSeeder trước!');
            return;
        }

        // Định nghĩa danh sách các Quiz mẫu đặc trưng cho từng người dùng
        $quizzesDefinition = [
            // =========================================================================
            // 1. THẦY HOÀNG MINH ĐỨC (TOÁN HỌC)
            // =========================================================================
            [
                'author_email' => 'thay.duchoang@quizflex.vn',
                'title' => 'Đề thi thử Tốt nghiệp THPT 2026 - Môn Toán (Đề số 01)',
                'description' => 'Bộ đề chuẩn cấu trúc đề thi tốt nghiệp THPT năm 2026 môn Toán do Thầy Hoàng Minh Đức biên soạn, bao gồm chuyên đề Khảo sát hàm số, Hình học Oxyz và Tích phân.',
                'subject_code' => 'math',
                'grade_code' => 'grade_12',
                'difficulty' => 'medium',
                'time_limit_seconds' => 3000,
                'category' => 'Toán học',
                'tag' => 'THPT Quốc Gia',
                'icon' => 'Calculator',
                'badge' => 'Chuyên Toán',
            ],
            [
                'author_email' => 'thay.duchoang@quizflex.vn',
                'title' => 'Chuyên đề Nâng cao: Khảo sát hàm số & Cực trị Hình học',
                'description' => 'Bài tập rèn luyện kỹ năng giải nhanh các bài toán vận dụng và vận dụng cao môn Toán lớp 12.',
                'subject_code' => 'math',
                'grade_code' => 'grade_12',
                'difficulty' => 'hard',
                'time_limit_seconds' => 2700,
                'category' => 'Toán học',
                'tag' => 'Vận dụng cao',
                'icon' => 'TrendingUp',
                'badge' => 'VIP Pro',
            ],

            // =========================================================================
            // 2. CÔ PHẠM QUỲNH NGA (TIẾNG ANH)
            // =========================================================================
            [
                'author_email' => 'co.quynhnga@quizflex.vn',
                'title' => 'Luyện đề Chuyên sâu: Ngữ pháp & Từ vựng Tiếng Anh 12',
                'description' => 'Tuyển tập các câu hỏi ngữ pháp nâng cao, đảo ngữ, câu điều kiện hỗn hợp và collocation trọng tâm kì thi THPT Quốc Gia.',
                'subject_code' => 'english',
                'grade_code' => 'grade_12',
                'difficulty' => 'medium',
                'time_limit_seconds' => 2400,
                'category' => 'Tiếng Anh',
                'tag' => 'Grammar & Vocab',
                'icon' => 'Languages',
                'badge' => 'IELTS/THPT',
            ],
            [
                'author_email' => 'co.quynhnga@quizflex.vn',
                'title' => 'Đề kiểm tra Định kỳ: Phrasal Verbs & Collocations Master',
                'description' => 'Thử thách 100% thành thạo cụm động từ và kết hợp từ thông dụng trong các bài đọc hiểu học thuật.',
                'subject_code' => 'english',
                'grade_code' => 'grade_12',
                'difficulty' => 'hard',
                'time_limit_seconds' => 1800,
                'category' => 'Tiếng Anh',
                'tag' => 'Advanced English',
                'icon' => 'Award',
                'badge' => 'Master',
            ],

            // =========================================================================
            // 3. THẦY TRẦN QUỐC BẢO (VẬT LÝ)
            // =========================================================================
            [
                'author_email' => 'thay.quocbao@quizflex.vn',
                'title' => 'Tổng ôn Vật Lý 12: Dao động cơ & Dòng điện xoay chiều',
                'description' => 'Bài kiểm tra đánh giá năng lực toàn diện kiến thức học kỳ 1 môn Vật lý lớp 12.',
                'subject_code' => 'physics',
                'grade_code' => 'grade_12',
                'difficulty' => 'medium',
                'time_limit_seconds' => 3000,
                'category' => 'Vật lý',
                'tag' => 'Vật Lý 12',
                'icon' => 'Atom',
                'badge' => 'Vật Lý Pro',
            ],
            [
                'author_email' => 'thay.quocbao@quizflex.vn',
                'title' => 'Đề luyện thi Cấp tốc: Sóng ánh sáng & Hạt nhân nguyên tử',
                'description' => 'Bộ câu hỏi chuẩn định dạng mới bám sát các dạng bài tập thực nghiệm vật lý.',
                'subject_code' => 'physics',
                'grade_code' => 'grade_12',
                'difficulty' => 'easy',
                'time_limit_seconds' => 2400,
                'category' => 'Vật lý',
                'tag' => 'Ôn thi cấp tốc',
                'icon' => 'Zap',
                'badge' => 'Cơ bản - Vận dụng',
            ],

            // =========================================================================
            // 4. LÊ THANH HÀ (HÓA HỌC & SINH HỌC)
            // =========================================================================
            [
                'author_email' => 'lethanhha@gmail.com',
                'title' => 'Chuyên đề Hóa học 12: Este - Lipit & Cacbohiđrat',
                'description' => 'Hệ thống câu hỏi lý thuyết và bài toán xà phòng hóa este đặc sắc.',
                'subject_code' => 'chemistry',
                'grade_code' => 'grade_12',
                'difficulty' => 'medium',
                'time_limit_seconds' => 2400,
                'category' => 'Hóa học',
                'tag' => 'Hóa Hữu cơ',
                'icon' => 'FlaskConical',
                'badge' => 'Hóa Học 12',
            ],
            [
                'author_email' => 'lethanhha@gmail.com',
                'title' => 'Sinh học 12: Cơ chế di truyền & Biến dị cấp độ phân tử',
                'description' => 'Đề kiểm tra trọng tâm về ADN, ARN, Protein và quy luật di truyền Mendel.',
                'subject_code' => 'biology',
                'grade_code' => 'grade_12',
                'difficulty' => 'medium',
                'time_limit_seconds' => 2400,
                'category' => 'Sinh học',
                'tag' => 'Di truyền học',
                'icon' => 'Dna',
                'badge' => 'Sinh Học 12',
            ],

            // =========================================================================
            // 5. NGUYỄN DUY ANH (TIN HỌC / CNTT)
            // =========================================================================
            [
                'author_email' => 'nguyenduyanh@gmail.com',
                'title' => 'Kiểm tra Kiến thức: Cơ sở dữ liệu SQL & Giải thuật lập trình',
                'description' => 'Đánh giá kỹ năng lập trình cấu trúc dữ liệu, thuật toán sắp xếp và truy vấn cơ sở dữ liệu quan hệ.',
                'subject_code' => 'informatics',
                'grade_code' => 'grade_12',
                'difficulty' => 'medium',
                'time_limit_seconds' => 2700,
                'category' => 'Tin học',
                'tag' => 'Lập trình & CSDL',
                'icon' => 'Laptop',
                'badge' => 'IT Expert',
            ],

            // =========================================================================
            // 6. NGUYỄN KHÁNH LINH (KỸ NĂNG & TIẾNG ANH)
            // =========================================================================
            [
                'author_email' => 'nguyenkhanhlinh@gmail.com',
                'title' => 'Tự luyện Kỹ năng: Quản lý thời gian & Tư duy phản biện',
                'description' => 'Trắc nghiệm rèn luyện phương pháp học tập hiệu quả, Pomodoro và kỹ năng đọc hiểu học thuật.',
                'subject_code' => 'skills',
                'grade_code' => 'other_gen',
                'difficulty' => 'easy',
                'time_limit_seconds' => 1200,
                'category' => 'Kỹ năng sống',
                'tag' => 'Kỹ năng mềm',
                'icon' => 'Lightbulb',
                'badge' => 'Soft Skills',
            ],

            // =========================================================================
            // 7. PHAN MINH KHÔI (HÓA - SINH NÂNG CAO)
            // =========================================================================
            [
                'author_email' => 'phanminhkhoi@gmail.com',
                'title' => 'Luyện tập Khối B: Kim loại kiềm & Di truyền quần thể',
                'description' => 'Bộ câu hỏi tự luyện của học sinh chuyên Hóa - Sinh chuẩn bị cho kỳ thi tuyển sinh Đại học Y Dược.',
                'subject_code' => 'chemistry',
                'grade_code' => 'grade_12',
                'difficulty' => 'hard',
                'time_limit_seconds' => 2400,
                'category' => 'Hóa học',
                'tag' => 'Khối B00',
                'icon' => 'Flame',
                'badge' => 'Luyện thi ĐH',
            ],

            // =========================================================================
            // 8. VŨ MINH QUÂN (HỌC SINH 12A1)
            // =========================================================================
            [
                'author_email' => 'vuminhquan@gmail.com',
                'title' => 'Tự học Nhóm 12A1: Toán mũ - Logarit & Sóng âm Vật lý',
                'description' => 'Đề ôn tập tuần của nhóm học tập lớp 12A1.',
                'subject_code' => 'math',
                'grade_code' => 'grade_12',
                'difficulty' => 'easy',
                'time_limit_seconds' => 1800,
                'category' => 'Toán học',
                'tag' => 'Nhóm 12A1',
                'icon' => 'Users',
                'badge' => 'Tự học',
            ],

            // =========================================================================
            // 9. ĐẶNG THÙY LINH (HỌC SINH 12A3 - KHXH)
            // =========================================================================
            [
                'author_email' => 'dangthuylinh@gmail.com',
                'title' => 'Tổng kết Văn học Hiện đại & Lịch sử Kháng chiến 1945-1975',
                'description' => 'Bộ câu hỏi ôn tập chuyên đề Ngữ văn và Lịch sử Việt Nam giai đoạn cách mạng.',
                'subject_code' => 'literature',
                'grade_code' => 'grade_12',
                'difficulty' => 'medium',
                'time_limit_seconds' => 2400,
                'category' => 'Ngữ văn',
                'tag' => 'Khối C00',
                'icon' => 'BookOpen',
                'badge' => 'Khoa học Xã hội',
            ],

            // =========================================================================
            // 10. ADMIN TRẦN HOÀNG LONG (HỆ THỐNG)
            // =========================================================================
            [
                'author_email' => 'admin@quizflex.vn',
                'title' => 'Đề Khảo sát Toàn diện Kiến thức Khoa học & Công nghệ Quốc gia',
                'description' => 'Bài thi tổng hợp kiến thức đa lĩnh vực: Tin học, An toàn mạng, Kỹ năng số và Tư duy logic dành cho cộng đồng học viên QuizFlex.',
                'subject_code' => 'informatics',
                'grade_code' => 'other_gen',
                'difficulty' => 'medium',
                'time_limit_seconds' => 3600,
                'category' => 'Tổng hợp',
                'tag' => 'Toàn quốc',
                'icon' => 'Globe',
                'badge' => 'Official QuizFlex',
            ],
        ];

        $createdQuizzes = [];

        foreach ($quizzesDefinition as $quizDef) {
            $author = $users->get($quizDef['author_email']);
            if (!$author) {
                continue;
            }

            $subject = Subject::where('code', $quizDef['subject_code'])->first() ?? Subject::first();
            $grade = Grade::where('code', $quizDef['grade_code'])->first();

            $quiz = Quiz::updateOrCreate(
                [
                    'user_id' => $author->id,
                    'title' => $quizDef['title'],
                ],
                [
                    'description' => $quizDef['description'],
                    'category' => $quizDef['category'],
                    'subject_id' => $subject->id,
                    'grade_id' => $grade?->id,
                    'education_level_id' => $grade?->education_level_id ?? $subject->education_level_id,
                    'tag' => $quizDef['tag'],
                    'difficulty' => $quizDef['difficulty'],
                    'creation_mode' => 'manual',
                    'status' => 'published',
                    'review_status' => 'approved',
                    'is_public' => true,
                    'time_limit_seconds' => $quizDef['time_limit_seconds'],
                    'icon' => $quizDef['icon'],
                    'badge' => $quizDef['badge'],
                ]
            );

            // =========================================================================
            // MIX CÂU HỎI: LẤY CÂU HỎI TỪ NGÂN HÀNG + KHO CÁ NHÂN CỦA TÁC GIẢ
            // =========================================================================
            // 1. Lấy câu hỏi cá nhân của tác giả
            $personalQuestions = Question::where('user_id', $author->id)
                ->whereNull('origin_question_id')
                ->where('is_public', false)
                ->get();

            // 2. Lấy câu hỏi công khai từ Ngân hàng câu hỏi
            $bankQuestionsQuery = Question::where('is_public', true)
                ->where('subject_id', $subject->id);

            if ($bankQuestionsQuery->count() < 4) {
                $bankQuestions = Question::where('is_public', true)->inRandomOrder()->limit(6)->get();
            } else {
                $bankQuestions = $bankQuestionsQuery->inRandomOrder()->limit(6)->get();
            }

            $mixedQuestions = collect();

            // Đưa câu hỏi cá nhân vào trước (2-4 câu)
            if ($personalQuestions->isNotEmpty()) {
                $mixedQuestions = $mixedQuestions->merge($personalQuestions->take(4));
            }

            // Đưa câu hỏi từ Ngân hàng vào (4-6 câu)
            if ($bankQuestions->isNotEmpty()) {
                $mixedQuestions = $mixedQuestions->merge($bankQuestions->take(6));
            }

            // Nếu vẫn ít hơn 5 câu, bổ sung thêm câu hỏi công khai bất kỳ
            if ($mixedQuestions->count() < 5) {
                $fallbackBank = Question::where('is_public', true)
                    ->whereNotIn('id', $mixedQuestions->pluck('id'))
                    ->limit(5)
                    ->get();
                $mixedQuestions = $mixedQuestions->merge($fallbackBank);
            }

            $syncData = [];
            foreach ($mixedQuestions->values() as $idx => $q) {
                $syncData[$q->id] = [
                    'order' => $idx,
                    'points' => 10.0,
                ];
            }

            $quiz->questions()->sync($syncData);
            $createdQuizzes[] = $quiz;
        }

        $this->command->info('Đã khởi tạo thành công ' . count($createdQuizzes) . ' bài Quiz mix câu hỏi Ngân hàng + Kho cá nhân!');

        // =========================================================================
        // TẠO LƯỢT LÀM BÀI MẪU (QUIZ ATTEMPTS) CHO CÁC QUIZ
        // =========================================================================
        $allUsersList = $users->values();
        $attemptCount = 0;

        foreach ($createdQuizzes as $quiz) {
            $quiz->loadMissing('questions.answers');
            $questions = $quiz->questions;
            if ($questions->isEmpty()) {
                continue;
            }

            // Tạo 4-6 lượt làm bài từ các học sinh/người dùng khác nhau
            $randomUsers = $allUsersList->where('id', '!=', $quiz->user_id)->random(min(5, $allUsersList->count() - 1));

            foreach ($randomUsers as $studentUser) {
                $correctCount = 0;
                $answersSnapshot = [];
                $totalPoints = $questions->count() * 10;

                foreach ($questions as $q) {
                    $answers = $q->answers;
                    $correctAnswer = $answers->firstWhere('is_correct', true);

                    // Tỉ lệ trả lời đúng thực tế 70% - 90%
                    $isCorrect = (rand(1, 100) <= 80);
                    if ($isCorrect && $correctAnswer) {
                        $selectedId = $correctAnswer->id;
                        $correctCount++;
                    } else {
                        $wrongAnswer = $answers->firstWhere('is_correct', false);
                        $selectedId = $wrongAnswer ? $wrongAnswer->id : ($answers->first()?->id);
                    }

                    $answersSnapshot[] = [
                        'question_id' => $q->id,
                        'selected_answer_ids' => $selectedId ? [$selectedId] : [],
                        'is_correct' => $isCorrect,
                    ];
                }

                $score = round(($correctCount / max(1, $questions->count())) * 100, 1);
                $timeSpent = rand(300, 1800);
                $startedAt = now()->subDays(rand(1, 10))->subHours(rand(1, 20));

                QuizAttempt::create([
                    'user_id' => $studentUser->id,
                    'quiz_id' => $quiz->id,
                    'room_id' => null,
                    'assignment_id' => null,
                    'mode' => 'practice',
                    'attempt_number' => 1,
                    'score' => $score,
                    'total_points' => $totalPoints,
                    'time_spent_seconds' => $timeSpent,
                    'answers_snapshot' => $answersSnapshot,
                    'status' => 'completed',
                    'started_at' => $startedAt,
                    'finished_at' => (clone $startedAt)->addSeconds($timeSpent),
                    'submitted_at' => (clone $startedAt)->addSeconds($timeSpent),
                    'xp_earned' => (int) ($score * 0.5),
                ]);

                $attemptCount++;
            }
        }

        $this->command->info("Đã khởi tạo thành công {$attemptCount} lượt làm bài thi chuẩn cho các Quiz!");
    }
}
