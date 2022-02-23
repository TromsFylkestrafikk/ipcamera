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
            $table->id()->comment('Laravel internal ID');
            $table->string('camera_id', 64)->comment('Custom camera ID');
            $table->string('name', 256)->nullable()->comment('Custom name of camera');
            $table->string('model', 256)->nullable()->comment('Camera maker and model');
            $table->string('ip', 256)->nullable()->comment('IP address of camera in field');
            $table->char('mac', 18)->nullable()->comment("Camera MAC address");
            $table->float('latitude', 12, 8)->nullable()->comment("Camera's latitude in the field");
            $table->float('longitude', 12, 8)->nullable()->comment("Camera's longitude in the field");
            $table->string('currentFile', 256)->nullable()->comment("The image this camera currently broadcasts");
            $table->string('currentMime', 64)->nullable();
            $table->timestamp('currentDate')->nullable();
            $table->boolean('active')->default(false)->comment("Camera is active and receiving images");
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
