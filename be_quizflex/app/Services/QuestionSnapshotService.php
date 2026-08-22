<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Question;
use App\Models\QuestionReviewRequest;
use Illuminate\Support\Facades\DB;

class QuestionSnapshotService
{
    /**
     * Tính toán Fingerprint từ dữ liệu Snapshot (dùng cho QuestionReviewRequest hoặc mảng dữ liệu)
     */
    public function computeFingerprintFromSnapshot(?string $content, ?string $type, array $answers): string
    {
        $cleanContent = mb_strtolower(trim(preg_replace('/\s+/u', ' ', strip_tags($content ?? ''))), 'UTF-8');
        $cleanType = strtolower(trim((string) ($type ?? 'single_choice')));

        $answerItems = [];
        foreach ($answers as $ans) {
            $ansContentText = is_array($ans) ? ($ans['content'] ?? $ans['text'] ?? '') : ($ans->content ?? '');
            $ansContent = mb_strtolower(trim(preg_replace('/\s+/u', ' ', strip_tags((string)$ansContentText))), 'UTF-8');
            $isCorrect = (is_array($ans) ? !empty($ans['is_correct']) : !empty($ans->is_correct)) ? '1' : '0';
            $answerItems[] = "{$ansContent}::{$isCorrect}";
        }

        sort($answerItems, SORT_STRING);
        $canonicalAnswers = implode('||', $answerItems);

        $canonicalString = "{$cleanContent}###{$cleanType}###{$canonicalAnswers}";

        return hash('sha256', $canonicalString);
    }

    /**
     * Tính toán Fingerprint (Mã băm chuẩn hóa SHA-256) cho câu hỏi và danh sách đáp án
     */
    public function computeFingerprint(Question $question): string
    {
        $answers = $question->relationLoaded('answers') ? $question->answers : $question->answers()->get();
        return $this->computeFingerprintFromSnapshot($question->content, $question->type, $answers->all());
    }

    /**
     * Tìm kiếm câu hỏi đã tồn tại trong Ngân hàng công khai theo Fingerprint
     */
    public function findExistingBankQuestion(string $fingerprint, bool $lockForUpdate = false): ?Question
    {
        if (empty($fingerprint)) {
            return null;
        }

        $query = Question::where('fingerprint', $fingerprint)
            ->where('is_public', true);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    /**
     * Tạo một bản Snapshot mới của câu hỏi cá nhân vào Ngân hàng câu hỏi
     */
    public function createSnapshotForBank(Question $originalQuestion, ?int $reviewedById = null): Question
    {
        return DB::transaction(function () use ($originalQuestion) {
            $fingerprint = $this->computeFingerprint($originalQuestion);

            // Kiểm tra xem đã tồn tại snapshot trong Ngân hàng câu hỏi chưa
            $existingBankQuestion = $this->findExistingBankQuestion($fingerprint, true);
            if ($existingBankQuestion) {
                return $existingBankQuestion->loadMissing('answers');
            }

            $snapshot = Question::create([
                'user_id' => $originalQuestion->user_id,
                'origin_question_id' => $originalQuestion->id,
                'quiz_id' => null,
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

    /**
     * Tạo một bản Snapshot mới của câu hỏi vào Ngân hàng dựa trên QuestionReviewRequest
     */
    public function createSnapshotFromReviewRequest(QuestionReviewRequest $reviewRequest, ?int $reviewedById = null): Question
    {
        return DB::transaction(function () use ($reviewRequest) {
            $originalQuestion = $reviewRequest->question;
            $snapshotAnswers = $reviewRequest->snapshot_answers ?? [];

            $fingerprint = $this->computeFingerprintFromSnapshot(
                $reviewRequest->snapshot_content,
                $reviewRequest->snapshot_type,
                $snapshotAnswers
            );

            // Kiểm tra xem đã tồn tại snapshot trong Ngân hàng với fingerprint này chưa (không phụ thuộc origin_question_id)
            $existingSnapshot = $this->findExistingBankQuestion($fingerprint, true);

            if ($existingSnapshot) {
                return $existingSnapshot->loadMissing('answers');
            }

            $snapshot = Question::create([
                'user_id' => $reviewRequest->user_id ?? $originalQuestion?->user_id,
                'origin_question_id' => $reviewRequest->question_id,
                'quiz_id' => null,
                'is_public' => true,
                'bank_submission_status' => 'approved',
                'bank_submission_at' => $originalQuestion?->bank_submission_at ?? now(),
                'content' => $reviewRequest->snapshot_content,
                'image_url' => $reviewRequest->snapshot_image_url,
                'type' => $reviewRequest->snapshot_type ?? 'single_choice',
                'difficulty' => $reviewRequest->snapshot_difficulty ?? 'medium',
                'education_level_id' => $reviewRequest->snapshot_education_level_id,
                'grade_id' => $reviewRequest->snapshot_grade_id,
                'subject_id' => $reviewRequest->snapshot_subject_id,
                'topic_name' => $reviewRequest->snapshot_topic_name,
                'points' => $reviewRequest->snapshot_points ?? 10,
                'order' => 0,
                'fingerprint' => $fingerprint,
            ]);

            foreach ($snapshotAnswers as $index => $ans) {
                Answer::create([
                    'question_id' => $snapshot->id,
                    'content' => is_array($ans) ? ($ans['content'] ?? $ans['text'] ?? '') : (string)$ans,
                    'is_correct' => is_array($ans) ? (bool)($ans['is_correct'] ?? false) : false,
                    'order' => is_array($ans) ? ($ans['order'] ?? $index) : $index,
                ]);
            }

            return $snapshot->fresh('answers');
        });
    }
}

