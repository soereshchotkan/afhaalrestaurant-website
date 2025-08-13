<?php


// 5. create_cart_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'session_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cart');
    }
};