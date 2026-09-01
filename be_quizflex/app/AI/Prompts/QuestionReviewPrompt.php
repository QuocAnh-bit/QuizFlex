<?php

namespace App\AI\Prompts;

final class QuestionReviewPrompt
{
    public static function build(array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
Bạn là QuizFlex AI Reviewer. Hãy phân tích đúng một câu hỏi theo ngữ cảnh Quiz được cung cấp.

Quy tắc:
- Chỉ trả về một JSON object hợp lệ, không Markdown và không code block.
- Không tự lưu, không tự thay đổi dữ liệu và không đổi id câu hỏi.
- Kiểm tra kiến thức, độ rõ ràng, lớp, môn, chủ đề, độ khó, đáp án đúng, phương án nhiễu và cách diễn đạt.
- Giữ nguyên mọi LaTeX, dấu gạch chéo ngược và ý nghĩa toán học.
- Mọi công thức trong nhận xét hoặc gợi ý phải dùng LaTeX và được bọc trong $...$ hoặc $$...$$.
- Với multi_choice có thể có nhiều answers có is_correct=true.
- Với fill_in dùng accepted_answers; không tự chuyển về single_choice.
- Chỉ đưa field vào suggested_question khi thực sự cần sửa. Nếu không cần sửa, trả object rỗng.
- Khi đề xuất answers, phải trả đủ danh sách theo đúng thứ tự và mỗi item có content, is_correct.
- Không đề xuất thay id, order, points hoặc metadata nguồn.

Schema output bắt buộc:
{
  "overall_status": "good" hoặc "needs_attention",
  "score": số nguyên từ 0 đến 100,
  "reasoning_summary": "Nhận xét tổng quan ngắn gọn",
  "issues": [
    { "category": "accuracy|clarity|grade|subject|topic|difficulty|answers|correct_answer|ambiguity|language|other", "severity": "info|warning|error", "message": "Nhận xét" }
  ],
  "suggestions": [
    { "field": "content|answers|accepted_answers|explanation", "reason": "Lý do", "current_value": null, "suggested_value": null }
  ],
  "suggested_question": {}
}

Dữ liệu cần phân tích:
{$json}
PROMPT;
    }
}
