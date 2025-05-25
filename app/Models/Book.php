<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = ['title', 'author', 'publisher', 'copies','total_copies', 'available_copies'];

    public function borrowers()
    {
        return $this->hasMany(Borrower::class);
    }
}
