<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BorrowerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->user->name,
            'book' => $this->book->title,
            'date_borrowed' => $this->date_borrowed,
            'due_date' => $this->due_date,
            'date_return' => $this->date_return,
            'is_returned' => $this->isReturned(),
        ];
    }
}
