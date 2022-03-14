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
            $table->unsignedBigInteger('camera_id')->comment("Reference to camera model");
            $table->string('filename', 256)->comment("File name of picture");
            $table->string('mime', 256)->comment("File's mime type");
            $table->unsignedInteger('size')->comment("File's final file size");
            $table->boolean('published')->default(false)->comment("File is processed and ready for publishing");
            $table->timestamps();
            $table->unique(['camera_id', 'filename']);
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
        Schema::dropIfExists('ip_camera_pictures');

        Schema::table('ip_cameras', function (Blueprint $table) {
            $table->string('currentFile', 256)->nullable();
            $table->string('currentMime', 64)->nullable();
            $table->timestamp('currentDate')->nullable();
        });
    }
}
