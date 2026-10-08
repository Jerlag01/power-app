<?php

namespace App\Models;

use App\Support\Permissions;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'is_super', 'permissions'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Whether this role grants a permission such as "posts.update".
     * Super roles are granted everything.
     */
    public function allows(string $permission): bool
    {
        if ($this->is_super) {
            return true;
        }

        [$area, $ability] = array_pad(explode('.', $permission, 2), 2, null);

        return in_array($ability, $this->permissions[$area] ?? [], true);
    }

    /**
     * Whether this role grants any ability within an area, e.g. "settings".
     */
    public function allowsAny(string $area): bool
    {
        return $this->is_super || filled($this->permissions[$area] ?? []);
    }

    public function permissionCount(): int
    {
        return $this->is_super
            ? collect(Permissions::all())->flatten()->count()
            : collect($this->permissions ?? [])->flatten()->count();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_super' => 'boolean',
            'permissions' => 'array',
        ];
    }
}
