<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One page view on the public website (see App\Support\Stats\VisitorTracker; no IP address is stored).
 */
#[Fillable(['visitor', 'path', 'locale', 'source', 'source_name', 'referrer_host', 'country_code', 'device'])]
class PageView extends Model
{
    public const UPDATED_AT = null;
}
