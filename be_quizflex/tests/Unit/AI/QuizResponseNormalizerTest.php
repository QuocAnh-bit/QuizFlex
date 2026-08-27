<?php

namespace Tests\Unit\AI;

use App\AI\Normalization\QuizResponseNormalizer;
use Tests\TestCase;

class QuizResponseNormalizerTest extends TestCase
{
    public function test_it_normalizes_quiz(): void
    {
        $data = [
            'title' => '  Test Quiz  ',
            'questions' => [
                [
                    'content' => '  Question one?  ',
                    'answers' => [
                        [
                            'content' => '  A  ',
                            'is_correct' => true,
                        ],
                        [
                            'content' => '  B  ',
                            'is_correct' => false,
                        ],
                        [
                            'content' => '  C  ',
                            'is_correct' => false,
                        ],
                        [
                            'content' => '  D  ',
                            'is_correct' => false,
                        ],
                    ],
                ],
            ],
        ];

        $normalizer =
            new QuizResponseNormalizer();

        $result = $normalizer->normalize($data);

        $this->assertSame(
            'Test Quiz',
            $result['title']
        );

        $this->assertSame(
            'Question one?',
            $result['questions'][0]['content']
        );

        $this->assertCount(
            4,
            $result['questions'][0]['answers']
        );

        $answerContents = collect(
            $result['questions'][0]['answers']
        )
            ->pluck('content')
            ->sort()
            ->values()
            ->all();

        $this->assertSame(
            ['A', 'B', 'C', 'D'],
            $answerContents
        );

        $correctCount = collect(
            $result['questions'][0]['answers']
        )
            ->where('is_correct', true)
            ->count();

        $this->assertSame(1, $correctCount);
    }

    public function test_it_uses_default_title(): void
    {
        $data = [
            'title' => '   ',
            'questions' => [],
        ];

        $result = (
            new QuizResponseNormalizer()
        )->normalize($data);

        $this->assertSame(
            'AI Generated Quiz',
            $result['title']
        );
    }

    public function test_it_limits_questions(): void
    {
        $question = [
            'content' => 'Question?',
            'answers' => [
                [
                    'content' => 'A',
                    'is_correct' => true,
                ],
                [
                    'content' => 'B',
                    'is_correct' => false,
                ],
                [
                    'content' => 'C',
                    'is_correct' => false,
                ],
                [
                    'content' => 'D',
                    'is_correct' => false,
                ],
            ],
        ];

        $data = [
            'title' => 'Quiz',
            'questions' => [
                $question,
                $question,
                $question,
            ],
        ];

        $result = (
            new QuizResponseNormalizer()
        )->normalize($data, 2);

        $this->assertCount(
            2,
            $result['questions']
        );
    }
}
