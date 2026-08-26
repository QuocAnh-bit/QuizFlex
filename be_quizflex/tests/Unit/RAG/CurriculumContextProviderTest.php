<?php

namespace Tests\Unit\RAG;

use App\Services\RAG\CurriculumContextProvider;
use App\Services\RAG\Retrieval\CurriculumRetrieverService;
use PHPUnit\Framework\TestCase;

class CurriculumContextProviderTest extends TestCase
{
    public function test_it_returns_context_and_trace_sources(): void
    {
        $results = [
            [
                'chunk_id' => 10,
                'unit_id' => 20,
                'document_id' => 30,
                'score' => 0.95,
                'subject' => 'Toán',
                'grade_min' => 10,
                'grade_max' => 10,
                'domain' => 'Đại số',
                'topic' => 'Hàm số',
                'section' => 'Hàm số bậc hai',
                'page_start' => 15,
                'page_end' => 16,
                'content' =>
                'Nội dung chương trình hàm số.',
            ],
        ];

        $retriever = $this->createMock(
            CurriculumRetrieverService::class
        );

        $retriever
            ->expects($this->once())
            ->method('retrieve')
            ->with(
                'Toán',
                10,
                'Hàm số bậc hai',
                6,
                null,
                [20]
            )
            ->willReturn($results);

        $retriever
            ->expects($this->once())
            ->method('buildContext')
            ->with($results)
            ->willReturn(
                '[NGỮ CẢNH 1] Nội dung hàm số.'
            );

        $provider = new CurriculumContextProvider(
            $retriever
        );

        $result = $provider->provide(
            subject: 'Toán',
            grade: 10,
            query: 'Hàm số bậc hai',
            curriculumUnitIds: [20],
        );

        $this->assertSame(
            '[NGỮ CẢNH 1] Nội dung hàm số.',
            $result['context']
        );

        $this->assertCount(
            1,
            $result['sources']
        );

        $this->assertSame(
            10,
            $result['sources'][0]['chunk_id']
        );

        $this->assertSame(
            0.95,
            $result['sources'][0]['score']
        );
    }
}
