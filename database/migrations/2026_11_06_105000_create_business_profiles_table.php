<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('display_name', 160);
            $table->string('document_type', 10)->nullable(); // 'cpf' | 'cnpj'
            $table->string('document_number', 20)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('street', 160)->nullable();
            $table->string('number', 20)->nullable();
            $table->string('complement', 60)->nullable();
            $table->string('neighborhood', 80)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('state', 2)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_profiles');
    }
};
