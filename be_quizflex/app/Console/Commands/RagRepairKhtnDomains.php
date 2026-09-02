<?php

namespace App\Console\Commands;

use App\Models\CurriculumUnit;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * KHTN 6-9 đã parse chi tiết từ PDF, nhưng domain ban đầu là một giá trị chung.
 * Lệnh này chỉ phân loại lại domain; không xóa unit, chunk, embedding hay môn khác.
 */
class RagRepairKhtnDomains extends Command
{
    protected $signature = 'rag:repair-khtn-domains {--apply : Thực sự cập nhật domain}';

    protected $description = 'Phân loại unit KHTN lớp 6-9 thành Vật lí, Hóa học, Sinh học hoặc Liên môn';

    public function handle(): int
    {
        $units = CurriculumUnit::query()
            ->where('subject', 'Khoa học tự nhiên')
            ->where('grade_min', '>=', 6)
            ->where('grade_max', '<=', 9)
            ->get(['id', 'grade_min', 'grade_max', 'domain', 'topic', 'section', 'subsection', 'title']);

        $groups = $units->groupBy(fn (CurriculumUnit $unit) => $this->domainFor($unit));
        $this->table(['Domain mới', 'Số unit'], collect(['Vật lí', 'Hóa học', 'Sinh học', 'Liên môn'])
            ->map(fn (string $domain) => [$domain, $groups->get($domain, collect())->count()])
            ->all());

        if (!$this->option('apply')) {
            $this->warn('Chế độ xem trước. Không có dữ liệu nào bị thay đổi.');
            return self::SUCCESS;
        }

        foreach ($groups as $domain => $domainUnits) {
            CurriculumUnit::query()->whereIn('id', $domainUnits->pluck('id'))->update(['domain' => $domain]);
        }

        $this->info("Đã cập nhật domain cho {$units->count()} unit KHTN lớp 6-9.");
        $this->line('Không cần embedding lại: truy xuất đang lọc theo curriculum_unit_ids.');

        return self::SUCCESS;
    }

    private function domainFor(CurriculumUnit $unit): string
    {
        $text = Str::lower(Str::ascii(implode(' ', array_filter([$unit->topic, $unit->section, $unit->subsection, $unit->title]))));

        // Ưu tiên Hóa/Sinh trước các từ chung như "năng lượng" hoặc "chất".
        if ($this->contains($text, ['acid', 'base', 'bazo', 'oxi', 'oxygen', 'nguyen tu', 'nguyen to', 'phan tu', 'hoa tri', 'lien ket hoa hoc', 'bang tuan hoan', 'phan ung hoa hoc', 'mol', 'oxide', 'oxit', 'muoi', 'dung dich', 'kim loai', 'phi kim', 'hydrocarbon', 'cacbon', 'polymer', 'hoa hoc'])) return 'Hóa học';
        if ($this->contains($text, ['te bao', 'sinh vat', 'sinh hoc', 'dong vat', 'thuc vat', 'co the nguoi', 'sinh san', 'sinh truong', 'cam ung', 'trao doi chat', 'quan the', 'quan xa', 'he sinh thai', 'sinh quyen', 'di truyen', 'nhiem sac the', 'tien hoa', 'protein', 'lipid', 'carbohydrate'])) return 'Sinh học';
        if ($this->contains($text, ['luc', 'ap suat', 'nhiet', 'dong dien', 'dien tro', 'dinh luat ohm', 'nam cham', 'tu truong', 'cam ung dien tu', 'anh sang', 'guong', 'khuc xa', 'thau kinh', 'lang kinh', 'am thanh', 'song am', 'toc do', 'quang duong', 'co nang', 'dong nang', 'the nang', 'do luong', 'khoi luong rieng', 'nang luong'])) return 'Vật lí';

        return 'Liên môn';
    }

    private function contains(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (Str::contains($text, $keyword)) return true;
        }

        return false;
    }
}
