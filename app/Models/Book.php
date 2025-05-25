<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = ['title', 'author', 'publisher', 'copies', 'total_copies', 'available_copies'];

    public function borrowers()
    {
        return $this->hasMany(Borrower::class);
    }

    public function getStatusAttribute()
    {
        return $this->available_copies > 0 ? 'available' : 'borrowed';
    }

    public function isAvailable()
    {
        return $this->available_copies > 0;
    }

    public function canBorrowCopies($numCopies)
    {
        return $this->available_copies >= $numCopies;
    }

    public function getCurrentBorrower()
    {
        return $this->borrowers()
            ->whereNull('date_return')
            ->with('user')
            ->first();
    }

    public function getBorrowedByUser($userId)
    {
        return $this->borrowers()
            ->where('user_id', $userId)
            ->whereNull('date_return')
            ->first();
    }

    public function getBorrowedCopiesByUser($userId)
    {
        return $this->borrowers()
            ->where('user_id', $userId)
            ->whereNull('date_return')
            ->count();
    }
}
