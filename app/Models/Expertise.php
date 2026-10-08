<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\ExpertiseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'title', 'icon', 'description', 'services', 'sort_order'])]
class Expertise extends Model
{
    /** @use HasFactory<ExpertiseFactory> */
    use HasFactory, HasTranslations;

    public const ICONS = ['bolt', 'panel', 'light', 'network', 'solar', 'shield'];

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'title' => 'json:unicode',
            'description' => 'json:unicode',
            'services' => 'json:unicode',
        ];
    }
}
