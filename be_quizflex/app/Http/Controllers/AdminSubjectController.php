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
     * Danh sách bộ môn đang hoạt động (Hỗ trợ phân trang Server-side, Tìm kiếm & Lọc)
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

        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $paginated = $query->orderBy('order', 'asc')->orderBy('id', 'asc')->paginate($perPage);

        $activeCount = Subject::count();
        $trashedCount = Subject::onlyTrashed()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'subjects' => $paginated->items(),
                'total' => $paginated->total(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'stats' => [
                    'total' => $activeCount,
                    'trashed' => $trashedCount,
                ],
            ],
        ]);
    }

    /**
     * Danh sách môn học trong Thùng rác (Hỗ trợ phân trang Server-side độc lập)
     */
    public function trash(Request $request)
    {
        $query = Subject::onlyTrashed()
            ->with(['grades.educationLevel'])
            ->withCount(['quizzes', 'questions']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('topic_name', 'like', "%{$search}%");
            });
        }

        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $paginated = $query->orderBy('deleted_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'subjects' => $paginated->items(),
                'total' => $paginated->total(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
            ],
        ]);
    }

    /**
     * Tạo mới một bộ môn
     */
    /**
     * Tạo mới một bộ môn
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50',
            'icon' => 'nullable|string|max:50',
            'category_group' => 'nullable|string|max:50',
            'order' => 'nullable|integer',
            'grade_ids' => 'nullable|array',
            'grade_ids.*' => 'integer|exists:grades,id',
        ], [
            'name.required' => 'Vui lòng nhập tên bộ môn.',
            'code.required' => 'Vui lòng nhập mã bộ môn (code).',
        ]);

        $name = trim($request->name);
        $code = strtolower(trim($request->code));

        // 1. Kiểm tra trùng lặp ở danh sách đang hoạt động
        $existingActive = Subject::where(function ($q) use ($name, $code) {
            $q->where('code', $code)->orWhere('name', $name);
        })->first();

        if ($existingActive) {
            $matchedField = (strtolower($existingActive->code) === $code) 
                ? "Mã môn (code) '{$code}'" 
                : "Tên bộ môn '{$name}'";

            return response()->json([
                'success' => false,
                'message' => "Tạo thất bại: {$matchedField} đã tồn tại trong danh sách chính (Môn: '{$existingActive->name}'). Vui lòng kiểm tra lại.",
            ], 422);
        }

        // 2. Kiểm tra trùng lặp ở danh sách Thùng rác (Đã xóa mềm)
        $existingTrashed = Subject::onlyTrashed()->where(function ($q) use ($name, $code) {
            $q->where('code', $code)->orWhere('name', $name);
        })->first();

        if ($existingTrashed) {
            $matchedField = (strtolower($existingTrashed->code) === $code) 
                ? "Mã môn (code) '{$code}'" 
                : "Tên bộ môn '{$name}'";

            return response()->json([
                'success' => false,
                'message' => "Tạo thất bại: {$matchedField} đã từng tạo và đang nằm trong Thùng rác. Vui lòng sang tab 'Thùng rác' để khôi phục thay vì tạo trùng lặp.",
            ], 422);
        }

        $subject = Subject::create([
            'name' => $name,
            'code' => $code,
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
            'code' => 'required|string|max:50',
            'icon' => 'nullable|string|max:50',
            'category_group' => 'nullable|string|max:50',
            'order' => 'nullable|integer',
            'grade_ids' => 'nullable|array',
            'grade_ids.*' => 'integer|exists:grades,id',
        ], [
            'name.required' => 'Vui lòng nhập tên bộ môn.',
            'code.required' => 'Vui lòng nhập mã bộ môn (code).',
        ]);

        $name = trim($request->name);
        $code = strtolower(trim($request->code));

        // Kiểm tra trùng lặp với môn học khác
        $existing = Subject::withTrashed()
            ->where('id', '!=', $subject->id)
            ->where(function ($q) use ($name, $code) {
                $q->where('code', $code)->orWhere('name', $name);
            })->first();

        if ($existing) {
            $matchedField = (strtolower($existing->code) === $code) 
                ? "Mã môn (code) '{$code}'" 
                : "Tên bộ môn '{$name}'";
            $statusLocation = $existing->trashed() ? 'trong Thùng rác' : 'ở danh sách chính';

            return response()->json([
                'success' => false,
                'message' => "Cập nhật thất bại: {$matchedField} trùng với môn học '{$existing->name}' ({$statusLocation}).",
            ], 422);
        }

        $subject->update([
            'name' => $name,
            'code' => $code,
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
