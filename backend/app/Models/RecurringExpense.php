<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringExpense extends Model
{
    use HasFactory;

    private const DAY_MAPPING = [
        'monday' => Carbon::MONDAY,
        'tuesday' => Carbon::TUESDAY,
        'wednesday' => Carbon::WEDNESDAY,
        'thursday' => Carbon::THURSDAY,
        'friday' => Carbon::FRIDAY,
        'saturday' => Carbon::SATURDAY,
        'sunday' => Carbon::SUNDAY,
    ];

    protected $fillable = [
        'user_id',
        'template_subcategory_id',
        'label',
        'amount_cents',
        'frequency',
        'day_of_month',
        'day_of_week',
        'month_of_year',
        'auto_create',
        'is_active',
        'start_date',
        'end_date',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'day_of_month' => 'integer',
        'month_of_year' => 'integer',
        'auto_create' => 'boolean',
        'is_active' => 'boolean',
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function templateSubcategory(): BelongsTo
    {
        return $this->belongsTo(TemplateSubcategory::class);
    }

    /**
     * Determine if this recurring expense should create an expense for the given month.
     *
     * @param Carbon $month The budget month (Y-m-01 format)
     */
    public function shouldCreateForMonth(Carbon $month): bool
    {
        // Check if active and auto_create enabled
        if (! $this->is_active || ! $this->auto_create) {
            return false;
        }

        // Check if month is within start/end date range
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        if ($this->start_date > $monthEnd) {
            return false; // Not started yet
        }

        if ($this->end_date && $this->end_date < $monthStart) {
            return false; // Already ended
        }

        // Frequency-specific logic
        return match ($this->frequency) {
            'monthly' => $this->shouldCreateForMonthly($month),
            'weekly' => $this->shouldCreateForWeekly($month),
            'yearly' => $this->shouldCreateForYearly($month),
            default => false,
        };
    }

    /**
     * Get the expense date for creation within a given month
     */
    public function getExpenseDateForMonth(Carbon $month): Carbon
    {
        switch ($this->frequency) {
            case 'monthly':
                $daysInMonth = $month->daysInMonth;
                $targetDay = min($this->day_of_month, $daysInMonth);

                return $month->copy()->day($targetDay);

            case 'weekly':
                // Return first occurrence in the month
                $targetDayOfWeek = self::DAY_MAPPING[$this->day_of_week];
                $current = $month->copy()->startOfMonth();

                while ($current->dayOfWeek !== $targetDayOfWeek) {
                    $current->addDay();
                }

                return $current;

            case 'yearly':
                $targetDay = $this->day_of_month ?? 1;
                $daysInMonth = $month->daysInMonth;
                $targetDay = min($targetDay, $daysInMonth);

                return $month->copy()->day($targetDay);

            default:
                return $month->copy()->startOfMonth();
        }
    }

    /**
     * Monthly frequency: Check if day_of_month falls in this month
     */
    private function shouldCreateForMonthly(Carbon $month): bool
    {
        if (! $this->day_of_month) {
            return false;
        }

        // Handle months with fewer days (e.g., day_of_month=31 in February)
        $daysInMonth = $month->daysInMonth;
        $targetDay = min($this->day_of_month, $daysInMonth);

        // Check if the target date is within start/end range
        $targetDate = $month->copy()->day($targetDay);

        if ($this->start_date > $targetDate) {
            return false;
        }

        if ($this->end_date && $this->end_date < $targetDate) {
            return false;
        }

        return true;
    }

    /**
     * Weekly frequency: Check if any occurrence of day_of_week falls in this month
     */
    private function shouldCreateForWeekly(Carbon $month): bool
    {
        if (! $this->day_of_week) {
            return false;
        }

        $targetDayOfWeek = self::DAY_MAPPING[$this->day_of_week];

        // Find all occurrences of this day in the month
        $current = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        while ($current <= $monthEnd) {
            if ($current->dayOfWeek === $targetDayOfWeek) {
                // Found an occurrence - check if it's within start/end range
                if ($current >= $this->start_date &&
                    (! $this->end_date || $current <= $this->end_date)) {
                    return true; // At least one occurrence should be created
                }
            }
            $current->addDay();
        }

        return false;
    }

    /**
     * Yearly frequency: Check if month_of_year matches this month
     */
    private function shouldCreateForYearly(Carbon $month): bool
    {
        if (! $this->month_of_year) {
            return false;
        }

        // Check if this is the correct month
        if ($month->month !== $this->month_of_year) {
            return false;
        }

        // Use day_of_month if provided, otherwise default to 1st
        $targetDay = $this->day_of_month ?? 1;
        $daysInMonth = $month->daysInMonth;
        $targetDay = min($targetDay, $daysInMonth);

        $targetDate = $month->copy()->day($targetDay);

        if ($this->start_date > $targetDate) {
            return false;
        }

        if ($this->end_date && $this->end_date < $targetDate) {
            return false;
        }

        return true;
    }
}
