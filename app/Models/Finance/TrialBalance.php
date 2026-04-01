<?php

namespace App\Models\Finance;

use App\Models\BaseModel;
use App\Traits\Auditable;

class TrialBalance extends BaseModel
{
    use Auditable;

    protected $table = 'general_ledgers';
}
