<?php

namespace App\Services\RAG\Curriculum;

use App\Models\Grade;
use App\Models\Subject;

final class CurriculumSubjectResolver
{
    /**
     * @return array{
     *     subject: string,
     *     domain: ?string
     * }|null
     */
    public function resolve(
        Subject $subject,
        Grade $grade,
    ): ?array {
        $code = trim(
            (string) $subject->code
        );

        $gradeNumber = (int) (
            $grade->level_number
        );

        if (
            $gradeNumber < 1
            || $gradeNumber > 12
        ) {
            return null;
        }

        /*
         * Các môn có tên ổn định
         * trong curriculum_units.
         */
        $directSubject = match ($code) {
            'math' => 'Toán',
            'literature' => 'Ngữ văn',
            'english' => 'Tiếng Anh',
            'civics' => 'Giáo dục công dân',

            default => null,
        };

        if ($directSubject !== null) {
            return [
                'subject' => $directSubject,
                'domain' => null,
            ];
        }

        /*
         * THCS đang dùng môn tích hợp
         * Khoa học tự nhiên.
         */
        if (
            in_array(
                $code,
                [
                    'physics',
                    'chemistry',
                    'biology',
                ],
                true
            )
            && $gradeNumber >= 6
            && $gradeNumber <= 9
        ) {
            return [
                'subject' =>
                'Khoa học tự nhiên',

                /*
                 * Dữ liệu hiện tại chưa có
                 * domain ổn định để tách riêng
                 * Lý, Hóa và Sinh.
                 */
                'domain' => null,
            ];
        }

        /*
         * THPT tách riêng ba môn.
         */
        if ($gradeNumber >= 10) {
            $scienceSubject = match ($code) {
                'physics' => 'Vật lí',
                'chemistry' => 'Hóa học',
                'biology' => 'Sinh học',

                default => null,
            };

            if ($scienceSubject !== null) {
                return [
                    'subject' =>
                    $scienceSubject,

                    'domain' => null,
                ];
            }
        }

        /*
         * THCS dùng môn tích hợp
         * Lịch sử và Địa lí.
         *
         * Có thể phân biệt bằng domain.
         */
        if (
            $gradeNumber >= 6
            && $gradeNumber <= 9
        ) {
            $integratedDomain = match ($code) {
                'history' => 'Lịch sử',
                'geography' => 'Địa lí',

                default => null,
            };

            if ($integratedDomain !== null) {
                return [
                    'subject' =>
                    'Lịch sử và Địa lí',

                    'domain' =>
                    $integratedDomain,
                ];
            }
        }

        /*
         * THPT tách riêng Lịch sử,
         * Địa lí.
         */
        if ($gradeNumber >= 10) {
            $socialSubject = match ($code) {
                'history' => 'Lịch sử',
                'geography' => 'Địa lí',

                default => null,
            };

            if ($socialSubject !== null) {
                return [
                    'subject' =>
                    $socialSubject,

                    'domain' => null,
                ];
            }
        }

        /*
         * Tin học, Công nghệ, Kỹ năng...
         * hiện chưa có dữ liệu tương ứng
         * trong curriculum_units.
         */
        return null;
    }
}
