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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('customer_id')->constrained()->onDelete('cascade')->after('id');
            $table->string('order_number')->unique()->after('customer_id');
            $table->decimal('total_amount', 10, 2)->after('order_number');
            $table->enum('status', ['pending', 'processing', 'paid', 'shipped', 'delivered', 'cancelled'])->default('pending')->after('total_amount');
            $table->enum('payment_status', ['pending', 'processing', 'paid', 'failed', 'refunded'])->default('pending')->after('status');
            $table->json('payment_data')->nullable()->after('payment_status');
            $table->timestamp('paid_at')->nullable()->after('payment_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn(['customer_id', 'order_number', 'total_amount', 'status', 'payment_status', 'payment_data', 'paid_at']);
        });
    }
};
