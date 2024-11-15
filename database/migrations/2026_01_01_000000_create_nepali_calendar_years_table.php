<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nepali_calendar_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('bs_year')->unique();
            $table->json('months');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nepali_calendar_years');
    }
};
