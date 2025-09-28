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
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('order_id')->constrained()->onDelete('cascade')->after('id');
            $table->foreignId('product_id')->constrained()->onDelete('cascade')->after('order_id');
            $table->integer('quantity')->after('product_id');
            $table->decimal('unit_price', 10, 2)->after('quantity');
            $table->decimal('total_price', 10, 2)->after('unit_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['product_id']);
            $table->dropColumn(['order_id', 'product_id', 'quantity', 'unit_price', 'total_price']);
        });
    }
};
