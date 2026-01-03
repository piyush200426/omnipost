<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class QrCode extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'qr_codes';

    protected $fillable = [
        'user_id',
        'title',         // QR name/description
        'qr_type',       // text, url, wifi, vcard, email, phone, sms, whatsapp
        'qr_mode',       // static, dynamic
        'qr_data',       // Actual content for QR
        'short_url',     // For dynamic QR only
        'original_url',  // For dynamic QR original link
        'qr_image_url',  // Generated QR image
        'scans',         // Total scans
        'visits',        // Total redirect visits (dynamic only)
        'settings',      // {colors, logo, margin, error_correction}
        'is_active',
        'expiry_date',
    ];

    protected $casts = [
        'settings' => 'array',
        'scans' => 'integer',
        'visits' => 'integer',
        'is_active' => 'boolean',
        'expiry_date' => 'datetime'
    ];
}