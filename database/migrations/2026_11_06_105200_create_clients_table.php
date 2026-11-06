<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('search_name', 160)->index();
            $table->string('phone_e164', 20)->nullable();
            $table->string('document_type', 10)->nullable(); // 'cpf' | 'cnpj'
            $table->string('document_number', 20)->nullable();
            $table->string('email', 160)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'search_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
