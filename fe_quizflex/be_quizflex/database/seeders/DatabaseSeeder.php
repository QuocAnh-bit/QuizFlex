<?php

namespace Database\Seeders;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@quizflex.local'],
            [
                'name' => 'QuizFlex Admin',
                'password' => bcrypt('password'),
                'role' => 'ADMIN',
                'ai_quota_remaining' => 50,
            ]
        );

        $guest = User::firstOrCreate(
            ['email' => 'guest@quizflex.local'],
            [
                'name' => 'Guest User',
                'password' => bcrypt('password'),
                'role' => 'GUEST',
            ]
        );

        $samples = [
            [
                'title' => 'Kiến thức Sinh học cơ bản',
                'description' => 'Bộ câu hỏi mẫu về tế bào, ti thể và ADN.',
                'category' => 'Khoa học',
                'tag' => 'Sinh học',
                'difficulty' => 'medium',
                'is_public' => true,
                'status' => 'published',
                'time_limit_seconds' => 720,
                'icon' => 'BIO',
                'badge' => 'SCI',
                'questions' => [
                    [
                        'content' => 'Bào quan nào được ví như nhà máy năng lượng của tế bào?',
                        'correct' => 'A',
                        'answers' => ['Ti thể', 'Ribosome', 'Nhân tế bào', 'Không bào'],
                    ],
                    [
                        'content' => 'ADN chủ yếu có chức năng gì?',
                        'correct' => 'B',
                        'answers' => ['Tạo năng lượng trực tiếp', 'Lưu trữ thông tin di truyền', 'Vận chuyển oxy', 'Tiêu hóa protein'],
                    ],
                    [
                        'content' => 'Quang hợp thường diễn ra mạnh nhất ở bộ phận nào của cây?',
                        'correct' => 'C',
                        'answers' => ['Rễ', 'Thân gỗ', 'Lá', 'Hoa'],
                    ],
                ],
            ],
            [
                'title' => 'Lịch sử Việt Nam nhập môn',
                'description' => 'Một số mốc lịch sử cơ bản để làm quen hệ thống quiz.',
                'category' => 'Lịch sử',
                'tag' => 'Việt Nam',
                'difficulty' => 'easy',
                'is_public' => true,
                'status' => 'published',
                'time_limit_seconds' => 600,
                'icon' => 'HIS',
                'badge' => 'VN',
                'questions' => [
                    [
                        'content' => 'Chiến thắng Điện Biên Phủ diễn ra vào năm nào?',
                        'correct' => 'D',
                        'answers' => ['1945', '1946', '1950', '1954'],
                    ],
                    [
                        'content' => 'Tuyên ngôn Độc lập của Việt Nam được đọc tại đâu?',
                        'correct' => 'A',
                        'answers' => ['Quảng trường Ba Đình', 'Dinh Độc Lập', 'Bến Nhà Rồng', 'Cố đô Huế'],
                    ],
                ],
            ],
        ];

        foreach ($samples as $sample) {
            $questions = $sample['questions'];
            unset($sample['questions']);

            $quiz = Quiz::updateOrCreate(
                ['title' => $sample['title']],
                ['user_id' => $admin->id, ...$sample]
            );

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
