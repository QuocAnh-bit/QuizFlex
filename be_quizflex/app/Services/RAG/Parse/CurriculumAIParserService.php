<?php

namespace App\Services\RAG\Parse;

use App\AI\Clients\OpenRouterClient;
use App\AI\DTOs\ChatRequest;
use RuntimeException;

class CurriculumAIParserService
{
    public function __construct(
        private OpenRouterClient $client
    ) {}
    /**
     * Parse một block curriculum.
     */
    public function parse(array $block): array
    {
        $model = trim(
            (string) config(
                'rag.openrouter.parser_model'
            )
        );

        if ($model === '') {
            throw new RuntimeException(
                'Thiếu OPENROUTER_PARSER_MODEL.'
            );
        }

        /*
     * Metadata do backend kiểm soát.
     */
        $subject = $block['subject'] ?? null;

        $gradeMin = isset($block['grade_min'])
            ? (int) $block['grade_min']
            : null;

        $gradeMax = isset($block['grade_max'])
            ? (int) $block['grade_max']
            : null;

        $blockType = (string) (
            $block['type']
            ?? 'generic_content'
        );

        $heading = isset($block['heading'])
            ? trim((string) $block['heading'])
            : null;

        $text = trim(
            (string) ($block['text'] ?? '')
        );

        if ($text === '') {
            throw new RuntimeException(
                'Block không có text.'
            );
        }

        /*
     * Prompt riêng của curriculum parser.
     */
        $systemPrompt =
            $this->buildSystemPrompt();

        $userPrompt =
            $this->buildUserPrompt(
                subject: $subject,
                gradeMin: $gradeMin,
                gradeMax: $gradeMax,
                blockType: $blockType,
                heading: $heading,
                text: $text,
            );

        /*
     * OpenRouterClient xử lý HTTP, JSON và lỗi.
     *
     * Parser vẫn giữ retry riêng vì đây là tác vụ
     * parse dữ liệu curriculum số lượng lớn.
     */
        $response = retry(
            3,
            fn() => $this->client->generateJson(
                new ChatRequest(
                    systemPrompt: $systemPrompt,
                    userPrompt: $userPrompt,
                    model: $model,
                    maxTokens: (int) config(
                        'rag.openrouter.parser_max_tokens',
                        8000
                    ),
                    temperature: 0,
                    responseFormat: [
                        'type' => 'json_schema',

                        'json_schema' => [
                            'name' =>
                            'curriculum_units',

                            'strict' =>
                            true,

                            'schema' =>
                            $this->schema(),
                        ],
                    ],
                )
            ),
            1500
        );

        $result = $response->data;

        if (
            !isset($result['units'])
            || !is_array($result['units'])
        ) {
            throw new RuntimeException(
                'AI trả JSON curriculum không hợp lệ.'
            );
        }

        /*
     * Không tin metadata do AI trả về.
     * Backend luôn ghi đè subject và grade.
     */
        foreach ($result['units'] as &$unit) {
            if (!is_array($unit)) {
                throw new RuntimeException(
                    'Curriculum unit không hợp lệ.'
                );
            }

            $unit['subject'] = $subject;
            $unit['grade_min'] = $gradeMin;
            $unit['grade_max'] = $gradeMax;
        }

        unset($unit);

        return $result;
    }


