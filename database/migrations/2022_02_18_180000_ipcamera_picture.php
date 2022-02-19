<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class IpcameraPicture extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ip_camera_pictures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('camera_id')->index();
            $table->string('filename', 256);
            $table->string('mime', 256);
            $table->unsignedInteger('size');
            $table->timestamps();
        });

        Schema::table('ip_cameras', function (Blueprint $table) {
            $table->dropColumn(['currentFile', 'currentMime', 'currentDate']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ip_camera_picture');

        Schema::table('ip_cameras', function (Blueprint $table) {
            $table->string('currentFile', 256)->nullable();
            $table->string('currentMime', 64)->nullable();
            $table->timestamp('currentDate')->nullable();
        });
    }
}
