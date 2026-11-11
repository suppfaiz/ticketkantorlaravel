<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class TicketCategory extends Model
{
    use HasFactory;

    public const DEFAULT_CATEGORIES = [
        'Hardware',
        'Software',
        'Jaringan',
        'Akses Akun',
        'Perubahan Sistem',
    ];

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'category', 'name');
    }

    /**
     * Return active category names for ticket forms, falling back to defaults if table is not migrated yet.
     *
     * @return array<int, string>
     */
    public static function activeNames(?string $includeCategory = null): array
    {
        if (! Schema::hasTable('ticket_categories')) {
            $names = self::DEFAULT_CATEGORIES;
        } else {
            $names = self::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name')
                ->all();

            if (empty($names)) {
                $names = self::DEFAULT_CATEGORIES;
            }
        }

        if ($includeCategory && ! in_array($includeCategory, $names, true)) {
            $names[] = $includeCategory;
        }

        return $names;
    }
}
