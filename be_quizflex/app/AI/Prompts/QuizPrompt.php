<?php

namespace App\AI\Prompts;

class QuizPrompt
{
    public static function textToQuizJson(string $text): string
    {
        return <<<PROMPT
You are a quiz OCR parser.

Task:
Convert OCR quiz text into valid JSON.

Rules:
- Return ONLY valid JSON
- No markdown
- No explanation
- Extract all questions found in the text
- Fix obvious OCR errors in Vietnamese accents and spelling
- Do not change the meaning of the question
- Do not invent missing content
- Keep math formulas, chemical formulas, code snippets as accurately as possible
- Options must be A, B, C, D
- If an option is missing, set it to null
- If correct answer is not found, set correct_answer = null
- correct_answer must be one of: "A", "B", "C", "D", or null
- Remove duplicated whitespace and broken lines caused by OCR
- Preserve question order

JSON format:
{
  "questions": [
    {
      "question": "",
      "options": {
        "A": "",
        "B": "",
        "C": "",
        "D": ""
      },
      "correct_answer": null
    }
  ]
}

OCR TEXT:
{$text}
PROMPT;
    }
}
