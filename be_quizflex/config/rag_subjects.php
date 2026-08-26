<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default
    |--------------------------------------------------------------------------
    |
    | Dùng khi môn chưa có profile riêng.
    |
    */

    'default' => [

        // Nếu không chia được theo lớp/heading,
        // chia theo số trang.
        'fallback_pages' => 3,

        // Block thô gửi AI parser.
        // Đây KHÔNG phải chunk embedding.
        'max_block_chars' => 12000,

        'headings' => [
            'NỘI DUNG GIÁO DỤC',
            'YÊU CẦU CẦN ĐẠT',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Ngữ văn
    |--------------------------------------------------------------------------
    */

    'Ngữ văn' => [

        'fallback_pages' => 3,

        'max_block_chars' => 12000,

        'headings' => [
            'ĐỌC',
            'ĐỌC HIỂU',
            'VIẾT',
            'NÓI VÀ NGHE',
            'KIẾN THỨC TIẾNG VIỆT',
            'KIẾN THỨC VĂN HỌC',
            'NGỮ LIỆU',
            'TRUYỆN, TIỂU THUYẾT',
            'TRUYỆN, VĂN XUÔI',
            'THƠ, CA DAO, TỤC NGỮ',
            'THƠ, CA DAO, TRUYỆN THƠ NÔM',
            'KỊCH, CHÈO',
            'KỊCH, TUỒNG, CHÈO',
            'KÍ, TẢN VĂN',
            'VĂN NGHỊ LUẬN',
            'VĂN BẢN THÔNG TIN',
        ],

        /*
         * Khi gặp heading này, những nhóm lớp phía sau
         * được hiểu là danh mục tác phẩm/ngữ liệu.
         */
        'special_sections' => [

            'IX. DANH MỤC VĂN BẢN' =>
            'literary_suggestions',

            'DANH MỤC VĂN BẢN' =>
            'literary_suggestions',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Toán
    |--------------------------------------------------------------------------
    */

    'Toán' => [

        'fallback_pages' => 3,

        'max_block_chars' => 12000,

        'headings' => [
            'SỐ VÀ ĐẠI SỐ',
            'HÌNH HỌC VÀ ĐO LƯỜNG',
            'THỐNG KÊ VÀ XÁC SUẤT',
            'HOẠT ĐỘNG THỰC HÀNH VÀ TRẢI NGHIỆM',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Khoa học tự nhiên
    |--------------------------------------------------------------------------
    */

    'Khoa học tự nhiên' => [

        'fallback_pages' => 3,

        'max_block_chars' => 12000,

        'headings' => [
            'CHẤT VÀ SỰ BIẾN ĐỔI CỦA CHẤT',
            'VẬT SỐNG',
            'NĂNG LƯỢNG VÀ SỰ BIẾN ĐỔI',
            'TRÁI ĐẤT VÀ BẦU TRỜI',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Vật lí
    |--------------------------------------------------------------------------
    */

    'Vật lí' => [

        'fallback_pages' => 3,

        'max_block_chars' => 12000,

        'headings' => [
            'VẬT LÍ NHIỆT',
            'CƠ HỌC',
            'ĐIỆN',
            'TỪ',
            'SÓNG',
            'ÁNH SÁNG',
            'VẬT LÍ HẠT NHÂN',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Hóa học
    |--------------------------------------------------------------------------
    */

    'Hóa học' => [

        'fallback_pages' => 3,

        'max_block_chars' => 12000,

        'headings' => [
            'CẤU TẠO NGUYÊN TỬ',
            'BẢNG TUẦN HOÀN',
            'LIÊN KẾT HÓA HỌC',
            'PHẢN ỨNG HÓA HỌC',
            'HÓA HỌC HỮU CƠ',
            'HÓA HỌC VÔ CƠ',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Sinh học
    |--------------------------------------------------------------------------
    */

    'Sinh học' => [

        'fallback_pages' => 3,

        'max_block_chars' => 12000,

        'headings' => [
            'SINH HỌC TẾ BÀO',
            'SINH HỌC CƠ THỂ',
            'DI TRUYỀN HỌC',
            'TIẾN HÓA',
            'SINH THÁI HỌC',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Lịch sử
    |--------------------------------------------------------------------------
    */

    'Lịch sử' => [

        'fallback_pages' => 3,

        'max_block_chars' => 12000,

        'headings' => [
            'LỊCH SỬ VIỆT NAM',
            'LỊCH SỬ THẾ GIỚI',
            'CHUYÊN ĐỀ HỌC TẬP',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Địa lí
    |--------------------------------------------------------------------------
    */

    'Địa lí' => [

        'fallback_pages' => 3,

        'max_block_chars' => 12000,

        'headings' => [
            'ĐỊA LÍ TỰ NHIÊN',
            'ĐỊA LÍ DÂN CƯ',
            'ĐỊA LÍ KINH TẾ',
            'ĐỊA LÍ KINH TẾ - XÃ HỘI',
            'ĐỊA LÍ CÁC NGÀNH KINH TẾ',
            'ĐỊA LÍ CÁC VÙNG',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Lịch sử và Địa lí
    |--------------------------------------------------------------------------
    */

    'Lịch sử và Địa lí' => [

        'fallback_pages' => 3,

        'max_block_chars' => 12000,

        'headings' => [
            'PHÂN MÔN LỊCH SỬ',
            'PHÂN MÔN ĐỊA LÍ',
            'LỊCH SỬ',
            'ĐỊA LÍ',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Tiếng Anh
    |--------------------------------------------------------------------------
    |
    | Quan trọng:
    | Tiếng Anh có thể không chia rõ từng lớp.
    | Khi đó splitter thử heading trước rồi mới page window.
    |
    */

    'Tiếng Anh' => [

        'fallback_pages' => 3,

        'max_block_chars' => 10000,

        'headings' => [
            'NGHE',
            'NÓI',
            'ĐỌC',
            'VIẾT',
            'KIẾN THỨC NGÔN NGỮ',
            'KĨ NĂNG NGÔN NGỮ',
            'KỸ NĂNG NGÔN NGỮ',
            'CHỦ ĐỀ',
            'NĂNG LỰC GIAO TIẾP',
            'NGỮ ÂM',
            'TỪ VỰNG',
            'NGỮ PHÁP',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | GDCD
    |--------------------------------------------------------------------------
    */

    'Giáo dục công dân' => [

        'fallback_pages' => 3,

        'max_block_chars' => 12000,

        'headings' => [
            'GIÁO DỤC ĐẠO ĐỨC',
            'GIÁO DỤC KĨ NĂNG SỐNG',
            'GIÁO DỤC KINH TẾ',
            'GIÁO DỤC PHÁP LUẬT',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Tự nhiên và Xã hội
    |--------------------------------------------------------------------------
    */

    'Tự nhiên và Xã hội' => [

        'fallback_pages' => 3,

        'max_block_chars' => 12000,

        'headings' => [
            'GIA ĐÌNH',
            'TRƯỜNG HỌC',
            'CỘNG ĐỒNG ĐỊA PHƯƠNG',
            'THỰC VẬT VÀ ĐỘNG VẬT',
            'CON NGƯỜI VÀ SỨC KHỎE',
            'TRÁI ĐẤT VÀ BẦU TRỜI',
        ],
    ],
];
