<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = [
        'category_id',
        'title',
        'author',
        'publisher',
        'publication_year',
        'isbn',
        'stock',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function borrowings()
    {
        return $this->belongsToMany(Borrowing::class, 'borrowing_details');
    }
}
