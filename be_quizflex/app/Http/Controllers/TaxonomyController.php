<?php

namespace App\Http\Controllers;

use App\Models\EducationLevel;
use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TaxonomyController extends Controller
{
    /**
     * Lấy toàn bộ cây danh mục Cấp học -> Lớp học -> Bộ môn
     */
    public function tree(Request $request)
    {
        $tree = Cache::remember('education_taxonomy_tree_v3', 86400, function () {
            $levels = EducationLevel::query()
                ->with(['grades.subjects'])
                ->orderBy('order')
                ->get()
                ->map(function ($level) {
                    return [
                        'id' => $level->id,
                        'code' => $level->code,
                        'name' => $level->name,
                        'grades' => $level->grades->map(function ($grade) {
                            return [
                                'id' => $grade->id,
                                'code' => $grade->code,
                                'name' => $grade->name,
                                'level_number' => $grade->level_number,
                                'subjects' => $grade->subjects->map(function ($subject) {
                                    return [
                                        'id' => $subject->id,
                                        'code' => $subject->code,
                                        'name' => $subject->name,
                                        'icon' => $subject->icon,
                                        'category_group' => $subject->category_group,
                                    ];
                                })->values()->all(),
                            ];
                        })->values()->all(),
                    ];
                })->values()->all();

            $subjects = Subject::orderBy('order')->get()->map(function ($subject) {
                return [
                    'id' => $subject->id,
                    'code' => $subject->code,
                    'name' => $subject->name,
                    'icon' => $subject->icon,
                    'category_group' => $subject->category_group,
                ];
            })->values()->all();

            return [
                'education_levels' => $levels,
                'subjects' => $subjects,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Cây danh mục Cấp học, Lớp và Bộ môn',
            'data' => $tree,
        ]);
    }
}
