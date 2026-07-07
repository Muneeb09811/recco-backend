<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('washerman_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            
            // Pickup Details
            $table->text('pickup_address');
            $table->string('pickup_phone');
            $table->date('pickup_date');
            $table->time('pickup_time')->nullable();
            
            // Delivery Details
            $table->date('expected_delivery_date');
            $table->date('actual_delivery_date')->nullable();
            
            // Order Items Summary
            $table->integer('shirts_quantity')->default(0);
            $table->integer('tshirts_quantity')->default(0);
            $table->integer('pants_quantity')->default(0);
            $table->integer('jeans_quantity')->default(0);
            $table->integer('coats_quantity')->default(0);
            $table->integer('bedsheets_quantity')->default(0);
            $table->integer('blankets_quantity')->default(0);
            $table->integer('curtains_quantity')->default(0);
            $table->integer('other_items_quantity')->default(0);
            $table->integer('total_quantity')->default(0);
            
            // Progress Tracking
            $table->integer('completed_quantity')->default(0);
            $table->integer('remaining_quantity')->default(0);
            $table->integer('delivered_quantity')->default(0);
            
            // Status
            $table->enum('status', [
                'pending', 'accepted', 'picked_up', 'washing', 
                'cleaning', 'ironing', 'packing', 'completed', 
                'delivered', 'cancelled', 'rejected'
            ])->default('pending');
            
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'online'])->default('cash');
            
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('final_amount', 10, 2)->default(0);
            
            $table->text('special_instructions')->nullable();
            $table->text('order_notes')->nullable();
            $table->json('images')->nullable();
            
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('order_number');
            $table->index('customer_id');
            $table->index('washerman_id');
            $table->index('status');
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};