<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Borrower extends Model
{
    protected $table = 'borrowers';
    
    protected $fillable = [
        'user_id', 
        'book_id',
        'date_borrowed',
        'due_date',
        'date_return'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function isReturned()
    {
        return $this->date_return !== null;
    }
}
