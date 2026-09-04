<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    use HasFactory;

    protected $table = 'customers';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'trade_name',
        'codigo_empresarial',
        'cnpj',
        'city_registration',
        'state_registration',
        'customer_group_id',
        'state_id',
        'software_id',
        'contact_name',
        'contact_email',
        'phone',
        'telephone_2',
        'address',
        'city',
        'bairro',
        'observations',
        'has_ecommerce',
        'has_crm',
        'has_tef',
        'is_active',
        'financial_irregular',
        'whatsapp_phone',
    ];

    protected $casts = [
        'has_ecommerce' => 'boolean',
        'has_crm' => 'boolean',
        'has_tef' => 'boolean',
        'is_active' => 'boolean',
        'financial_irregular' => 'boolean',
    ];

    protected $appends = ['company_summary'];

    /** Relacionamentos */
    public function contacts(): HasMany
    {
        return $this->hasMany(CompanyContact::class, 'customer_id')->orderByDesc('is_main');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(\App\Models\Ticket\Agent::class, 'company');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'company_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(\App\Models\Ticket\Ticket::class, 'company_id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
    }

    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class, 'customer_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }

    public function moduleTypes(): BelongsToMany
    {
        return $this->belongsToMany(CompanyModuleType::class, 'customer_module_type', 'customer_id', 'module_type_id');
    }

    /**
     * Módulos técnicos de implantação configurados para este cliente.
     * Se nenhum configurado, o sistema usa todos como fallback (via ScheduleModuleConfigService).
     */
    public function scheduleModules(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Schedule\Module::class,
            'customer_schedule_module',
            'customer_id',
            'schedule_module_id'
        )->orderBy('project')->orderBy('name');
    }

    /** * Atributos customizados
     * Resolvido S6353: Substituído [^0-9] por \D para maior concisão.
     */
    public function getCompanySummaryAttribute(): string
    {
        $cnpjClean = preg_replace('/\D/', '', $this->cnpj ?? '');

        return "{$this->name} {$this->cnpj} {$cnpjClean}";
    }

    /**
     * Compara o CNPJ ignorando formatação (pontos, barras e traços).
     *
     * Empresas cadastradas via admin geralmente são gravadas com máscara
     * (`92.328.407/4004-18`), enquanto o chatbot WhatsApp recebe apenas
     * os 14 dígitos. Este escopo permite que ambos os formatos coexistam
     * sem alterações destrutivas no dado existente.
     */
    public function scopeWhereCnpjDigits(Builder $query, ?string $digits): Builder
    {
        $digits = preg_replace('/\D+/', '', (string) $digits) ?? '';

        if ($digits === '') {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereRaw(
            "REPLACE(REPLACE(REPLACE(COALESCE(cnpj, ''), '.', ''), '/', ''), '-', '') = ?",
            [$digits]
        );
    }

    /** Métodos auxiliares para flags */
    public function hasEcommerce(): bool
    {
        return $this->has_ecommerce;
    }

    public function hasCrm(): bool
    {
        return $this->has_crm;
    }

    public function hasTef(): bool
    {
        return $this->has_tef;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    /** * Métodos de Toggle simplificados
     */
    public function toggleEcommerce(): void
    {
        $this->update(['has_ecommerce' => ! $this->has_ecommerce]);
    }

    public function toggleCrm(): void
    {
        $this->update(['has_crm' => ! $this->has_crm]);
    }

    public function toggleTef(): void
    {
        $this->update(['has_tef' => ! $this->has_tef]);
    }

    public function toggleActive(): void
    {
        $this->update(['is_active' => ! $this->is_active]);
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }
}
