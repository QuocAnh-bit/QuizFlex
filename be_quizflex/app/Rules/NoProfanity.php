<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class NoProfanity implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (empty($value)) {
            return true;
        }

        $badWords = config('profanity.bad_words', []);
        
        // Chuyển toàn bộ chuỗi về chữ thường để so sánh (có hỗ trợ tiếng Việt)
        $valueLower = mb_strtolower($value, 'UTF-8');

        foreach ($badWords as $word) {
            $wordLower = mb_strtolower($word, 'UTF-8');
            // Nếu phát hiện từ cấm nằm trong chuỗi -> Bắt lỗi ngay
            if (str_contains($valueLower, $wordLower)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Trường :attribute chứa từ ngữ không phù hợp hoặc vi phạm tiêu chuẩn cộng đồng.';
    }
}