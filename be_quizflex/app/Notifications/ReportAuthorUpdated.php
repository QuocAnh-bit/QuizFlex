<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Models\User;

class ReportAuthorUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public $item;
    public $itemType;
    public $author;

    public function __construct($item, string $itemType, User $author)
    {
        $this->item = $item;
        $this->itemType = $itemType;
        $this->author = $author;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function broadcastType(): string
    {
        return 'report_author_updated';
    }

    public function toArray(object $notifiable): array
    {
        $authorName = $this->author->name ?? 'Tác giả';

        if ($this->itemType === 'question') {
            $content = $this->item->content ?? $this->item->text ?? 'Câu hỏi';
            $snippet = mb_substr($content, 0, 45, 'UTF-8');
            if (mb_strlen($content, 'UTF-8') > 45) {
                $snippet .= '...';
            }
            $title = 'Tác giả đã đính chính câu hỏi';
            $message = "Tác giả {$authorName} vừa chỉnh sửa nội dung câu hỏi #{$this->item->id} (\"{$snippet}\") từng bị báo cáo/gỡ công khai. Nhấp để kiểm duyệt!";
        } else {
            $title = 'Tác giả đã đính chính bài Quiz';
            $message = "Tác giả {$authorName} vừa cập nhật nội dung bài Quiz '{$this->item->title}' từng bị báo cáo/gỡ công khai. Nhấp để kiểm duyệt!";
        }

        return [
            'type' => 'report_author_updated',
            'title' => $title,
            'message' => $message,
            'action' => 'view',
            'action_link' => '/admin/report-tickets',
            'metadata' => [
                'item_type' => $this->itemType,
                'item_id' => $this->item->id,
                'author_id' => $this->author->id,
            ],
        ];
    }
}
