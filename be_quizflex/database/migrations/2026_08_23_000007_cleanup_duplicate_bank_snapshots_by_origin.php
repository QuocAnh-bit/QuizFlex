<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Question;
use App\Models\Answer;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Quét tìm tất cả origin_question_id có nhiều hơn 1 snapshot trong ngân hàng
        $duplicateOrigins = Question::whereNotNull('origin_question_id')
            ->select('origin_question_id', DB::raw('count(*) as count'))
            ->groupBy('origin_question_id')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicateOrigins as $item) {
            $originId = $item->origin_question_id;

            // Lấy tất cả snapshot của origin_question_id này, sắp xếp theo id tăng dần
            $snapshots = Question::where('origin_question_id', $originId)
                ->with('answers')
                ->orderBy('id', 'asc')
                ->get();

            if ($snapshots->count() <= 1) {
                continue;
            }

            // Bản ghi đầu tiên làm canonical để giữ ID đang được quiz/room tham chiếu
            $canonical = $snapshots->first();

            // Bản ghi mới nhất chứa dữ liệu cập nhật sau cùng
            $latestSnapshot = $snapshots->last();

            // Lưu dữ liệu mới nhất vào biến tạm trước khi xóa bản ghi duplicate
            $latestData = [
                'content' => $latestSnapshot->content,
                'image_url' => $latestSnapshot->image_url,
                'type' => $latestSnapshot->type,
                'difficulty' => $latestSnapshot->difficulty,
                'education_level_id' => $latestSnapshot->education_level_id,
                'grade_id' => $latestSnapshot->grade_id,
                'subject_id' => $latestSnapshot->subject_id,
                'topic_name' => $latestSnapshot->topic_name,
                'points' => $latestSnapshot->points,
                'fingerprint' => $latestSnapshot->fingerprint,
                'is_public' => true,
                'bank_submission_status' => 'approved',
                'bank_submission_at' => $latestSnapshot->bank_submission_at ?? now(),
            ];

            $latestAnswersData = $latestSnapshot->answers->map(function ($a) {
                return [
                    'content' => $a->content,
                    'is_correct' => (bool)$a->is_correct,
                    'order' => $a->order,
                ];
            })->toArray();

            // BƯỚC 1: Xử lý và xóa các bản ghi duplicate thừa (để giải phóng unique index bank_fingerprint)
            $duplicates = $snapshots->slice(1);
            foreach ($duplicates as $dup) {
                // Di dời quiz_questions
                $dupQuizQuestions = DB::table('quiz_questions')->where('question_id', $dup->id)->get();
                foreach ($dupQuizQuestions as $qq) {
                    $existsInQuiz = DB::table('quiz_questions')
                        ->where('quiz_id', $qq->quiz_id)
                        ->where('question_id', $canonical->id)
                        ->exists();

                    if ($existsInQuiz) {
                        DB::table('quiz_questions')->where('id', $qq->id)->delete();
                    } else {
                        DB::table('quiz_questions')->where('id', $qq->id)->update(['question_id' => $canonical->id]);
                    }
                }

                // Di dời live_room_answers
                $dupRoomAnswers = DB::table('live_room_answers')->where('question_id', $dup->id)->get();
                foreach ($dupRoomAnswers as $lra) {
                    $existsInRoom = DB::table('live_room_answers')
                        ->where('live_room_id', $lra->live_room_id)
                        ->where('user_id', $lra->user_id)
                        ->where('question_id', $canonical->id)
                        ->exists();

                    if ($existsInRoom) {
                        DB::table('live_room_answers')->where('id', $lra->id)->delete();
                    } else {
                        DB::table('live_room_answers')->where('id', $lra->id)->update(['question_id' => $canonical->id]);
                    }
                }

                // Di dời report_tickets
                DB::table('report_tickets')->where('question_id', $dup->id)->update(['question_id' => $canonical->id]);

                // Di dời question_review_requests
                DB::table('question_review_requests')->where('question_id', $dup->id)->update(['question_id' => $canonical->id]);

                // Di dời origin_question_id trỏ đến duplicate này (nếu có)
                DB::table('questions')->where('origin_question_id', $dup->id)->update(['origin_question_id' => $canonical->id]);

                // Xóa đáp án của bản ghi duplicate và xóa bản ghi duplicate
                DB::table('answers')->where('question_id', $dup->id)->delete();
                DB::table('questions')->where('id', $dup->id)->delete();
            }

            // BƯỚC 2: Cập nhật dữ liệu mới nhất cho canonical snapshot
            $canonical->update($latestData);

            // Đồng bộ đáp án mới nhất sang canonical snapshot
            $existingCanonicalAnswers = $canonical->answers()->orderBy('order')->orderBy('id')->get();
            $keptIds = [];

            foreach ($latestAnswersData as $idx => $ans) {
                if (isset($existingCanonicalAnswers[$idx])) {
                    $exAns = $existingCanonicalAnswers[$idx];
                    $exAns->update([
                        'content' => $ans['content'],
                        'is_correct' => $ans['is_correct'],
                        'order' => $ans['order'] ?? $idx,
                    ]);
                    $keptIds[] = $exAns->id;
                } else {
                    $newAns = Answer::create([
                        'question_id' => $canonical->id,
                        'content' => $ans['content'],
                        'is_correct' => $ans['is_correct'],
                        'order' => $ans['order'] ?? $idx,
                    ]);
                    $keptIds[] = $newAns->id;
                }
            }

            if (!empty($keptIds)) {
                $canonical->answers()->whereNotIn('id', $keptIds)->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cleanup data không thể hoàn tác lại các snapshot duplicate thừa đã xóa.
    }
};
