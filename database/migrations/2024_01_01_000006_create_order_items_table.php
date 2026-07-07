<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->string('item_type');
            $table->integer('quantity');
            $table->integer('completed_quantity')->default(0);
            $table->integer('remaining_quantity')->default(0);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('order_id');
            $table->index('service_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};