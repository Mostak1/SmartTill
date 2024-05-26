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
        Schema::table('variations', function (Blueprint $table) {
            $table->decimal('foreign_price', 22, 4)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->decimal('currency_rate', 22, 4)->nullable();
            $table->boolean('is_foreign')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('variations', function (Blueprint $table) {
            $table->dropColumn('foreign_price');
            $table->dropColumn('currency_code');
            $table->dropColumn('currency_rate');
            $table->dropColumn('is_foreign');
        });
    }
};
