<?php

namespace Database\Seeders;

use App\Models\EducationLevel;
use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EducationTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks while seeding
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('subject_grade')->truncate();
        Subject::truncate();
        Grade::truncate();
        EducationLevel::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Dữ liệu Cấp học (Education Levels)
        $levelsData = [
            [
                'code' => 'primary',
                'name' => 'Tiểu học',
                'order' => 1,
            ],
            [
                'code' => 'secondary',
                'name' => 'THCS (Cấp 2)',
                'order' => 2,
            ],
            [
                'code' => 'high_school',
                'name' => 'THPT (Cấp 3)',
                'order' => 3,
            ],
            [
                'code' => 'university',
                'name' => 'Đại học / Cao đẳng',
                'order' => 4,
            ],
            [
                'code' => 'other',
                'name' => 'Luyện thi / Kỹ năng / Khác',
                'order' => 5,
            ],
        ];

        $createdLevels = [];
        foreach ($levelsData as $ld) {
            $createdLevels[$ld['code']] = EducationLevel::create($ld);
        }

        // 2. Dữ liệu Khối lớp (Grades)
        $gradesData = [
            // Tiểu học
            ['level' => 'primary', 'code' => 'grade_1', 'name' => 'Lớp 1', 'level_number' => 1, 'order' => 1],
            ['level' => 'primary', 'code' => 'grade_2', 'name' => 'Lớp 2', 'level_number' => 2, 'order' => 2],
            ['level' => 'primary', 'code' => 'grade_3', 'name' => 'Lớp 3', 'level_number' => 3, 'order' => 3],
            ['level' => 'primary', 'code' => 'grade_4', 'name' => 'Lớp 4', 'level_number' => 4, 'order' => 4],
            ['level' => 'primary', 'code' => 'grade_5', 'name' => 'Lớp 5', 'level_number' => 5, 'order' => 5],

            // THCS
            ['level' => 'secondary', 'code' => 'grade_6', 'name' => 'Lớp 6', 'level_number' => 6, 'order' => 6],
            ['level' => 'secondary', 'code' => 'grade_7', 'name' => 'Lớp 7', 'level_number' => 7, 'order' => 7],
            ['level' => 'secondary', 'code' => 'grade_8', 'name' => 'Lớp 8', 'level_number' => 8, 'order' => 8],
            ['level' => 'secondary', 'code' => 'grade_9', 'name' => 'Lớp 9', 'level_number' => 9, 'order' => 9],

            // THPT
            ['level' => 'high_school', 'code' => 'grade_10', 'name' => 'Lớp 10', 'level_number' => 10, 'order' => 10],
            ['level' => 'high_school', 'code' => 'grade_11', 'name' => 'Lớp 11', 'level_number' => 11, 'order' => 11],
            ['level' => 'high_school', 'code' => 'grade_12', 'name' => 'Lớp 12', 'level_number' => 12, 'order' => 12],

            // Đại học
            ['level' => 'university', 'code' => 'university_gen', 'name' => 'Đại học / Cao đẳng', 'level_number' => null, 'order' => 13],

            // Khác
            ['level' => 'other', 'code' => 'other_gen', 'name' => 'Tổng hợp / Chứng chỉ', 'level_number' => null, 'order' => 14],
        ];

        $createdGrades = [];
        foreach ($gradesData as $gd) {
            $levelId = $createdLevels[$gd['level']]->id;
            $createdGrades[$gd['code']] = Grade::create([
                'education_level_id' => $levelId,
                'code' => $gd['code'],
                'name' => $gd['name'],
                'level_number' => $gd['level_number'],
                'order' => $gd['order'],
            ]);
        }

        // 3. Dữ liệu Bộ môn (Subjects)
        $subjectsData = [
            ['code' => 'math', 'name' => 'Toán học', 'icon' => 'Calculator', 'category_group' => 'natural', 'order' => 1],
            ['code' => 'literature', 'name' => 'Ngữ văn', 'icon' => 'BookOpen', 'category_group' => 'social', 'order' => 2],
            ['code' => 'english', 'name' => 'Tiếng Anh', 'icon' => 'Languages', 'category_group' => 'foreign_language', 'order' => 3],
            ['code' => 'physics', 'name' => 'Vật lý', 'icon' => 'Atom', 'category_group' => 'natural', 'order' => 4],
            ['code' => 'chemistry', 'name' => 'Hóa học', 'icon' => 'FlaskConical', 'category_group' => 'natural', 'order' => 5],
            ['code' => 'biology', 'name' => 'Sinh học', 'icon' => 'Dna', 'category_group' => 'natural', 'order' => 6],
            ['code' => 'history', 'name' => 'Lịch sử', 'icon' => 'Landmark', 'category_group' => 'social', 'order' => 7],
            ['code' => 'geography', 'name' => 'Địa lý', 'icon' => 'Globe', 'category_group' => 'social', 'order' => 8],
            ['code' => 'civics', 'name' => 'GDCD / KTPL', 'icon' => 'Scale', 'category_group' => 'social', 'order' => 9],
            ['code' => 'informatics', 'name' => 'Tin học / CNTT', 'icon' => 'Laptop', 'category_group' => 'technology', 'order' => 10],
            ['code' => 'technology', 'name' => 'Công nghệ', 'icon' => 'Wrench', 'category_group' => 'technology', 'order' => 11],
            ['code' => 'skills', 'name' => 'Kỹ năng sống & Khác', 'icon' => 'Lightbulb', 'category_group' => 'other', 'order' => 12],
        ];

        $createdSubjects = [];
        foreach ($subjectsData as $sd) {
            $createdSubjects[$sd['code']] = Subject::create($sd);
        }

        // 4. Liên kết môn học với các khối lớp thích hợp (subject_grade)
        // Tất cả các lớp đều có Toán, Văn, Anh, Tin
        $universalSubjectCodes = ['math', 'literature', 'english', 'informatics', 'skills'];
        // THCS & THPT có thêm Vật Lý, Hóa Học, Sinh Học, Lịch Sử, Địa Lý, GDCD
        $secondaryHighSubjects = ['physics', 'chemistry', 'biology', 'history', 'geography', 'civics', 'technology'];

        foreach ($createdGrades as $gradeCode => $gradeObj) {
            $gradeSubjectIds = [];

            // Gán universal subjects cho tất cả lớp
            foreach ($universalSubjectCodes as $sc) {
                if (isset($createdSubjects[$sc])) {
                    $gradeSubjectIds[] = $createdSubjects[$sc]->id;
                }
            }

            // Gán môn KHTN / KHXH cho Lớp 6 -> 12 và Đại học, Khác
            if (!in_array($gradeCode, ['grade_1', 'grade_2', 'grade_3', 'grade_4', 'grade_5'], true)) {
                foreach ($secondaryHighSubjects as $sc) {
                    if (isset($createdSubjects[$sc])) {
                        $gradeSubjectIds[] = $createdSubjects[$sc]->id;
                    }
                }
            }

            $gradeObj->subjects()->sync($gradeSubjectIds);
        }
    }
}
