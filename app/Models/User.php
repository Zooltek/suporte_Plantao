<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Models\Company;
use App\Support\Auth\PasswordResetUrlFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'must_change_password',
        'department_id',
        'company_id',
        'ticketit_agent',
        'ticketit_admin',
        'deployment_admin',
        'can_manage_implementation',
        'active',
        'is_oncall',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at'         => 'datetime',
        'password'                  => 'hashed',
        'must_change_password'      => 'boolean',
        'ticketit_agent'            => 'boolean',
        'ticketit_admin'            => 'boolean',
        'deployment_admin'          => 'boolean',
        'can_manage_implementation' => 'boolean',
        'active'                    => 'boolean',
        'is_oncall'                 => 'boolean',
    ];

    /**
     * Escopo para usuários configurados como plantonistas
     */
    public function scopeOncall($query)
    {
        return $query->where('is_oncall', true);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Avaliações recebidas pelo usuário/agente (tabela user_ratings).
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(UserRating::class);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || (bool) $this->ticketit_admin;
    }

    public function isAgent(): bool
    {
        return $this->hasRole(['admin', 'agent']) || (bool) $this->ticketit_agent;
    }

    public function isDeploymentAdmin(): bool
    {
        return $this->hasRole('deployment-admin') || (bool) $this->deployment_admin;
    }

    public function canManageImplementation(): bool
    {
        return $this->hasPermissionTo('implementation.manage')
            || $this->isAdmin()
            || $this->isDeploymentAdmin()
            || (bool) $this->can_manage_implementation;
    }

    /**
     * Gera o link de redefinição de senha apontando para a área admin.
     * O broker 'admins' usa esta rota em vez da rota padrão 'password.reset'.
     */
    public function sendPasswordResetNotification($token): void
    {
        $url = app(PasswordResetUrlFactory::class)->make($this, $token);

        $this->notify(new ResetPasswordNotification($token, $url));
    }

    /**
     * Anonimiza os dados do usuário em vez de deletá-lo.
     * Preserva o histórico de tickets e registros relacionados.
     */
    public function anonymize(): void
    {
        $this->update([
            'name' => 'Usuário Excluído',
            'email' => "deleted_user_{$this->id}@excluido.local",
            'active' => 0,
            'ticketit_agent' => 0,
            'ticketit_admin' => 0,
        ]);

        $this->delete(); // Soft delete
    }
}
