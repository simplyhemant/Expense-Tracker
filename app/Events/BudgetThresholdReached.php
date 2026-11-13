<?php

namespace App\Events;

use App\Models\Budget;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BudgetThresholdReached
{
    use Dispatchable;
    use SerializesModels;

    public const STATUS_THRESHOLD = 'threshold';
    public const STATUS_EXCEEDED = 'exceeded';

    public function __construct(
        public Budget $budget,
        public string $status
    ) {
    }
}

