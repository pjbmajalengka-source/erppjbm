<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

/**
 * Service to handle core payroll calculations for ERP-PJBM.
 * Ref: docs/09-LOGIKA-PAYROLL.md
 */
readonly class PayrollCalculatorService
{
    /**
     * Calculate earnings for a specific user on a specific date.
     */
    public function calculateDailyEarning(User $user, Carbon $date): int
    {
        // Logic will fetch assignment wage and attendance multiplier
        return 0; 
    }

    /**
     * Calculate overtime (bongkar/non-bongkar).
     */
    public function calculateOvertime(int $minutes, string $type): int
    {
        $rate = match($type) {
            'bongkar' => config('payroll.overtime.rate_bongkar', 0),
            default   => config('payroll.overtime.rate_non_bongkar', 0),
        };

        return (int) (($minutes / 60) * $rate);
    }
}
