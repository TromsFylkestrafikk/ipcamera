<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIpCameraTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ip_cameras', function (Blueprint $table) {
            $table->id();
            $table->string('camera_id', 64);
            $table->string('name', 256)->nullable();
            $table->string('model', 256)->nullable();
            $table->string('ip', 256)->nullable();
            $table->char('mac', 18)->nullable()->comment("Camera MAC address");
            $table->float('latitude', 12, 8)->nullable();
            $table->float('longitude', 12, 8)->nullable();
            $table->string('currentFile', 256)->nullable()->comment("The image this camera currently broadcasts");
            $table->string('currentMime', 64)->nullable();
            $table->timestamp('currentDate')->nullable();
            $table->boolean('active')->default(true);
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
        Schema::dropIfExists('ip_cameras');
    }
}
