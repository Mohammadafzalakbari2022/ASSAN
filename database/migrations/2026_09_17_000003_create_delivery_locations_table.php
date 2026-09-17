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
        Schema::create('delivery_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_user_id')->index();
            $table->unsignedBigInteger('assignment_id')->nullable()->index();
            $table->double('latitude');
            $table->double('longitude');
            $table->double('accuracy')->nullable();
            $table->double('speed')->nullable();
            $table->timestamp('recorded_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('delivery_locations');
    }
};