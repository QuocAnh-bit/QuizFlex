<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Subject;
use App\Services\RAG\Curriculum\CurriculumOptionService;
use App\Services\RAG\Curriculum\CurriculumSubjectResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurriculumOptionController extends Controller
{
    public function index(
        Request $request,
        CurriculumSubjectResolver $subjectResolver,
        CurriculumOptionService $optionService,
    ): JsonResponse {
        $data = $request->validate([
            'education_level_id' => [
                'required',
                'integer',
                'exists:education_levels,id',
            ],

            'grade_id' => [
                'required',
                'integer',
                'exists:grades,id',
            ],

            'subject_id' => [
                'required',
                'integer',
                'exists:subjects,id',
            ],
        ]);

        /*
         * Kiểm tra lớp có thực sự thuộc
         * cấp học đã chọn hay không.
         */
        $grade = Grade::query()
            ->whereKey($data['grade_id'])
            ->where(
                'education_level_id',
                $data['education_level_id']
            )
            ->first();

        if (!$grade) {
            return response()->json([
                'success' => false,
                'message' =>
                'Lớp không thuộc cấp học đã chọn.',
            ], 422);
        }

        /*
         * Kiểm tra môn có được cấu hình
         * cho lớp đã chọn hay không.
         */
        $subject = Subject::query()
            ->whereKey($data['subject_id'])
            ->first();

        $subjectBelongsToGrade =
            $grade->subjects()
            ->where(
                'subjects.id',
                $subject->id
            )
            ->exists();

        if (!$subjectBelongsToGrade) {
            return response()->json([
                'success' => false,
                'message' =>
                'Môn học không thuộc lớp đã chọn.',
            ], 422);
        }

        /*
         * Chuyển taxonomy subject sang
         * subject/domain trong curriculum_units.
         */
        $scope = $subjectResolver->resolve(
            subject: $subject,
            grade: $grade,
        );

        /*
         * Taxonomy hợp lệ nhưng RAG chưa
         * có dữ liệu cho môn này.
         */
        if ($scope === null) {
            return response()->json([
                'success' => true,

                'message' =>
                "Chưa có dữ liệu chương trình "
                    . "cho môn {$subject->name} "
                    . "lớp {$grade->level_number}.",

                'data' => [
                    'available' => false,
                    'options' => [],
                ],
            ]);
        }

        $options = $optionService->options(
            subject: $scope['subject'],
            grade: (int) $grade->level_number,
            domain: $scope['domain'],
        );

        $available = count($options) > 0;

        return response()->json([
            'success' => true,

            'message' => $available
                ? 'Danh sách chủ đề chính.'
                : "Chưa có dữ liệu chương trình "
                . "cho môn {$subject->name} "
                . "lớp {$grade->level_number}.",

            'data' => [
                'available' => $available,
                'options' => $options,
            ],
        ]);
    }
}
