<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $casts = [
        'price' => 'double'
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'pivot'
    ];

    public function categories(){
        return $this->belongsToMany('App\Models\Category');
    }
}
