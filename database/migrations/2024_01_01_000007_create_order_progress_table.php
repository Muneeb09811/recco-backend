<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('updated_by')->constrained('users')->onDelete('cascade');
            $table->enum('stage', [
                'pending', 'accepted', 'picked_up', 'washing',
                'cleaning', 'ironing', 'packing', 'completed', 'delivered'
            ]);
            $table->integer('completed_quantity')->default(0);
            $table->integer('remaining_quantity')->default(0);
            $table->text('notes')->nullable();
            $table->json('images')->nullable();
            $table->timestamps();
            
            $table->index('order_id');
            $table->index('stage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_progress');
    }
};