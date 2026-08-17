<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $quizzes = DB::table('quizzes')->get();

        foreach ($quizzes as $q) {
            $title = mb_strtolower(($q->title ?? '') . ' ' . ($q->category ?? '') . ' ' . ($q->tag ?? ''));
            $subjectId = null;
            $gradeId = null;
            $levelId = null;

            if (str_contains($title, 'toán') || str_contains($title, 'đại số') || str_contains($title, 'math')) {
                $subjectId = 1; // Toán học
            } elseif (str_contains($title, 'văn') || str_contains($title, 'truyện') || str_contains($title, 'ngữ văn')) {
                $subjectId = 2; // Ngữ văn
            } elseif (str_contains($title, 'anh') || str_contains($title, 'english') || str_contains($title, 'ielts') || str_contains($title, 'toeic')) {
                $subjectId = 3; // Tiếng Anh
            } elseif (str_contains($title, 'lý') || str_contains($title, 'vật lý')) {
                $subjectId = 4; // Vật lý
            } elseif (str_contains($title, 'hóa')) {
                $subjectId = 5; // Hóa học
            } elseif (str_contains($title, 'sinh')) {
                $subjectId = 6; // Sinh học
            } elseif (str_contains($title, 'sử') || str_contains($title, 'lịch sử')) {
                $subjectId = 7; // Lịch sử
            } elseif (str_contains($title, 'địa')) {
                $subjectId = 8; // Địa lý
            } elseif (str_contains($title, 'tin') || str_contains($title, 'lập trình') || str_contains($title, 'công nghệ') || str_contains($title, 'ai')) {
                $subjectId = 10; // Tin học / CNTT
            } else {
                $subjectId = 12; // Kỹ năng / Khác
            }

            if (str_contains($title, 'lớp 10') || str_contains($title, 'grade 10')) {
                $gradeId = 10;
                $levelId = 3;
            } elseif (str_contains($title, 'lớp 12') || str_contains($title, 'grade 12') || str_contains($title, 'thpt')) {
                $gradeId = 12;
                $levelId = 3;
            } elseif (str_contains($title, 'lớp 6')) {
                $gradeId = 6;
                $levelId = 2;
            } elseif (str_contains($title, 'lớp 8') || str_contains($title, 'lớp 9')) {
                $gradeId = 8;
                $levelId = 2;
            } else {
                $levelId = 3; // Mặc định THPT
                $gradeId = 10; // Mặc định Lớp 10
            }

            DB::table('quizzes')->where('id', $q->id)->update([
                'education_level_id' => $levelId,
                'grade_id' => $gradeId,
                'subject_id' => $subjectId,
            ]);

            DB::table('questions')->where('quiz_id', $q->id)->update([
                'education_level_id' => $levelId,
                'grade_id' => $gradeId,
                'subject_id' => $subjectId,
            ]);
        }

        // Cập nhật các câu hỏi độc lập chưa thuộc quiz nào
        DB::table('questions')->whereNull('education_level_id')->update([
            'education_level_id' => 3,
            'grade_id' => 10,
            'subject_id' => 1,
        ]);
    }

    public function down(): void
    {
    }
};
