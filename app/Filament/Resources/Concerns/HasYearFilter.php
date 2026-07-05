<?php

namespace App\Filament\Resources\Concerns;

use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

/**
 * ตัวช่วยเพิ่ม dropdown "ปี" ให้ตาราง Filament โดยกรองตามวันที่จริงของเอกสาร
 * (business date เช่น request_date/order_date) แทน created_at ที่เป็นวันที่ import.
 */
trait HasYearFilter
{
    /**
     * รายการปีสำหรับ dropdown (ปีปัจจุบันย้อนหลัง 6 ปี).
     *
     * @return array<int, string>
     */
    public static function yearFilterOptions(): array
    {
        $currentYear = (int) date('Y');

        return collect(range($currentYear, $currentYear - 6))
            ->mapWithKeys(fn ($y) => [$y => (string) $y])
            ->all();
    }

    /**
     * สร้าง SelectFilter ปีที่กรองด้วย COALESCE(businessDateColumn, created_at).
     */
    public static function yearFilter(string $businessDateColumn, string $name = 'year'): SelectFilter
    {
        return SelectFilter::make($name)
            ->label('ปี (Year)')
            ->options(static::yearFilterOptions())
            ->query(fn (Builder $query, array $data): Builder => $query->when(
                $data['value'],
                fn (Builder $q, $year) => $q->whereRaw("YEAR(COALESCE({$businessDateColumn}, created_at)) = ?", [$year])
            ));
    }
}
