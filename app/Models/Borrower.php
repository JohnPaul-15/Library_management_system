<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Borrower extends Model
{
    use HasFactory;

    protected $table = 'borrower';

    protected $fillable = [
        'student_name',
        'block',
        'year_level',
        'email',
        'book_name',
        'date_borrowed',
        'date_return',
    ];
}
