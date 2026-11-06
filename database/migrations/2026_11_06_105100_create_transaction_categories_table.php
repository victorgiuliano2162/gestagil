<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10); // 'income' | 'expense'
            $table->string('name', 80);
            $table->string('name_key', 80);
            $table->string('color', 20)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'type', 'name_key']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_categories');
    }
};
