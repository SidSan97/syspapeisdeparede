<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use BeyondCode\Comments\Traits\HasComments;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderBudget extends Model
{
    use HasComments, HasFactory, HasTenantScope;

    protected $fillable = [
        'order_id',
        'tenant_id',
        'budget_wall_id',
        'description',
        'status',
        'layout_column_names_id',
        'production_column_names_id',
        'production_date',
        'production_percentage',
        'tinyErp_order_id',
        'tinyErp_order_expedition_id',
        'order_index',
        'ready_to_expedition',
        'activity_running_since',
        'activity_elapsed_seconds',
        'completed_at',
    ];

    protected $appends = [
        'activity_total_seconds',
        'activity_is_running',
        'is_completed',
    ];

    protected function casts(): array
    {
        return [
            'description' => 'string',
            'status' => 'string',
            'layout_column_names_id' => 'integer',
            'budget_wall_id' => 'integer',
            'production_column_names_id' => 'integer',
            'production_date' => 'date',
            'production_percentage' => 'decimal:1',
            'tinyErp_order_id' => 'string',
            'tinyErp_order_expedition_id' => 'integer',
            'order_index' => 'integer',
            'ready_to_expedition' => 'integer:0,1',
            'activity_running_since' => 'datetime',
            'activity_elapsed_seconds' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Indica se o card já foi marcado como concluído na página de Layout.
     */
    protected function isCompleted(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->completed_at !== null,
        );
    }

    /**
     * Total acumulado em segundos do timer (sessões fechadas + sessão em execução).
     */
    protected function activityTotalSeconds(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                $base = (int) ($this->activity_elapsed_seconds ?? 0);

                if ($this->activity_running_since) {
                    $base += max(0, now()->diffInSeconds($this->activity_running_since, true));
                }

                return $base;
            }
        );
    }

    /**
     * Indica se o timer está em execução no momento.
     */
    protected function activityIsRunning(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->activity_running_since !== null,
        );
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function wall(): BelongsTo
    {
        return $this->belongsTo(BudgetWall::class, 'budget_wall_id');
    }

    public function layoutColumnName(): BelongsTo
    {
        return $this->belongsTo(LayoutColumnName::class, 'layout_column_names_id');
    }

    public function productionColumnName(): BelongsTo
    {
        return $this->belongsTo(ProductionColumnName::class, 'production_column_names_id');
    }

    /**
     * Get the users that belong to this order budget card.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'layout_card_user', 'card_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Get the history entries for this order budget card.
     */
    public function history()
    {
        return $this->hasMany(LayoutCardHistory::class, 'card_id');
    }

    /**
     * Sessões fechadas do temporizador (cada pausa ou conclusão com timer ativo).
     */
    public function activitySessions(): HasMany
    {
        return $this->hasMany(OrderBudgetActivitySession::class)->orderBy('ended_at');
    }

    /**
     * Get the production reports for this order budget.
     */
    public function productionReports()
    {
        return $this->hasMany(ProductionReport::class);
    }
}
