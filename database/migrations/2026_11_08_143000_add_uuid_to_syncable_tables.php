<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique();
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique();
        });

        foreach (['clients', 'appointments', 'transactions'] as $table) {
            $rows = DB::table($table)->whereNull('uuid')->pluck('id');
            foreach ($rows as $id) {
                DB::table($table)->where('id', $id)->update(['uuid' => (string) Str::uuid()]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
