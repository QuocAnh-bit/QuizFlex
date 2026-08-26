<?php

namespace App\AI\Normalization;

final class QuizResponseNormalizer
{
    public function normalize(
        array $data,
        ?int $limit = null
    ): array {
        $title = trim(
            (string) ($data['title'] ?? '')
        );

        $questions = collect(
            $data['questions']
        );

        if (
            $limit !== null
            && $questions->count() > $limit
        ) {
            $questions = $questions->take($limit);
        }

        return [
            'title' => $title !== ''
                ? $title
                : 'AI Generated Quiz',

            'questions' => $questions
                ->map(function (
                    array $question
                ): array {
                    $answers = collect(
                        $question['answers']
                    )
                        ->map(
                            fn(array $answer) => [
                                'content' => trim(
                                    (string) $answer['content']
                                ),
                                'is_correct' => (bool)
                                $answer['is_correct'],
                            ]
                        )
                        ->all();

                    shuffle($answers);

                    return [
                        'content' => trim(
                            (string) $question['content']
                        ),
                        'answers' => $answers,
                    ];
                })
                ->values()
                ->all(),
        ];
    }
}
