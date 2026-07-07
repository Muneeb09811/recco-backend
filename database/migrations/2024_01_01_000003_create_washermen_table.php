<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('washermen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('shop_name')->nullable();
            $table->string('cnic')->nullable();
            $table->text('experience')->nullable();
            $table->string('specialization')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->integer('total_orders_completed')->default(0);
            $table->integer('total_orders_pending')->default(0);
            $table->integer('total_orders_active')->default(0);
            $table->decimal('average_delivery_time', 5, 2)->default(0);
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_available')->default(true);
            $table->decimal('service_charge', 10, 2)->default(0);
            $table->string('service_area')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('user_id');
            $table->index('approval_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('washermen');
    }
};