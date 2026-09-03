<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Room;
use App\Models\RoomAllowedMember;
use App\Models\RoomAssignment;
use App\Models\RoomMember;
use App\Models\RoomMemberEvaluation;
use App\Models\RoomSubmissionEvaluation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HomeworkRoomSeeder extends Seeder
{
    /**
     * Khởi tạo phòng bài tập cho các tài khoản VIP (PRO, ULTRA, PLUS),
     * mỗi tài khoản VIP có ít nhất 2 phòng với đầy đủ dữ liệu học sinh, bài giao, bài nộp và đánh giá.
     */
    public function run(): void
    {
        $vipUsers = User::whereIn('role', ['pro', 'ultra', 'plus'])
            ->orWhereIn('plan', ['pro', 'ultra', 'plus'])
            ->get();

        if ($vipUsers->isEmpty()) {
            $this->command->warn('Chưa tìm thấy tài khoản VIP. Vui lòng chạy UserSeeder trước!');
            return;
        }

        // Danh sách học sinh mẫu tham gia các phòng bài tập
        $students = User::whereIn('role', ['free', 'plus'])
            ->whereNotIn('email', ['admin@quizflex.vn', 'admin@quizflex.local'])
            ->get();

        if ($students->isEmpty()) {
            $students = User::all();
        }

        $allQuizzes = Quiz::with('questions.answers')->get();

        // Cấu hình phòng bài tập đặc thù cho từng tài khoản VIP
        $roomBlueprints = [
            'thay.duchoang@quizflex.vn' => [
                [
                    'name' => 'Lớp Luyện Thi Chuyên Toán 12A (Khóa 2025 - 2026)',
                    'description' => 'Lớp học bồi dưỡng chuyên sâu môn Toán dành cho học sinh mục tiêu 9+ kì thi Tốt nghiệp THPT và Đánh giá Năng lực.',
                    'code' => 'TOAN12',
                    'assignment_titles' => [
                        'Bài tập tuần 01: Khảo sát & Vẽ đồ thị hàm số vận dụng cao',
                        'Bài tập tuần 02: Ứng dụng Tích phân tính diện tích hình phẳng',
                        'Đề kiểm tra Định kỳ Khảo sát Năng lực Môn Toán số 1',
                    ],
                ],
                [
                    'name' => 'Đội Tuyển Ôn Thi Học Sinh Giỏi Toán THPT',
                    'description' => 'Phòng rèn luyện kỹ năng giải các chuyên đề Số phức, Hình học Oxyz và Bất đẳng thức cực trị.',
                    'code' => 'HSGTOA',
                    'assignment_titles' => [
                        'Chuyên đề 01: Cực trị hình học trong không gian Oxyz',
                        'Chuyên đề 02: Các dạng toán Số phức nâng cao 9+',
                    ],
                ],
            ],

            'co.quynhnga@quizflex.vn' => [
                [
                    'name' => 'Phòng Luyện Thi Tiếng Anh THPT Quốc Gia - Target 9+',
                    'description' => 'Lớp ôn luyện tổng hợp Ngữ pháp, Từ vựng, Cụm từ cố định (Collocations) và Kỹ năng làm bài Đọc hiểu.',
                    'code' => 'ENG9PL',
                    'assignment_titles' => [
                        'Homework 01: Advanced Inversion & Mixed Conditionals',
                        'Homework 02: Idioms and Phrasal Verbs in Context',
                        'Reading Test: Academic Texts Comprehension Practice',
                    ],
                ],
                [
                    'name' => 'Lớp Tiếng Anh Học Thuật & IELTS 7.0+ Intensive',
                    'description' => 'Rèn luyện kỹ năng ngữ pháp học thuật, tư duy phân tích câu và vốn từ vựng Band 7.5+.',
                    'code' => 'IELTS7',
                    'assignment_titles' => [
                        'Assignment 01: Academic Word List Mastery Check',
                        'Assignment 02: Complex Sentence Structures in Writing & Reading',
                    ],
                ],
            ],

            'thay.quocbao@quizflex.vn' => [
                [
                    'name' => 'Lớp Vật Lý 12 - Luyện Đề Chuẩn Cấu Trúc Bộ GD&ĐT',
                    'description' => 'Chuyên đề Vật lý trọng tâm: Dao động cơ, Sóng cơ, Dòng điện xoay chiều và Sóng ánh sáng.',
                    'code' => 'LY12A1',
                    'assignment_titles' => [
                        'Bài tập 01: Bài toán Đồ thị Dao động cơ và Con lắc lò xo',
                        'Bài tập 02: Mạch điện xoay chiều RLC và Hiện tượng Cộng hưởng',
                    ],
                ],
                [
                    'name' => 'Phòng Ôn Luyện Cấp Tốc: Vật Lý Thực Nghiệm & Lượng Tử',
                    'description' => 'Tổng hợp các câu hỏi thực tế, bài toán đồ thị và lý thuyết hiện tượng quang điện.',
                    'code' => 'LYPRO1',
                    'assignment_titles' => [
                        'Đề kiểm tra 1 tiết: Quang sóng & Vật lý Hạt nhân',
                        'Bài tập củng cố: Thí nghiệm Young và Giao thoa ánh sáng',
                    ],
                ],
            ],

            'lethanhha@gmail.com' => [
                [
                    'name' => 'CLB Hóa Học 12 - Chuyên Đề Este & Hợp Chất Hữu Cơ',
                    'description' => 'Phòng học tập trực tuyến môn Hóa học 12, thảo luận bài toán este đa chức và xà phòng hóa.',
                    'code' => 'HOA12A',
                    'assignment_titles' => [
                        'Bài tập 01: Thủy phân Este và Phản ứng đốt cháy',
                        'Bài tập 02: Cacbohiđrat & Phản ứng tráng gương',
                    ],
                ],
                [
                    'name' => 'Lớp Luyện Thi Sinh Học 12 - Di Truyền Học Phân Tử',
                    'description' => 'Hệ thống hóa kiến thức cơ chế di truyền ADN, ARN, Protein và quy luật di truyền Menđen.',
                    'code' => 'SINH12',
                    'assignment_titles' => [
                        'Bài tập tuần 01: Bài toán nhân đôi ADN và Phiên mã mARN',
                        'Bài tập tuần 02: Quy luật di truyền và Phép lai phân tích',
                    ],
                ],
            ],

            'nguyenduyanh@gmail.com' => [
                [
                    'name' => 'Lớp Lập Trình & Cấu Trúc Dữ Liệu - Thuật Toán 2026',
                    'description' => 'Phòng thực hành Tin học ứng dụng: Thuật toán sắp xếp, Đồ thị, Quy hoạch động và Cơ sở dữ liệu SQL.',
                    'code' => 'CSDL01',
                    'assignment_titles' => [
                        'Lab 01: Truy vấn SQL nâng cao (GROUP BY & HAVING)',
                        'Lab 02: Độ phức tạp thuật toán và Sắp xếp nhanh QuickSort',
                    ],
                ],
                [
                    'name' => 'Phòng Luyện Thi Tin Học Trẻ & Học Sinh Giỏi CNTT',
                    'description' => 'Chuyên đề nâng cao về thuật toán Dijkstra, Cây nhị phân và Xử lý xâu ký tự.',
                    'code' => 'TINTHG',
                    'assignment_titles' => [
                        'Bài kiểm tra 01: Đường đi ngắn nhất trên đồ thị có trọng số',
                        'Bài kiểm tra 02: Kỹ thuật đệ quy và Quy hoạch động cơ bản',
                    ],
                ],
            ],

            'nguyenkhanhlinh@gmail.com' => [
                [
                    'name' => 'Nhóm Tự Học: Kỹ Năng Mềm & Tư Duy Học Thuật',
                    'description' => 'Chia sẻ phương pháp quản trị thời gian, kỹ năng thuyết trình và đọc hiểu tài liệu học thuật.',
                    'code' => 'SKILL1',
                    'assignment_titles' => [
                        'Bài test 01: Ứng dụng kỹ thuật Pomodoro trong học tập',
                        'Bài test 02: Kỹ năng quét thông tin Scanning & Skimming',
                    ],
                ],
                [
                    'name' => 'Phòng Ôn Thi Đánh Giá Tư Duy & Kỹ Năng Đọc Hiểu',
                    'description' => 'Luyện tập đề thi đánh giá tư duy định tính và định lượng.',
                    'code' => 'TUDUY1',
                    'assignment_titles' => [
                        'Đề luyện tập số 01: Tư duy logic và lập luận phản biện',
                        'Đề luyện tập số 02: Xử lý số liệu và biểu đồ phân tích',
                    ],
                ],
            ],

            'phanminhkhoi@gmail.com' => [
                [
                    'name' => 'Nhóm Học Tập Khối B00: Hóa Học & Sinh Học Đại Học Y',
                    'description' => 'Không gian tự học và kiểm tra chéo kiến thức giữa các thành viên dự thi Khối B.',
                    'code' => 'KHOIB1',
                    'assignment_titles' => [
                        'Bài tập 01: Kim loại kiềm & Hợp chất quan trọng',
                        'Bài tập 02: Di truyền học quần thể và Cân bằng Hardy-Weinberg',
                    ],
                ],
                [
                    'name' => 'Phòng Luyện Đề Tốc Độ Hóa Học 12',
                    'description' => 'Rèn luyện phản xạ giải nhanh 40 câu trắc nghiệm Hóa học trong 50 phút.',
                    'code' => 'HOASPD',
                    'assignment_titles' => [
                        'Đề thi thử số 01: 40 câu trắc nghiệm tổng hợp Hóa 12',
                        'Đề thi thử số 02: Lý thuyết đếm mệnh đề Hóa học đúng sai',
                    ],
                ],
            ],
        ];

        $totalRoomsCreated = 0;
        $totalAssignmentsCreated = 0;
        $totalAttemptsCreated = 0;

        foreach ($vipUsers as $vipUser) {
            $blueprints = $roomBlueprints[$vipUser->email] ?? [
                [
                    'name' => "Phòng Bài Tập Chuyên Đề - {$vipUser->name} #01",
                    'description' => 'Phòng giao bài tập và theo dõi tiến độ tự học của học viên.',
                    'code' => strtoupper(Str::random(6)),
                    'assignment_titles' => [
                        'Bài tập tuần 01: Ôn luyện kiến thức trọng tâm',
                        'Bài kiểm tra đánh giá định kỳ tuần 02',
                    ],
                ],
                [
                    'name' => "Phòng Rèn Luyện & Đánh Giá Năng Lực - {$vipUser->name} #02",
                    'description' => 'Phòng kiểm tra chất lượng và khảo sát bảng điểm học sinh.',
                    'code' => strtoupper(Str::random(6)),
                    'assignment_titles' => [
                        'Bài kiểm tra tổng hợp kiến thức nâng cao',
                        'Đề thi thử định kỳ tháng',
                    ],
                ],
            ];

            foreach ($blueprints as $blueprint) {
                // 1. Tạo phòng bài tập (Room)
                $room = Room::updateOrCreate(
                    [
                        'host_id' => $vipUser->id,
                        'name' => $blueprint['name'],
                    ],
                    [
                        'description' => $blueprint['description'],
                        'type' => 'homework',
                        'code' => $blueprint['code'],
                        'status' => 'active',
                        'max_players' => 60,
                        'join_policy' => 'open',
                        'started_at' => now()->subDays(14),
                    ]
                );

                $totalRoomsCreated++;

                // 2. Thêm Host và các học sinh vào phòng (RoomMember)
                // Host là admin/owner phòng
                RoomMember::firstOrCreate(
                    [
                        'room_id' => $room->id,
                        'user_id' => $vipUser->id,
                    ],
                    [
                        'role' => 'owner',
                        'status' => 'active',
                        'joined_at' => now()->subDays(14),
                    ]
                );


                // Lựa chọn 5 - 8 học sinh làm thành viên phòng
                $roomStudents = $students->where('id', '!=', $vipUser->id)->random(min(7, $students->count()));

                foreach ($roomStudents as $student) {
                    RoomMember::firstOrCreate(
                        [
                            'room_id' => $room->id,
                            'user_id' => $student->id,
                        ],
                        [
                            'role' => 'member',
                            'status' => 'active',
                            'joined_at' => now()->subDays(rand(5, 12)),
                        ]
                    );

                    // Thêm vào allowed members để hỗ trợ danh sách mời
                    RoomAllowedMember::firstOrCreate(
                        [
                            'room_id' => $room->id,
                            'email' => strtolower($student->email),
                        ],
                        [
                            'user_id' => $student->id,
                        ]
                    );

                    // Tạo đánh giá thành viên (RoomMemberEvaluation)
                    RoomMemberEvaluation::updateOrCreate(
                        [
                            'room_id' => $room->id,
                            'user_id' => $student->id,
                        ],
                        [
                            'evaluator_id' => $vipUser->id,
                            'comment' => "Học sinh {$student->name} tham gia học tập nghiêm túc, làm bài tập đầy đủ đúng hạn và có tiến bộ rõ rệt qua các tuần.",
                        ]
                    );
                }

                // 3. Tạo các bài giao (RoomAssignment)
                // Lấy quiz phù hợp của giáo viên hoặc quiz công khai
                $teacherQuizzes = $allQuizzes->where('user_id', $vipUser->id);
                if ($teacherQuizzes->isEmpty()) {
                    $teacherQuizzes = $allQuizzes;
                }

                foreach ($blueprint['assignment_titles'] as $asIndex => $asTitle) {
                    $quiz = $teacherQuizzes->values()->get($asIndex % $teacherQuizzes->count()) ?? $allQuizzes->first();

                    $assignment = RoomAssignment::updateOrCreate(
                        [
                            'room_id' => $room->id,
                            'title' => $asTitle,
                        ],
                        [
                            'quiz_id' => $quiz?->id,
                            'assigned_by' => $vipUser->id,
                            'description' => "Vui lòng hoàn thành bài trắc nghiệm {$asTitle} trước thời hạn. Bài làm tính vào điểm quá trình.",
                            'starts_at' => now()->subDays(7 - ($asIndex * 2)),
                            'deadline_at' => now()->addDays(7 + ($asIndex * 2)),
                            'duration_minutes' => 45,
                            'max_attempts' => 2,
                            'show_result_mode' => 'immediately',
                            'status' => 'published',
                            'shuffle_questions' => true,
                            'shuffle_answers' => true,
                        ]

                    );

                    $totalAssignmentsCreated++;

                    // 4. Tạo bài nộp hoàn chỉnh (QuizAttempt) cho toàn bộ học sinh trong phòng
                    // Đảm bảo dữ liệu bảng điểm (/gradebook) và tính năng xuất Excel hoạt động 100%
                    if ($quiz && $quiz->questions->isNotEmpty()) {
                        $questions = $quiz->questions;

                        foreach ($roomStudents as $stIdx => $student) {
                            $totalPoints = $questions->count() * 10;
                            // Tạo điểm số chân thực (dao động từ 7.0 đến 10.0)
                            $targetCorrect = min($questions->count(), max(1, (int) round($questions->count() * (0.7 + ($stIdx % 4) * 0.1))));

                            $correctCount = 0;
                            $answersSnapshot = [];

                            foreach ($questions as $qIdx => $q) {
                                $answers = $q->answers;
                                $correctAnswer = $answers->firstWhere('is_correct', true);
                                $isCorrect = ($qIdx < $targetCorrect);

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
                            $timeSpent = rand(900, 2400);
                            $submittedAt = now()->subDays(rand(1, 4))->subHours(rand(1, 10));

                            $attempt = QuizAttempt::updateOrCreate(
                                [
                                    'user_id' => $student->id,
                                    'room_id' => $room->id,
                                    'assignment_id' => $assignment->id,
                                ],
                                [
                                    'quiz_id' => $quiz->id,
                                    'mode' => 'homework',
                                    'attempt_number' => 1,
                                    'score' => $score,
                                    'total_points' => $totalPoints,
                                    'time_spent_seconds' => $timeSpent,
                                    'answers_snapshot' => $answersSnapshot,
                                    'status' => 'completed',
                                    'started_at' => (clone $submittedAt)->subSeconds($timeSpent),
                                    'finished_at' => $submittedAt,
                                    'submitted_at' => $submittedAt,
                                    'xp_earned' => (int) ($score * 0.5),
                                ]
                            );

                            // Tạo nhận xét cho bài nộp (RoomSubmissionEvaluation)
                            RoomSubmissionEvaluation::updateOrCreate(
                                [
                                    'room_id' => $room->id,
                                    'submission_id' => $attempt->id,
                                ],
                                [
                                    'user_id' => $student->id,
                                    'evaluator_id' => $vipUser->id,
                                    'comment' => "Điểm số đạt {$score}/100. Bài làm rất tốt, cách trình bày logic và chọn đáp án chính xác.",
                                ]
                            );

                            $totalAttemptsCreated++;
                        }
                    }
                }
            }
        }

        $this->command->info("Đã khởi tạo thành công {$totalRoomsCreated} phòng bài tập VIP, {$totalAssignmentsCreated} bài giao và {$totalAttemptsCreated} bài nộp của học sinh (đầy đủ bảng điểm để xuất Excel)!");
    }
}
