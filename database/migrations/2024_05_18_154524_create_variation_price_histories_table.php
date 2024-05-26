<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('variation_price_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('variation_id');
            $table->decimal('old_price', 22, 4);
            $table->decimal('new_price', 22, 4);
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();

            // Define foreign key constraints
            $table->foreign('variation_id')->references('id')->on('variations')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('variation_price_histories');
    }
};
