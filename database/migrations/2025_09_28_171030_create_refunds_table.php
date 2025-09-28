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
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('refund_number')->unique();
            $table->decimal('refund_amount', 10, 2);
            $table->decimal('original_amount', 10, 2);
            $table->enum('type', ['full', 'partial'])->default('full');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->json('refund_data')->nullable(); // Payment gateway response
            $table->string('transaction_id')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('processed_by')->nullable(); // Admin user who processed
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['order_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index('refund_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
