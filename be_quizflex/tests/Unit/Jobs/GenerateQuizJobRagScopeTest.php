<?php

namespace Tests\Unit\Jobs;

use App\Jobs\GenerateQuizJob;
use App\Models\AiJob;
use App\Models\Grade;
use App\Models\Subject;
use App\Services\RAG\Curriculum\CurriculumSubjectResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class GenerateQuizJobRagScopeTest extends TestCase
{
    #[DataProvider('validPromptProvider')]
    public function test_it_resolves_rag_scope_from_prompt(
        string $prompt,
        string $expectedSubject,
        int $expectedGrade,
    ): void {
        $scope = $this->resolveScope($prompt);

        $this->assertSame(
            $expectedSubject,
            $scope['subject']
        );

        $this->assertSame(
            $expectedGrade,
            $scope['grade']
        );
    }

    public function test_it_disables_rag_when_scope_is_incomplete(): void
    {
        $scope = $this->resolveScope(
            'Tạo câu hỏi về hàm số bậc hai'
        );

        $this->assertSame([
            'subject' => null,
            'grade' => null,
        ], $scope);
    }

    public function test_it_uses_the_taxonomy_saved_on_the_job(): void
    {
        $job = new AiJob([
            'subject_id' => 3,
            'grade_id' => 10,
            'topic_name' => 'Environmental vocabulary',
            'curriculum_unit_ids' => [101, 102],
            'prompt' => 'Create vocabulary questions',
        ]);

        $job->setRelation(
            'subject',
            new Subject([
                'code' => 'english',
                'name' => 'Tiếng Anh',
            ])
        );

        $job->setRelation(
            'grade',
            new Grade([
                'level_number' => 10,
                'name' => 'Lớp 10',
            ])
        );

        $method = new ReflectionMethod(
            GenerateQuizJob::class,
            'resolveRagScopeFromJob'
        );

        $scope = $method->invoke(
            new GenerateQuizJob('test-uuid'),
            $job,
            new CurriculumSubjectResolver(),
        );

        $this->assertSame('Tiếng Anh', $scope['subject']);
        $this->assertSame(10, $scope['grade']);
        $this->assertSame(
            [101, 102],
            $scope['curriculum_unit_ids']
        );
    }

    public static function validPromptProvider(): array
    {
        return [
            'Vietnamese math prompt' => [
                'Tạo 10 câu hỏi Toán lớp 10 về hàm số',
                'Toán',
                10,
            ],
            'English physics prompt' => [
                'Create questions about Physics grade 12',
                'Vật lý',
                12,
            ],
        ];
    }

    /**
     * @return array{subject: ?string, grade: ?int}
     */
    private function resolveScope(string $prompt): array
    {
        $job = new GenerateQuizJob('test-uuid');

        $method = new ReflectionMethod(
            GenerateQuizJob::class,
            'resolveRagScopeFromPrompt'
        );

        return $method->invoke(
            $job,
            $prompt
        );
    }
}
