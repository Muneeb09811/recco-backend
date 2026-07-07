<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('washerman_id')->constrained('users')->onDelete('cascade');
            $table->integer('rating')->check('rating >= 1 AND rating <= 5');
            $table->text('comment')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
            
            $table->index('order_id');
            $table->index('customer_id');
            $table->index('washerman_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};