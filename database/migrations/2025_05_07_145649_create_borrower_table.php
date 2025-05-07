<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('borrower', function (Blueprint $table) {
            $table->id();
            $table->string('student_name');
            $table->string('block');
            $table->string('year_level');
            $table->string('book_name');
            $table->date('date_borrowed');
            $table->date('date_return');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrower');
    }
};