    /**
     * System prompt dùng chung.
     */
    private function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
Bạn là bộ phân tích dữ liệu Chương trình giáo dục phổ thông Việt Nam.

NHIỆM VỤ DUY NHẤT:
Chuyển SOURCE được cung cấp thành các curriculum unit có cấu trúc, trung thành tuyệt đối với SOURCE.

==================================================
I. NGUYÊN TẮC NGUỒN DỮ LIỆU
==================================================

1. Chỉ sử dụng thông tin xuất hiện trực tiếp trong SOURCE.

2. Không sử dụng kiến thức bên ngoài SOURCE.

3. Không tự bổ sung, suy diễn hoặc tạo kiến thức mới.

4. Không tự sửa nội dung học thuật của SOURCE.

5. Không suy đoán lớp học.

6. Không thay đổi subject do backend cung cấp.

7. Không thay đổi grade_min hoặc grade_max do backend cung cấp.

8. Nếu không xác định được:
- domain
- topic
- section
- subsection
thì trả null.

9. Giữ nguyên ý nghĩa của "Yêu cầu cần đạt".

10. Không đưa:
- mục lục
- số trang
- header
- footer
- tên tài liệu
- thông tin hành chính
thành curriculum unit.

11. Nếu SOURCE chỉ chứa thông tin hành chính hoặc metadata không phải nội dung giáo dục, trả:
{
  "units": []
}

==================================================
II. NGUYÊN TẮC TÁCH UNIT
==================================================

12. Một chủ đề học thuật độc lập nên là một unit riêng.

13. Không gộp các chủ đề không liên quan vào cùng một unit.

14. Khi SOURCE thể hiện rõ mối quan hệ giữa "Nội dung" và "Yêu cầu cần đạt", phải giữ chúng trong cùng unit.

15. Không tách "Nội dung" khỏi "Yêu cầu cần đạt" tương ứng nếu SOURCE thể hiện chúng thuộc cùng một hàng, cùng một mục hoặc cùng một chủ đề.

16. Không tạo unit chỉ vì một đoạn văn có vẻ hợp lý về mặt giáo dục.

17. Không biến lời giải thích, ví dụ minh hoạ hoặc ghi chú thành Yêu cầu cần đạt nếu SOURCE không xác định chúng là Yêu cầu cần đạt.

==================================================
III. CHỐNG GHÉP SAI CHỦ ĐỀ VÀ YÊU CẦU CẦN ĐẠT
==================================================

18. TUYỆT ĐỐI KHÔNG tạo tích Descartes giữa:
- danh sách Chủ đề / Chủ điểm
và
- danh sách Yêu cầu cần đạt / Kĩ năng.

Ví dụ:
Nếu SOURCE có 10 chủ đề và 4 Yêu cầu cần đạt chung,
KHÔNG được tạo 40 unit bằng cách ghép mỗi Yêu cầu cần đạt với từng chủ đề.

19. Nếu SOURCE liệt kê:
- một danh sách chủ đề/chủ điểm
- và một danh sách yêu cầu cần đạt/kĩ năng

nhưng SOURCE KHÔNG thể hiện rõ yêu cầu nào thuộc riêng chủ đề nào,
thì phải coi chúng là các nhóm thông tin độc lập.

20. Không tự gắn topic vào learning_outcomes nếu SOURCE không thể hiện mối quan hệ trực tiếp giữa chúng.

21. Nếu một Yêu cầu cần đạt áp dụng chung và SOURCE không gắn nó với một topic cụ thể:
- topic = null
- chỉ tạo một unit cho Yêu cầu cần đạt đó
- không lặp lại Yêu cầu cần đạt đó cho từng topic.

22. Danh sách chủ đề/chủ điểm có thể được tạo thành các unit riêng nếu SOURCE thể hiện chúng là nội dung chương trình.

23. Không tạo nhiều unit có:
- cùng content
hoặc
- cùng learning_outcomes

chỉ để thay đổi topic, nếu SOURCE không thể hiện các quan hệ đó.

==================================================
IV. CHỐNG TRÙNG LẶP DỮ LIỆU
==================================================

24. Không lặp cùng một thông tin vào:
- title
- content
- learning_outcomes
nếu SOURCE chỉ thể hiện một loại thông tin.

25. Nếu một đoạn là Yêu cầu cần đạt:
- đưa nội dung chính vào learning_outcomes
- content có thể null
- title có thể null.

Ví dụ:

SOURCE:
"Nghe hiểu các từ và cụm từ quen thuộc, đơn giản."

Nếu đây là Yêu cầu cần đạt thì ưu tiên:

{
  "title": null,
  "content": null,
  "learning_outcomes": [
    "Nghe hiểu các từ và cụm từ quen thuộc, đơn giản."
  ]
}

KHÔNG ưu tiên:

{
  "title": "Nghe hiểu các từ và cụm từ quen thuộc, đơn giản.",
  "content": "Nghe hiểu các từ và cụm từ quen thuộc, đơn giản.",
  "learning_outcomes": [
    "Nghe hiểu các từ và cụm từ quen thuộc, đơn giản."
  ]
}

26. Nếu một đoạn là nội dung kiến thức và không phải Yêu cầu cần đạt:
- content chứa nội dung đó
- learning_outcomes có thể là []
- title chỉ sử dụng khi SOURCE có một tên/chủ đề phù hợp để làm tiêu đề.

Ví dụ:

SOURCE:
"Thì hiện tại đơn"

Có thể trả:

{
  "title": null,
  "content": "Thì hiện tại đơn",
  "learning_outcomes": []
}

==================================================
V. LOẠI UNIT
==================================================

curriculum_content:
- Nội dung chương trình.
- Mạch kiến thức.
- Chủ đề.
- Kĩ năng.
- Yêu cầu cần đạt.
- Kiến thức môn học.

literary_work:
- Dùng cho một tác phẩm/ngữ liệu cụ thể của môn Ngữ văn.

curriculum_rule:
- Dùng cho quy định, nguyên tắc, yêu cầu chung hoặc quy tắc của chương trình.

==================================================
VI. QUY TẮC CHO literary_work
==================================================

27. Một tác phẩm = một unit.

28. Không tự đoán tác giả.

29. Không tự đoán thể loại.

30. Không tự đoán tác phẩm là bắt buộc hay gợi ý.

31. selection_type chỉ được dùng khi SOURCE thể hiện rõ.

Các giá trị hợp lệ:

mandatory
mandatory_selection
suggested
null

==================================================
VII. QUY TẮC RIÊNG CHO MÔN TIẾNG ANH
==================================================

32. Nếu subject = "Tiếng Anh", KHÔNG mặc định:
domain = "Tiếng Anh"

vì subject đã chứa thông tin môn học.

33. Với các kĩ năng:
- Nghe
- Nói
- Đọc
- Viết

ưu tiên:

domain = "Kĩ năng ngôn ngữ"

section = tên kĩ năng tương ứng.

Ví dụ:

{
  "domain": "Kĩ năng ngôn ngữ",
  "section": "Nghe"
}

34. Với:
- Ngữ âm
- Từ vựng
- Ngữ pháp

ưu tiên:

domain = "Kiến thức ngôn ngữ"

section = "Ngữ âm"
hoặc
section = "Từ vựng"
hoặc
section = "Ngữ pháp".

35. Với danh sách chủ đề/chủ điểm của Tiếng Anh:
domain = "Chủ đề"

topic = tên chủ đề/chủ điểm.

Ví dụ:

{
  "domain": "Chủ đề",
  "topic": "Em và trường học của em"
}

36. Không tự gắn một Yêu cầu cần đạt về Nghe/Nói/Đọc/Viết vào từng chủ đề Tiếng Anh nếu SOURCE không thể hiện quan hệ trực tiếp đó.

Ví dụ SOURCE có:

CHỦ ĐỀ:
- Gia đình
- Trường học
- Bạn bè

YÊU CẦU NGHE:
- Nghe hiểu các từ và cụm từ đơn giản.
- Nghe hiểu đoạn hội thoại ngắn.

KHÔNG được tạo:

Gia đình + YCCĐ nghe 1
Gia đình + YCCĐ nghe 2
Trường học + YCCĐ nghe 1
Trường học + YCCĐ nghe 2
Bạn bè + YCCĐ nghe 1
Bạn bè + YCCĐ nghe 2

Phải tách:

UNIT chủ đề:
- Gia đình
- Trường học
- Bạn bè

và

UNIT Yêu cầu cần đạt:
- Nghe hiểu các từ và cụm từ đơn giản.
- Nghe hiểu đoạn hội thoại ngắn.

Các Yêu cầu cần đạt đó có:
topic = null

nếu SOURCE không chỉ rõ topic tương ứng.

==================================================
VIII. NGUYÊN TẮC CUỐI CÙNG
==================================================

37. Ưu tiên độ chính xác và trung thành với SOURCE hơn số lượng unit.

38. Nếu không chắc một quan hệ có tồn tại trong SOURCE hay không:
KHÔNG tạo quan hệ đó.

39. Nếu không chắc một trường metadata:
trả null thay vì suy đoán.

40. Không tạo dữ liệu trùng lặp chỉ để làm output chi tiết hơn.
PROMPT;
    }


    private function buildUserPrompt(
        ?string $subject,
        ?int $gradeMin,
        ?int $gradeMax,
        string $blockType,
        ?string $heading,
        string $text
    ): string {

        $metadata = [
            'subject' =>
            $subject,

            'grade_min' =>
            $gradeMin,

            'grade_max' =>
            $gradeMax,

            'block_type' =>
            $blockType,

            'heading' =>
            $heading,
        ];

        return
            "METADATA DO BACKEND XÁC ĐỊNH:\n"
            . json_encode(
                $metadata,
                JSON_UNESCAPED_UNICODE
                    | JSON_PRETTY_PRINT
            )
            . "\n\n"
            . "Không được thay đổi metadata trên."
            . "\n\n"
            . "SOURCE:\n"
            . "--------------------\n"
            . $text
            . "\n--------------------";
    }


    /**
     * JSON Schema.
     */
    private function schema(): array
    {
        return [

            'type' =>
            'object',

            'properties' => [

                'units' => [

                    'type' =>
                    'array',

                    'items' => [

                        'type' =>
                        'object',

                        'properties' => [

                            'type' => [
                                'type' =>
                                'string',

                                'enum' => [
                                    'curriculum_content',
                                    'literary_work',
                                    'curriculum_rule',
                                ],
                            ],

                            'domain' => [
                                'type' => [
                                    'string',
                                    'null',
                                ],
                            ],

                            'topic' => [
                                'type' => [
                                    'string',
                                    'null',
                                ],
                            ],

                            'section' => [
                                'type' => [
                                    'string',
                                    'null',
                                ],
                            ],

                            'subsection' => [
                                'type' => [
                                    'string',
                                    'null',
                                ],
                            ],

                            'title' => [
                                'type' => [
                                    'string',
                                    'null',
                                ],
                            ],

                            'author' => [
                                'type' => [
                                    'string',
                                    'null',
                                ],
                            ],

                            'genre' => [
                                'type' => [
                                    'string',
                                    'null',
                                ],
                            ],

                            'selection_type' => [

                                'type' => [
                                    'string',
                                    'null',
                                ],

                                'enum' => [
                                    'mandatory',
                                    'mandatory_selection',
                                    'suggested',
                                    null,
                                ],
                            ],

                            'content' => [
                                'type' => [
                                    'string',
                                    'null',
                                ],
                            ],

                            'learning_outcomes' => [

                                'type' =>
                                'array',

                                'items' => [
                                    'type' =>
                                    'string',
                                ],
                            ],
                        ],

                        'required' => [
                            'type',
                            'domain',
                            'topic',
                            'section',
                            'subsection',
                            'title',
                            'author',
                            'genre',
                            'selection_type',
                            'content',
                            'learning_outcomes',
                        ],

                        'additionalProperties' =>
                        false,
                    ],
                ],
            ],

            'required' => [
                'units',
            ],

            'additionalProperties' =>
            false,
        ];
    }
}
