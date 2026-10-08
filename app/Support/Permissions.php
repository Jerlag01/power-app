<?php

namespace App\Support;

/**
 * Every permission a role can be granted, grouped per area of the website.
 * A role stores them as {"posts": ["view", "create"], "settings": ["mail"], ...}.
 */
class Permissions
{
    public const AREAS = [
        'posts' => ['view', 'create', 'update', 'delete'],
        'projects' => ['view', 'create', 'update', 'delete'],
        'expertises' => ['view', 'create', 'update', 'delete'],
        'clients' => ['view', 'create', 'update', 'delete'],
        'certificates' => ['view', 'create', 'update', 'delete'],
        'footer_links' => ['view', 'create', 'update', 'delete'],
        'employees' => ['view', 'create', 'update', 'delete'],
        'contact_messages' => ['view', 'update', 'delete'],
        'users' => ['view', 'create', 'update', 'delete'],
        'roles' => ['view', 'create', 'update', 'delete'],
        'settings' => ['general', 'contact', 'mail', 'appearance'],
    ];

    /**
     * Every permission, e.g. ['posts' => ['view', 'create', ...], ...].
     *
     * @return array<string, list<string>>
     */
    public static function all(): array
    {
        return self::AREAS;
    }

    /**
     * Keep only known areas and abilities, so a crafted request cannot store
     * permissions that do not exist.
     *
     * @param  array<string, mixed>  $permissions
     * @return array<string, list<string>>
     */
    public static function sanitize(array $permissions): array
    {
        $clean = [];

        foreach (self::AREAS as $area => $abilities) {
            $granted = array_values(array_intersect($abilities, (array) ($permissions[$area] ?? [])));

            if ($granted !== []) {
                $clean[$area] = $granted;
            }
        }

        return $clean;
    }
}
