<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained('transactions')->restrictOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->uuid('code')->unique();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('number');
            $table->string('status', 10)->default('issued'); // 'issued' | 'canceled'
            $table->dateTime('issued_at');
            $table->integer('amount_cents');
            $table->string('payment_method', 15)->default('pix');
            $table->text('description');
            $table->string('payer_name', 150);
            $table->string('payer_document', 20)->nullable();
            $table->json('issuer_snapshot');
            $table->dateTime('canceled_at')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'year', 'number']);
            $table->index(['user_id', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
