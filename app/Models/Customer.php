<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; // Use this if customer will log in
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'company_name',
        'website',
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'fax',
        'shipping_address1',
        'shipping_address2',
        'shipping_city',
        'shipping_state',
        'shipping_zipcode',
        'shipping_country',
        'billing_address1',
        'billing_address2',
        'billing_city',
        'billing_state',
        'billing_zipcode',
        'billing_country',
        'verification_key',
    ];

    protected $hidden = [
        'password',
    ];
    protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}

}

