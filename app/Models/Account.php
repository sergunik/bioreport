<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Account extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'nickname',
        'date_of_birth',
        'sex',
        'language',
        'timezone',
        'sensitive_words',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function setSensitiveWordsAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['sensitive_words'] = null;

            return;
        }
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $value)));

        $this->attributes['sensitive_words'] = $normalized === '' ? null : $normalized;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
