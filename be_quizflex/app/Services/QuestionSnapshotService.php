<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Question;
use Illuminate\Support\Facades\DB;

class QuestionSnapshotService
{
    /**
     * Tính toán Fingerprint (Mã băm chuẩn hóa SHA-256) cho câu hỏi và danh sách đáp án
     */
    public function computeFingerprint(Question $question): string
    {
        $cleanContent = mb_strtolower(trim(preg_replace('/\s+/u', ' ', strip_tags($question->content ?? ''))), 'UTF-8');
        $type = strtolower(trim((string) ($question->type ?? 'single_choice')));

        // Chuẩn hóa và sắp xếp các đáp án theo thứ tự bảng chữ cái để không phụ thuộc hoán vị A/B/C/D
        $answers = $question->relationLoaded('answers') ? $question->answers : $question->answers()->get();
        $answerItems = [];

        foreach ($answers as $ans) {
            $ansContent = mb_strtolower(trim(preg_replace('/\s+/u', ' ', strip_tags($ans->content ?? ''))), 'UTF-8');
            $isCorrect = $ans->is_correct ? '1' : '0';
            $answerItems[] = "{$ansContent}::{$isCorrect}";
        }

        sort($answerItems, SORT_STRING);
        $canonicalAnswers = implode('||', $answerItems);

        $canonicalString = "{$cleanContent}###{$type}###{$canonicalAnswers}";

        return hash('sha256', $canonicalString);
    }

    /**
     * Tìm kiếm câu hỏi đã tồn tại trong Ngân hàng công khai theo Fingerprint
     */
    public function findExistingBankQuestion(string $fingerprint): ?Question
    {
        if (empty($fingerprint)) {
            return null;
        }

        return Question::where('fingerprint', $fingerprint)
            ->where('is_public', true)
            ->first();
    }

    /**
     * Tạo một bản Snapshot mới của câu hỏi cá nhân vào Ngân hàng câu hỏi
     */
    public function createSnapshotForBank(Question $originalQuestion, ?int $reviewedById = null): Question
    {
        return DB::transaction(function () use ($originalQuestion) {
            $fingerprint = $this->computeFingerprint($originalQuestion);

            $snapshot = Question::create([
                'user_id' => $originalQuestion->user_id,
                'origin_question_id' => $originalQuestion->id,
                'is_public' => true,
                'bank_submission_status' => 'approved',
                'bank_submission_at' => $originalQuestion->bank_submission_at ?? now(),
                'content' => $originalQuestion->content,
                'image_url' => $originalQuestion->image_url,
                'type' => $originalQuestion->type,
                'difficulty' => $originalQuestion->difficulty,
                'education_level_id' => $originalQuestion->education_level_id,
                'grade_id' => $originalQuestion->grade_id,
                'subject_id' => $originalQuestion->subject_id,
                'topic_name' => $originalQuestion->topic_name,
                'points' => $originalQuestion->points ?? 10,
                'order' => $originalQuestion->order ?? 0,
                'fingerprint' => $fingerprint,
            ]);

            // Sao chép toàn bộ đáp án sang Question snapshot mới
            $answers = $originalQuestion->relationLoaded('answers') ? $originalQuestion->answers : $originalQuestion->answers()->get();
            foreach ($answers as $ans) {
                Answer::create([
                    'question_id' => $snapshot->id,
                    'content' => $ans->content,
                    'is_correct' => (bool) $ans->is_correct,
                    'order' => $ans->order ?? 0,
                ]);
            }

            return $snapshot->fresh('answers');
        });
    }
}
