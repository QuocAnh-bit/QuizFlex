<?php

namespace Tests\Unit;

use App\Models\CurriculumUnit;
use App\Services\RAG\Curriculum\CurriculumOptionService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class CurriculumOptionServiceTest extends TestCase
{
    #[DataProvider('labelCases')]
    public function test_it_uses_subject_appropriate_labels(
        array $attributes,
        string $expected,
    ): void {
        $service = new CurriculumOptionService();
        $method = new ReflectionMethod(
            $service,
            'resolveMainTopic'
        );

        $unit = new CurriculumUnit();
        $unit->forceFill($attributes);

        $this->assertSame(
            $expected,
            $method->invoke($service, $unit)
        );
    }

    public static function labelCases(): array
    {
        return [
            'math keeps its curriculum strand' => [
                [
                    'subject' => 'Toán',
                    'topic' => 'Số và phép tính',
                    'title' => 'Phép cộng trong phạm vi 10',
                ],
                'Số và phép tính',
            ],

            'literature shows the concrete content' => [
                [
                    'subject' => 'Ngữ văn',
                    'topic' => 'Tập làm văn',
                    'title' => 'Văn miêu tả đồ vật, cây cối, con vật',
                ],
                'Văn miêu tả đồ vật, cây cối, con vật',
            ],

            'english grammar shows the concrete lesson' => [
                [
                    'subject' => 'Tiếng Anh',
                    'domain' => 'Kiến thức ngôn ngữ',
                    'topic' => 'Grammar & Daily Topics',
                    'title' => 'Present Simple, Likes & Dislikes',
                ],
                'Present Simple, Likes & Dislikes',
            ],

            'english communication theme keeps its topic' => [
                [
                    'subject' => 'Tiếng Anh',
                    'domain' => 'Chủ đề',
                    'topic' => 'Gia đình',
                    'title' => 'Từ vựng về các thành viên',
                ],
                'Gia đình',
            ],
        ];
    }
}
