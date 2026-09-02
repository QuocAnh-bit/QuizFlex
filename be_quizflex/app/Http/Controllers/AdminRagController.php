<?php

namespace App\Http\Controllers;

use App\Models\CurriculumChunk;
use App\Models\CurriculumDocument;
use App\Models\CurriculumUnit;
use App\Services\RAG\Retrieval\CurriculumRetrieverService;
use Illuminate\Http\Request;

/**
 * Read-only observability endpoints for the curriculum RAG corpus.
 * The RAG ingestion, chunking and embedding pipeline intentionally stays outside this controller.
 */
class AdminRagController extends Controller
{
    public function overview()
    {
        $documents = CurriculumDocument::count();
        $units = CurriculumUnit::count();
        $chunks = CurriculumChunk::count();
        $embedded = CurriculumChunk::where('embedding_status', 'embedded')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'documents' => $documents,
                'units' => $units,
                'chunks' => $chunks,
                'embedded_chunks' => $embedded,
                'embedding_percent' => $chunks > 0 ? round(($embedded / $chunks) * 100, 1) : 0,
            ],
        ]);
    }

    public function documents(Request $request)
    {
        $query = CurriculumDocument::query()
            ->withCount('units')
            ->selectSub(
                CurriculumChunk::query()
                    ->selectRaw('count(*)')
                    ->join('curriculum_units', 'curriculum_units.id', '=', 'curriculum_chunks.unit_id')
                    ->whereColumn('curriculum_units.document_id', 'curriculum_documents.id'),
                'chunks_count'
            );

        $query->when($request->string('search')->trim()->value(), fn ($q, $search) => $q->where(fn ($nested) => $nested
            ->where('title', 'like', "%{$search}%")
            ->orWhere('subject', 'like', "%{$search}%")
            ->orWhere('publisher', 'like', "%{$search}%")));
        $query->when($request->string('subject')->trim()->value(), fn ($q, $subject) => $q->where('subject', $subject));
        $query->when($request->string('status')->trim()->value(), fn ($q, $status) => $q->where('status', $status));

        return response()->json($this->paginate($query->latest('updated_at'), $request, [
            'subjects' => CurriculumDocument::query()->whereNotNull('subject')->distinct()->orderBy('subject')->pluck('subject')->values(),
        ]));
    }

    public function units(Request $request)
    {
        $query = CurriculumUnit::query()->with('document:id,title,subject')->withCount('chunks');

        $query->when($request->string('search')->trim()->value(), fn ($q, $search) => $q->where(fn ($nested) => $nested
            ->where('title', 'like', "%{$search}%")
            ->orWhere('content', 'like', "%{$search}%")
            ->orWhere('topic', 'like', "%{$search}%")));
        $query->when($request->string('subject')->trim()->value(), fn ($q, $subject) => $q->where('subject', $subject));
        $query->when($request->string('domain')->trim()->value(), fn ($q, $domain) => $q->where('domain', $domain));
        $query->when($request->string('topic')->trim()->value(), fn ($q, $topic) => $q->where('topic', $topic));
        $query->when($request->integer('grade'), fn ($q, $grade) => $q->where(fn ($nested) => $nested
            ->whereNull('grade_min')->orWhere('grade_min', '<=', $grade))
            ->where(fn ($nested) => $nested->whereNull('grade_max')->orWhere('grade_max', '>=', $grade)));

        return response()->json($this->paginate($query->latest('updated_at'), $request, [
            'subjects' => CurriculumUnit::query()->whereNotNull('subject')->distinct()->orderBy('subject')->pluck('subject')->values(),
            'domains' => CurriculumUnit::query()->whereNotNull('domain')->distinct()->orderBy('domain')->pluck('domain')->values(),
            'topics' => CurriculumUnit::query()->whereNotNull('topic')->distinct()->orderBy('topic')->pluck('topic')->values(),
        ]));
    }

    public function chunks(Request $request)
    {
        $query = CurriculumChunk::query()->with('unit:id,subject,grade_min,grade_max,topic,document_id');

        $query->when($request->string('search')->trim()->value(), fn ($q, $search) => $q->where(fn ($nested) => $nested
            ->where('content', 'like', "%{$search}%")
            ->orWhere('embedding_text', 'like', "%{$search}%")
            ->orWhere('qdrant_point_id', 'like', "%{$search}%")));
        $query->when($request->string('embedding_status')->trim()->value(), fn ($q, $status) => $q->where('embedding_status', $status));
        $query->when($request->string('subject')->trim()->value(), fn ($q, $subject) => $q->whereHas('unit', fn ($unit) => $unit->where('subject', $subject)));
        $query->when($request->integer('grade'), fn ($q, $grade) => $q->whereHas('unit', fn ($unit) => $unit
            ->where(fn ($nested) => $nested->whereNull('grade_min')->orWhere('grade_min', '<=', $grade))
            ->where(fn ($nested) => $nested->whereNull('grade_max')->orWhere('grade_max', '>=', $grade))));

        return response()->json($this->paginate($query->latest('updated_at'), $request, [
            'subjects' => CurriculumUnit::query()->whereNotNull('subject')->distinct()->orderBy('subject')->pluck('subject')->values(),
        ]));
    }

    /** Runs the existing retrieval pipeline only; it never mutates the corpus or Qdrant. */
    public function testRetrieval(Request $request, CurriculumRetrieverService $retriever)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:100'],
            'grade' => ['required', 'integer', 'between:1,12'],
            'query' => ['required', 'string', 'max:2000'],
        ]);

        $results = $retriever->retrieve(
            subject: $data['subject'],
            grade: (int) $data['grade'],
            query: $data['query'],
            limit: 5,
        );

        // The retriever intentionally returns compact source data. Enrich only the response
        // for the Admin inspector so the ingestion/retrieval pipeline remains untouched.
        $units = CurriculumUnit::query()
            ->with('document:id,title')
            ->whereIn('id', collect($results)->pluck('unit_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $payload = collect($results)->map(function (array $result) use ($units) {
            $unit = $units->get($result['unit_id'] ?? null);

            return array_merge($result, [
                'document_title' => $unit?->document?->title,
                'page_start' => $unit?->source_page_start,
                'page_end' => $unit?->source_page_end,
            ]);
        })->values();

        return response()->json(['success' => true, 'data' => $payload]);
    }

    private function paginate($query, Request $request, array $meta = []): array
    {
        $records = $query->paginate(min(50, max(5, $request->integer('per_page', 10))));

        return [
            'success' => true,
            'data' => $records->items(),
            'meta' => array_merge($meta, [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ]),
        ];
    }
}
