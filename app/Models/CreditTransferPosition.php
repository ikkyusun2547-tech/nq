<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditTransferPosition extends Model
{
    protected $fillable = [
        'key',
        'label',
        'hours',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'hours' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * key => Thai label, in display order — the admin-configurable
     * replacement for the old CreditTransferRequest::POSITION_LABELS
     * constant, reused everywhere a position needs to be shown to a human.
     *
     * @return array<string,string>
     */
    public static function labelsMap(): array
    {
        return static::orderBy('sort_order')->pluck('label', 'key')->all();
    }

    /**
     * key => standard hours, in display order — the admin-configurable
     * replacement for the old CreditTransferRequest::POSITION_HOURS
     * constant.
     *
     * @return array<string,int>
     */
    public static function hoursMap(): array
    {
        return static::orderBy('sort_order')->pluck('hours', 'key')->all();
    }
}
