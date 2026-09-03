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
     * Tìm kiếm câu hỏi đã tồn tại trong Ngân hàng công khai theo Fingerprint (Dùng để kiểm tra trùng lặp nội dung)
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
     * Tìm kiếm Bank Snapshot duy nhất của một Question gốc theo origin_question_id
     */
    public function findBankSnapshotByOriginId(int $originQuestionId, bool $lockForUpdate = false): ?Question
    {
        $query = Question::where('origin_question_id', $originQuestionId);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    /**
     * Tạo hoặc Cập nhật bản Snapshot của câu hỏi cá nhân vào Ngân hàng câu hỏi
     * QUY TẮC: Một origin_question_id chỉ có duy nhất 1 snapshot đại diện trong ngân hàng.
     */
    public function createSnapshotForBank(Question $originalQuestion, ?int $reviewedById = null): Question
    {
        return DB::transaction(function () use ($originalQuestion) {
            $fingerprint = $this->computeFingerprint($originalQuestion);
            $answers = $originalQuestion->relationLoaded('answers') ? $originalQuestion->answers : $originalQuestion->answers()->get();

            // 1. Tìm Bank Snapshot hiện tại theo origin_question_id
            $existingSnapshot = $this->findBankSnapshotByOriginId($originalQuestion->id, true);

            // 2. Nếu chưa có snapshot theo origin_id, kiểm tra xem đã có câu hỏi trong ngân hàng với cùng fingerprint hay chưa
            if (!$existingSnapshot && !empty($fingerprint)) {
                $existingSnapshot = Question::whereNull('quiz_id')
                    ->where('is_public', true)
                    ->where('fingerprint', $fingerprint)
                    ->lockForUpdate()
                    ->first();
            }

            if ($existingSnapshot) {
                // Đã có snapshot trong ngân hàng -> UPDATE snapshot hiện tại (Giữ nguyên snapshot.id)
                $existingSnapshot->update([
                    'user_id' => $existingSnapshot->user_id ?? $originalQuestion->user_id,
                    'is_public' => true,
                    'bank_submission_status' => 'approved',
                    'bank_submission_at' => now(),
                    'content' => $originalQuestion->content,
                    'image_url' => $originalQuestion->image_url ?? $existingSnapshot->image_url,
                    'type' => $originalQuestion->type,
                    'difficulty' => $originalQuestion->difficulty,
                    'education_level_id' => $originalQuestion->education_level_id ?? $existingSnapshot->education_level_id,
                    'grade_id' => $originalQuestion->grade_id ?? $existingSnapshot->grade_id,
                    'subject_id' => $originalQuestion->subject_id ?? $existingSnapshot->subject_id,
                    'topic_name' => $originalQuestion->topic_name ?? $existingSnapshot->topic_name,
                    'points' => $originalQuestion->points ?? $existingSnapshot->points ?? 10,
                    'order' => $originalQuestion->order ?? $existingSnapshot->order ?? 0,
                    'fingerprint' => $fingerprint,
                ]);

                // Đồng bộ answers
                $this->syncSnapshotAnswersFromModels($existingSnapshot, $answers);

                return $existingSnapshot->fresh('answers');
            }

            // 3. Chưa có snapshot và chưa có fingerprint trùng -> CREATE snapshot mới (Lần đầu vào ngân hàng)
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
     * Tạo hoặc Cập nhật bản Snapshot của câu hỏi vào Ngân hàng dựa trên QuestionReviewRequest
     * QUY TẮC: Một origin_question_id chỉ có duy nhất 1 snapshot đại diện trong ngân hàng.
     */
    public function createSnapshotFromReviewRequest(QuestionReviewRequest $reviewRequest, ?int $reviewedById = null): Question
    {
        return DB::transaction(function () use ($reviewRequest) {
            $originalQuestion = $reviewRequest->question;
            $originQuestionId = $reviewRequest->question_id;
            $snapshotAnswers = $reviewRequest->snapshot_answers ?? [];

            $fingerprint = $this->computeFingerprintFromSnapshot(
                $reviewRequest->snapshot_content,
                $reviewRequest->snapshot_type,
                $snapshotAnswers
            );

            // 1. Tìm Bank Snapshot hiện tại theo origin_question_id
            $existingSnapshot = $this->findBankSnapshotByOriginId($originQuestionId, true);

            // 2. Nếu chưa có theo origin_id, kiểm tra xem đã có câu hỏi ngân hàng với cùng fingerprint hay chưa
            if (!$existingSnapshot && !empty($fingerprint)) {
                $existingSnapshot = Question::whereNull('quiz_id')
                    ->where('is_public', true)
                    ->where('fingerprint', $fingerprint)
                    ->lockForUpdate()
                    ->first();
            }

            if ($existingSnapshot) {
                // TRƯỜNG HỢP 1: ĐÃ CÓ SNAPSHOT -> UPDATE SNAPSHOT HIỆN TẠI (Giữ nguyên snapshot.id)
                $existingSnapshot->update([
                    'user_id' => $existingSnapshot->user_id ?? $reviewRequest->user_id ?? $originalQuestion?->user_id,
                    'is_public' => true,
                    'bank_submission_status' => 'approved',
                    'bank_submission_at' => now(),
                    'content' => $reviewRequest->snapshot_content,
                    'image_url' => $reviewRequest->snapshot_image_url ?? $existingSnapshot->image_url,
                    'type' => $reviewRequest->snapshot_type ?? 'single_choice',
                    'difficulty' => $reviewRequest->snapshot_difficulty ?? 'medium',
                    'education_level_id' => $reviewRequest->snapshot_education_level_id ?? $existingSnapshot->education_level_id,
                    'grade_id' => $reviewRequest->snapshot_grade_id ?? $existingSnapshot->grade_id,
                    'subject_id' => $reviewRequest->snapshot_subject_id ?? $existingSnapshot->subject_id,
                    'topic_name' => $reviewRequest->snapshot_topic_name ?? $existingSnapshot->topic_name,
                    'points' => $reviewRequest->snapshot_points ?? $existingSnapshot->points ?? 10,
                    'fingerprint' => $fingerprint,
                ]);

                // Đồng bộ lại toàn bộ đáp án của snapshot
                $this->syncSnapshotAnswers($existingSnapshot, $snapshotAnswers);

                return $existingSnapshot->fresh('answers');
            }

            // TRƯỜNG HỢP 2: CHƯA CÓ SNAPSHOT -> CREATE SNAPSHOT MỚI (Lần đầu duyệt vào ngân hàng)
            $snapshot = Question::create([
                'user_id' => $reviewRequest->user_id ?? $originalQuestion?->user_id,
                'origin_question_id' => $originQuestionId,
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

    /**
     * Đồng bộ danh sách đáp án của snapshot từ mảng dữ liệu (Array snapshot_answers)
     */
    private function syncSnapshotAnswers(Question $snapshot, array $snapshotAnswers): void
    {
        $existingAnswers = $snapshot->answers()->orderBy('order')->orderBy('id')->get();
        $keptIds = [];

        foreach ($snapshotAnswers as $index => $ans) {
            $content = is_array($ans) ? ($ans['content'] ?? $ans['text'] ?? '') : (string)$ans;
            $isCorrect = is_array($ans) ? (bool)($ans['is_correct'] ?? false) : false;
            $order = is_array($ans) ? ($ans['order'] ?? $index) : $index;

            if (isset($existingAnswers[$index])) {
                $existingAnswer = $existingAnswers[$index];
                $existingAnswer->update([
                    'content' => $content,
                    'is_correct' => $isCorrect,
                    'order' => $order,
                ]);
                $keptIds[] = $existingAnswer->id;
            } else {
                $newAnswer = Answer::create([
                    'question_id' => $snapshot->id,
                    'content' => $content,
                    'is_correct' => $isCorrect,
                    'order' => $order,
                ]);
                $keptIds[] = $newAnswer->id;
            }
        }

        if (!empty($keptIds)) {
            $snapshot->answers()->whereNotIn('id', $keptIds)->delete();
        }
    }

    /**
     * Đồng bộ danh sách đáp án của snapshot từ Collection/Models Answer
     */
    private function syncSnapshotAnswersFromModels(Question $snapshot, $originalAnswers): void
    {
        $existingAnswers = $snapshot->answers()->orderBy('order')->orderBy('id')->get();
        $keptIds = [];

        $index = 0;
        foreach ($originalAnswers as $ans) {
            $content = $ans->content;
            $isCorrect = (bool) $ans->is_correct;
            $order = $ans->order ?? $index;

            if (isset($existingAnswers[$index])) {
                $existingAnswer = $existingAnswers[$index];
                $existingAnswer->update([
                    'content' => $content,
                    'is_correct' => $isCorrect,
                    'order' => $order,
                ]);
                $keptIds[] = $existingAnswer->id;
            } else {
                $newAnswer = Answer::create([
                    'question_id' => $snapshot->id,
                    'content' => $content,
                    'is_correct' => $isCorrect,
                    'order' => $order,
                ]);
                $keptIds[] = $newAnswer->id;
            }
            $index++;
        }

        if (!empty($keptIds)) {
            $snapshot->answers()->whereNotIn('id', $keptIds)->delete();
        }
    }
}

