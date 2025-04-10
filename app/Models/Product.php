<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    public function rentals() {
        return $this->hasMany(Rental::class);
    }

    protected $fillable = ['name','type', 'description', 'price', 'image'];
}
