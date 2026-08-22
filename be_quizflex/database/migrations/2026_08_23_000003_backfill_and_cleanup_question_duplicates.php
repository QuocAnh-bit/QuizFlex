<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Question;
use App\Models\Answer;
use App\Services\QuestionSnapshotService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $snapshotService = app(QuestionSnapshotService::class);

        // 1. Backfill fingerprint cho tất cả các Question còn thiếu
        $unfingerprintedQuestions = Question::withTrashed()->with('answers')->whereNull('fingerprint')->orWhere('fingerprint', '')->get();
        foreach ($unfingerprintedQuestions as $q) {
            $fp = $snapshotService->computeFingerprint($q);
            DB::table('questions')->where('id', $q->id)->update(['fingerprint' => $fp]);
        }

        // 2. Xử lý duplicate trong Question Bank (is_public = 1, deleted_at IS NULL)
        $bankQuestions = Question::where('is_public', true)->with('answers')->get();
        $bankGrouped = [];
        foreach ($bankQuestions as $bq) {
            $fp = $bq->fingerprint ?: $snapshotService->computeFingerprint($bq);
            $bankGrouped[$fp][] = $bq;
        }

        foreach ($bankGrouped as $fp => $group) {
            if (count($group) <= 1) {
                continue;
            }

            // Sắp xếp theo ID tăng dần để lấy bản ghi đầu tiên làm canonical
            usort($group, fn($a, $b) => $a->id <=> $b->id);
            $canonical = $group[0];
            $duplicates = array_slice($group, 1);

            foreach ($duplicates as $dup) {
                // Di dời tham chiếu quiz_questions an toàn
                $dupQuizQuestions = DB::table('quiz_questions')->where('question_id', $dup->id)->get();
                foreach ($dupQuizQuestions as $qq) {
                    $existsCanonicalInQuiz = DB::table('quiz_questions')
                        ->where('quiz_id', $qq->quiz_id)
                        ->where('question_id', $canonical->id)
                        ->exists();

                    if ($existsCanonicalInQuiz) {
                        DB::table('quiz_questions')->where('id', $qq->id)->delete();
                    } else {
                        DB::table('quiz_questions')->where('id', $qq->id)->update(['question_id' => $canonical->id]);
                    }
                }

                // Di dời tham chiếu live_room_answers an toàn
                $dupRoomAnswers = DB::table('live_room_answers')->where('question_id', $dup->id)->get();
                foreach ($dupRoomAnswers as $lra) {
                    $existsInLiveRoom = DB::table('live_room_answers')
                        ->where('live_room_id', $lra->live_room_id)
                        ->where('user_id', $lra->user_id)
                        ->where('question_id', $canonical->id)
                        ->exists();

                    if ($existsInLiveRoom) {
                        DB::table('live_room_answers')->where('id', $lra->id)->delete();
                    } else {
                        DB::table('live_room_answers')->where('id', $lra->id)->update(['question_id' => $canonical->id]);
                    }
                }

                // Di dời report_tickets
                DB::table('report_tickets')->where('question_id', $dup->id)->update(['question_id' => $canonical->id]);

                // Di dời question_review_requests
                DB::table('question_review_requests')->where('question_id', $dup->id)->update(['question_id' => $canonical->id]);

                // Di dời origin_question_id trỏ đến duplicate này
                DB::table('questions')->where('origin_question_id', $dup->id)->update(['origin_question_id' => $canonical->id]);

                // Xóa đáp án của bản ghi duplicate và xóa bản ghi duplicate
                DB::table('answers')->where('question_id', $dup->id)->delete();
                DB::table('questions')->where('id', $dup->id)->delete();
            }
        }

        // 3. Xử lý duplicate trong kho cá nhân của User (is_public = 0, origin_question_id IS NULL, deleted_at IS NULL)
        $personalQuestions = Question::where('is_public', false)->whereNull('origin_question_id')->with('answers')->get();
        $personalGrouped = [];
        foreach ($personalQuestions as $pq) {
            $fp = $pq->fingerprint ?: $snapshotService->computeFingerprint($pq);
            $key = $pq->user_id . ':::' . $fp;
            $personalGrouped[$key][] = $pq;
        }

        foreach ($personalGrouped as $key => $group) {
            if (count($group) <= 1) {
                continue;
            }

            usort($group, fn($a, $b) => $a->id <=> $b->id);
            $canonical = $group[0];
            $duplicates = array_slice($group, 1);

            foreach ($duplicates as $dup) {
                // Di dời tham chiếu quiz_questions an toàn
                $dupQuizQuestions = DB::table('quiz_questions')->where('question_id', $dup->id)->get();
                foreach ($dupQuizQuestions as $qq) {
                    $existsCanonicalInQuiz = DB::table('quiz_questions')
                        ->where('quiz_id', $qq->quiz_id)
                        ->where('question_id', $canonical->id)
                        ->exists();

                    if ($existsCanonicalInQuiz) {
                        DB::table('quiz_questions')->where('id', $qq->id)->delete();
                    } else {
                        DB::table('quiz_questions')->where('id', $qq->id)->update(['question_id' => $canonical->id]);
                    }
                }

                // Di dời tham chiếu live_room_answers an toàn
                $dupRoomAnswers = DB::table('live_room_answers')->where('question_id', $dup->id)->get();
                foreach ($dupRoomAnswers as $lra) {
                    $existsInLiveRoom = DB::table('live_room_answers')
                        ->where('live_room_id', $lra->live_room_id)
                        ->where('user_id', $lra->user_id)
                        ->where('question_id', $canonical->id)
                        ->exists();

                    if ($existsInLiveRoom) {
                        DB::table('live_room_answers')->where('id', $lra->id)->delete();
                    } else {
                        DB::table('live_room_answers')->where('id', $lra->id)->update(['question_id' => $canonical->id]);
                    }
                }

                // Di dời report_tickets
                DB::table('report_tickets')->where('question_id', $dup->id)->update(['question_id' => $canonical->id]);

                // Di dời question_review_requests
                DB::table('question_review_requests')->where('question_id', $dup->id)->update(['question_id' => $canonical->id]);

                // Di dời origin_question_id trỏ đến duplicate này
                DB::table('questions')->where('origin_question_id', $dup->id)->update(['origin_question_id' => $canonical->id]);

                // Xóa đáp án của bản ghi duplicate và xóa bản ghi duplicate
                DB::table('answers')->where('question_id', $dup->id)->delete();
                DB::table('questions')->where('id', $dup->id)->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cleanup data không thể hoàn tác lại các duplicate đã xóa.
    }
};
