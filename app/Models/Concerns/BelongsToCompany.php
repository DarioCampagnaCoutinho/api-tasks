<?php

namespace App\Models\Concerns;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Isola os dados por empresa (multi-tenant — single database, shared schema).
 *
 * Aplica automaticamente um global scope filtrando pela empresa do usuário
 * autenticado e preenche `company_id` ao criar registros. Assim, nenhuma query
 * precisa lembrar de adicionar `where('company_id', ...)` manualmente.
 */
trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            // hasUser() não dispara a resolução do guard — evita recursão
            // durante a autenticação via Sanctum (resolução do token).
            if (Auth::hasUser() && Auth::user()->company_id) {
                $builder->where(
                    $builder->getModel()->getTable() . '.company_id',
                    Auth::user()->company_id
                );
            }
        });

        static::creating(function ($model) {
            if (! $model->company_id && Auth::hasUser() && Auth::user()->company_id) {
                $model->company_id = Auth::user()->company_id;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
