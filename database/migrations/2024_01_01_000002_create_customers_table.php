<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('company_name')->nullable();
            $table->integer('total_orders')->default(0);
            $table->integer('active_orders')->default(0);
            $table->integer('completed_orders')->default(0);
            $table->decimal('total_spent', 10, 2)->default(0);
            $table->string('loyalty_points')->default(0);
            $table->boolean('is_vip')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};