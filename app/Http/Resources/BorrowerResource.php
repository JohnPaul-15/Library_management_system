<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BorrowerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        //return parent::toArray($request);
        return [
            'id' => $this->id,
            'student_name' => $this->student_name,
            'block' => $this->block,
            'year_level' => $this->year_level,
            'book_name' => $this->book_name,
            'date_borrowed' => $this->date_borrowed,
            'date_return' => $this->date_return,
            'created_at' => $this->created_at,
        ];
    }
}
