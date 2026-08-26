<?php

namespace Tests\Unit\AI;

use App\AI\Validation\QuizResponseValidator;
use PHPUnit\Framework\TestCase;

class QuizResponseValidatorTest extends TestCase
{
    public function test_valid_quiz_passes(): void
    {
        $data = [
            'title' => 'Test quiz',
            'questions' => [
                [
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
                ],
            ],
        ];

        $validator = new QuizResponseValidator();

        $this->assertTrue(
            $validator->isValid($data)
        );
    }

    public function test_quiz_with_two_correct_answers_fails(): void
    {
        $data = [
            'questions' => [
                [
                    'content' => 'Question?',
                    'answers' => [
                        [
                            'content' => 'A',
                            'is_correct' => true,
                        ],
                        [
                            'content' => 'B',
                            'is_correct' => true,
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
                ],
            ],
        ];

        $validator = new QuizResponseValidator();

        $this->assertFalse(
            $validator->isValid($data)
        );
    }

    public function test_quiz_without_questions_fails(): void
    {
        $validator = new QuizResponseValidator();

        $this->assertFalse(
            $validator->isValid([])
        );
    }
}
