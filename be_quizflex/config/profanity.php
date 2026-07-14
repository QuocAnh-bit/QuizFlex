<?php

return [
    // Danh sách các từ ngữ thô tục, nhạy cảm, vi phạm tiêu chuẩn cộng đồng
    'bad_words' => [
        // NHÓM VIẾT TẮT VÀ TEENCODE
        // Bao gồm các từ viết tắt phổ biến của giới trẻ và các biến thể dùng số thay chữ
        'dm', 'đm', 'dcm', 'đcm', 'vcl', 'vl', 'vkl', 'clgt', 'cmm', 'cc', 
        'l0n', 'c4c', 'd1t', 'đ1t', 'fck', 'b1tch',

        // NHÓM BIẾN THỂ KÝ TỰ ĐẶC BIỆT
        // Chặn các trường hợp thêm dấu chấm, dấu gạch giữa các chữ cái
        'l.ồ.n', 'c.ặ.c', 'đ.ị.t', 'd-u', 'l*n',

        // NHÓM TỪ KHÔNG DẤU
        // Để tránh chặn nhầm, các từ này nên được xử lý bằng thuật toán so khớp nguyên từ
        'dit', 'du', 'lon', 'cac', 'buoi', 'cut', 'di', 'pho', 'chich', 'xoac',

        // Các thuật ngữ liên quan đến tệ nạn hoặc hành vi không phù hợp
        'đập đá', 'chơi đá', 'thuốc lắc', 'cần sa', 'ma túy', 'ngáo đá',
        'đánh bạc', 'lô đề', 'cá độ', 'nhà cái', 'kubet', 'shbet',
        'bóng cười', 'cần sa', 'cờ bạc', 'đánh bài', 'đá gà', 'đá bóng',

        // Nhóm viết tắt phổ biến
        'dm', 'đm', 'dcm', 'đcm', 'vcl', 'vl', 'vkl', 'đml', 
        
        // Nhóm từ tục tĩu, chửi thề
        'địt', 'đụ', 'đệch', 'đệt', 'đếch',
        'lồn', 'cặc', 'buồi', 'dái', 
        'cứt', 'đĩ', 'phò', 'cave', 'điếm', 'đĩ điếm', 'đĩ lồn',
        
        // Nhóm từ không dấu (để chặn các trường hợp cố tình lách luật)
        'dit', 'du', 'lon', 'cac', 'buoi', 'cut', 'di',
        
        // Nhóm xúc phạm, lăng mạ
        'chó đẻ', 'thằng chó', 'con chó', 'sủa', 'đồ chó', 'đồ cặn bã', 'đồ súc vật', 'đồ rác rưởi', 'đồ vô dụng',
        'óc chó', 'occho', 'mất dạy', 'hãm lồn', 'hãm tài', 'súc vật',
        
        // Nhóm từ nhạy cảm chính trị, xã hội
        'phản động', 'ba que', '3 que', 'đu càng', 'khát nước', 'vnch', 'việt tân',
    ],
];