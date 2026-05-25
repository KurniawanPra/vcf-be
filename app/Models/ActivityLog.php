<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'event',
        'module',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'subject_label',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    /**
     * Relasi ke user (opsional — bisa null jika log sistem)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
