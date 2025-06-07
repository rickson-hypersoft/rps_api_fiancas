<?php

declare(strict_types = 1);

namespace App\Models\Propostal;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

class PropostalPayments extends Authenticatable
{
    protected $connection = 'firebird';

    protected $table = 'PROPOSTAS_PAGAMENTOS';

    protected $primaryKey = 'ID';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
