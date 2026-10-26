<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class GalleryItem extends Model
{
    protected static string $table = 'gallery_items';

    public static function getActive(string $orderBy = 'sort_order ASC, id ASC'): array
    {
        return self::where('`is_active` = 1', [], $orderBy);
    }
}
