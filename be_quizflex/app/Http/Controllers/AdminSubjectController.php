<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminSubjectController extends Controller
{
    private function clearTaxonomyCache()
    {
        Cache::forget('education_taxonomy_tree_v3');
    }

    /**
     * Danh sách bộ môn đang hoạt động
     */
    public function index(Request $request)
    {
        $query = Subject::with(['grades.educationLevel'])
            ->withCount(['quizzes', 'questions']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('topic_name', 'like', "%{$search}%");
            });
        }

        if ($group = $request->input('category_group')) {
            $query->where('category_group', $group);
        }

        if ($gradeId = $request->input('grade_id')) {
            $query->whereHas('grades', function ($q) use ($gradeId) {
                $q->where('grades.id', $gradeId);
            });
        }

        $subjects = $query->orderBy('order', 'asc')->orderBy('id', 'asc')->get();

        $activeCount = Subject::count();
        $trashedCount = Subject::onlyTrashed()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'subjects' => $subjects,
                'stats' => [
                    'total' => $activeCount,
                    'trashed' => $trashedCount,
                ],
            ],
        ]);
    }

    /**
     * Danh sách môn học trong Thùng rác (Đã xóa mềm)
     */
    public function trash(Request $request)
    {
        $subjects = Subject::onlyTrashed()
            ->with(['grades.educationLevel'])
            ->withCount(['quizzes', 'questions'])
            ->orderBy('deleted_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subjects,
        ]);
    }

    /**
     * Tạo mới một bộ môn
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:subjects,code',
            'icon' => 'nullable|string|max:50',
            'category_group' => 'nullable|string|max:50',
            'order' => 'nullable|integer',
            'grade_ids' => 'nullable|array',
            'grade_ids.*' => 'integer|exists:grades,id',
        ]);

        $subject = Subject::create([
            'name' => trim($request->name),
            'code' => strtolower(trim($request->code)),
            'icon' => $request->icon ?? '📚',
            'category_group' => $request->category_group ?? 'general',
            'order' => $request->order ?? 0,
        ]);

        if ($request->has('grade_ids')) {
            $subject->grades()->sync($request->input('grade_ids', []));
        }

        $this->clearTaxonomyCache();

        return response()->json([
            'success' => true,
            'message' => "Tạo môn học '{$subject->name}' thành công.",
            'data' => $subject,
        ], 201);
    }

    /**
     * Chi tiết một bộ môn
     */
    public function show($id)
    {
        $subject = Subject::withTrashed()
            ->withCount(['quizzes', 'questions'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $subject,
        ]);
    }

    /**
     * Cập nhật thông tin bộ môn
     */
    public function update(Request $request, $id)
    {
        $subject = Subject::withTrashed()->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:subjects,code,' . $subject->id,
            'icon' => 'nullable|string|max:50',
            'category_group' => 'nullable|string|max:50',
            'order' => 'nullable|integer',
            'grade_ids' => 'nullable|array',
            'grade_ids.*' => 'integer|exists:grades,id',
        ]);

        $subject->update([
            'name' => trim($request->name),
            'code' => strtolower(trim($request->code)),
            'icon' => $request->icon ?? '📚',
            'category_group' => $request->category_group ?? 'general',
            'order' => $request->order ?? 0,
        ]);

        if ($request->has('grade_ids')) {
            $subject->grades()->sync($request->input('grade_ids', []));
        }

        $this->clearTaxonomyCache();

        return response()->json([
            'success' => true,
            'message' => "Cập nhật môn học '{$subject->name}' thành công.",
            'data' => $subject,
        ]);
    }

    /**
     * Xóa mềm môn học (Soft Delete)
     */
    public function destroy($id)
    {
        $subject = Subject::findOrFail($id);
        $subjectName = $subject->name;
        $subject->delete();

        $this->clearTaxonomyCache();

        return response()->json([
            'success' => true,
            'message' => "Đã chuyển môn học '{$subjectName}' vào thùng rác.",
        ]);
    }

    /**
     * Khôi phục môn học từ Thùng rác
     */
    public function restore($id)
    {
        $subject = Subject::onlyTrashed()->findOrFail($id);
        $subject->restore();

        $this->clearTaxonomyCache();

        return response()->json([
            'success' => true,
            'message' => "Đã khôi phục môn học '{$subject->name}'.",
            'data' => $subject->load(['grades.educationLevel']),
        ]);
    }

    /**
     * Xóa vĩnh viễn môn học
     */
    public function forceDelete($id)
    {
        $subject = Subject::onlyTrashed()->withCount(['quizzes', 'questions'])->findOrFail($id);

        if ($subject->quizzes_count > 0 || $subject->questions_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "Không thể xóa vĩnh viễn! Môn học '{$subject->name}' đang được liên kết với {$subject->quizzes_count} Quiz và {$subject->questions_count} Câu hỏi.",
            ], 422);
        }

        $subject->grades()->detach();
        $subject->forceDelete();

        $this->clearTaxonomyCache();

        return response()->json([
            'success' => true,
            'message' => "Đã xóa vĩnh viễn môn học '{$subject->name}' khỏi hệ thống.",
        ]);
    }
}
