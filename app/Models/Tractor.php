<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tractor extends Model
{
    use HasFactory;

    // Specify the table name (optional, but good practice)
    protected $table = 'tractors';

    // Fillable fields
    protected $fillable = [
        'name',
        'description',
        'price_per_acre',
        'type',
        'stock',
        'image_url',
        'brand',
        'horse_power',
        'is_available'
    ];

    // Cast certain fields to appropriate types
    protected $casts = [
        'price_per_acre' => 'float',
        'stock' => 'integer',
        'horse_power' => 'integer',
        'is_available' => 'boolean'
    ];

    // Relationships
    public function rentals()
    {
        return $this->hasMany(Rental::class);
    }

    // Scope for available tractors
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)->where('stock', '>', 0);
    }

    // Mutator for image URL
    public function getImageUrlAttribute($value)
    {
        // Ensure full URL is returned
        return $value ? url('storage/' . $value) : null;
    }
}