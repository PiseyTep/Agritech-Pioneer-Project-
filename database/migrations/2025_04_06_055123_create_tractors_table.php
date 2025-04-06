<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTractorsTable extends Migration
{
    public function up()
    {
        // Drop existing products table if it exists
        Schema::dropIfExists('products');

        // Create new tractors table
        Schema::create('tractors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price_per_acre', 10, 2);
            $table->string('type');
            $table->integer('stock');
            $table->string('image_url')->nullable();
            $table->string('brand')->nullable();
            $table->integer('horse_power')->default(0);
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tractors');
    }
}