<?php

declare(strict_types=1);

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsTo;

final class Account extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'accounts';

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

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
