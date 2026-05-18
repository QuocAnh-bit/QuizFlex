# Cài đặt OCR với Tesseract cho PHP

## Bước 1: Cài đặt Tesseract OCR

Tải và cài đặt Tesseract OCR tại:

* [Tesseract OCR UB Mannheim](https://github.com/UB-Mannheim/tesseract/wiki)

Sau khi cài đặt xong, mặc định sẽ nằm tại:

```text
C:\Program Files\Tesseract-OCR
```

---

## Bước 2: Cài package cho PHP

Mở terminal trong thư mục back-end và chạy:

```bash
composer require thiagoalessio/tesseract_ocr
```

---

## Bước 3: Tải dữ liệu tiếng Việt

Tải file `vie.traineddata` tại:

* [Vietnamese traineddata](https://github.com/tesseract-ocr/tessdata/blob/main/vie.traineddata)

---

## Bước 4: Thêm file ngôn ngữ vào Tesseract

Copy file:

```text
vie.traineddata
```

vào thư mục:

```text
C:\Program Files\Tesseract-OCR\tessdata
```

---

## Bước 5: Test OCR trong PHP

Ví dụ:

```php
<?php

require 'vendor/autoload.php';

use thiagoalessio\TesseractOCR\TesseractOCR;

$text = (new TesseractOCR('image.png'))
    ->lang('vie')
    ->run();

echo $text;
```

---

## Lưu ý

Nếu PHP không nhận được Tesseract, có thể cần khai báo đường dẫn:

```php
(new TesseractOCR('image.png'))
    ->executable('C:\Program Files\Tesseract-OCR\tesseract.exe')
    ->lang('vie')
    ->run();
```
