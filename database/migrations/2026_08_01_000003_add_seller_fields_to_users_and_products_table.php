<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('store_name')->nullable()->after('role');
            $table->timestamp('seller_approved_at')->nullable()->after('store_name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('seller_id')->nullable()->after('category_id');
            $table->foreign('seller_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['seller_id']);
            $table->dropColumn('seller_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['store_name', 'seller_approved_at']);
        });
    }
};
