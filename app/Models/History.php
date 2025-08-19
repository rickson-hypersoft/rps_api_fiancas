<?php

declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class History extends Model
{
    protected $connection = 'firebird';

    protected $table = 'HISTORICOS';

    protected $primaryKey = 'ID';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ID_USUARIO', 'ID');
    }

    // OPCIONAL: alias para não quebrar quem ainda chama "users"
    public function users(): BelongsTo
    {
        return $this->user();
    }
}
