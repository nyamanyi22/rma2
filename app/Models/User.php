<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
   

    protected $fillable = [
    'company_name', 'is_not_company', 'website', 'first_name', 'last_name', 'email',
    'phone', 'fax', 'shipping_address1', 'shipping_address2', 'shipping_city',
    'shipping_state', 'shipping_zipcode', 'shipping_country',
    'is_billing_address_different', 'billing_address1', 'billing_address2',
    'billing_city', 'billing_state', 'billing_zipcode', 'billing_country',
    'verification_key', 'password','name',
        'email',
        'password',
];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
