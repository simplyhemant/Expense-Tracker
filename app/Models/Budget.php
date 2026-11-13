<?php

namespace App\Models;

use App\Models\Transaction;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;
use Morilog\Jalali\CalendarUtils;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'amount',
        'spent',
        'start_date',
        'end_date',
        'period',
        'type',
        'percentage',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'amount' => 'decimal:2',
        'spent' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'percentage' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the first day of the current Jalali month (day 1) converted to Gregorian.
     */
    public static function getCurrentJalaliMonthStart(): Carbon
    {
        $now = Carbon::now();
        $jalaliNow = Jalalian::fromCarbon($now);
        $jy = $jalaliNow->getYear();
        $jm = $jalaliNow->getMonth();
        
        // Convert Jalali day 1 of current month to Gregorian
        [$gy, $gm, $gd] = CalendarUtils::toGregorian($jy, $jm, 1);
        $startDate = Carbon::create($gy, $gm, $gd)->startOfDay();
        
        return $startDate;
    }

    /**
     * Get the last day of the current Jalali month (day 30 or 31) converted to Gregorian.
     */
    public static function getCurrentJalaliMonthEnd(): Carbon
    {
        $now = Carbon::now();
        $jalaliNow = Jalalian::fromCarbon($now);
        $jy = $jalaliNow->getYear();
        $jm = $jalaliNow->getMonth();
        
        // Get the number of days in the current Jalali month
        $daysInMonth = $jalaliNow->getMonthDays();
        
        // Convert Jalali last day of current month to Gregorian
        [$gy, $gm, $gd] = CalendarUtils::toGregorian($jy, $jm, $daysInMonth);
        $endDate = Carbon::create($gy, $gm, $gd)->endOfDay();
        
        return $endDate;
    }

    /**
     * Determine if the provided date falls within the current Jalali month.
     */
    public function includesDate(CarbonInterface $date): bool
    {
        $startDate = self::getCurrentJalaliMonthStart();
        $endDate = self::getCurrentJalaliMonthEnd();
        return $date->betweenIncluded($startDate, $endDate);
    }

    /**
     * Recalculate the spent amount based on expense transactions for the current Jalali month.
     */
    public function refreshSpent(): void
    {
        if ($this->category_id === null) {
            $this->forceFill(['spent' => 0])->save();
            return;
        }

        $startDate = self::getCurrentJalaliMonthStart();
        $endDate = self::getCurrentJalaliMonthEnd();

        $spent = Transaction::query()
            ->where('user_id', $this->user_id)
            ->where('type', 'expense')
            ->where('category_id', $this->category_id)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->sum('amount');
        
        $this->forceFill(['spent' => $spent])->save();

    }

    /**
     * Get number of days in a Jalali month.
     */
    private static function getJalaliMonthDays(int $jy, int $jm): int
    {
        if ($jm <= 6) {
            return 31;
        } elseif ($jm <= 11) {
            return 30;
        } else {
            // Month 12 (Esfand) - check if it's a leap year
            $a = (($jy + 2346) * 682) % 2816;
            return ($a < 682) ? 30 : 29;
        }
    }

    private static function gregorianToJalali(int $gy, int $gm, int $gd): array
    {
        $gDays = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = 355666 + (365 * $gy) + (int)(($gy2 + 3) / 4) - (int)(($gy2 + 99) / 100) + (int)(($gy2 + 399) / 400) + $gd + $gDays[$gm - 1];

        $jy = -1595 + (33 * (int)($days / 12053));
        $days %= 12053;
        $jy += 4 * (int)($days / 1461);
        $days %= 1461;
        if ($days > 365) {
            $jy += (int)(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }

        $jm = ($days < 186) ? 1 + (int)($days / 31) : 7 + (int)(($days - 186) / 30);
        $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));

        return [$jy, $jm, $jd];
    }

    private static function jalaliToGregorian(int $jy, int $jm, int $jd): array
    {
        $jy += 1595;
        $days = -355668 + (365 * $jy) + (int)(($jy / 33) * 8) + (int)(((($jy % 33) + 3) / 4));
        $days += ($jm < 7) ? (($jm - 1) * 31) : (((($jm - 7) * 30) + 186));
        $days += $jd - 1;

        $gy = 400 * (int)($days / 146097);
        $days %= 146097;

        if ($days > 36524) {
            $gy += 100;
            $days -= 36524;
            if ($days >= 365) {
                $days++;
            }
        }

        $gy += 4 * (int)($days / 1461);
        $days %= 1461;

        if ($days > 365) {
            $gy += (int)(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }

        $gd = $days + 1;
        $months = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $leap = ($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0);

        if ($leap) {
            $months[1] = 29;
        }

        $gm = 0;
        while ($gm < 12 && $gd > $months[$gm]) {
            $gd -= $months[$gm];
            $gm++;
        }

        return [$gy, $gm + 1, $gd];
    }
}

