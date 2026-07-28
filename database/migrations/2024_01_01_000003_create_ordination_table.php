<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ordination', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('text_color_code');
            $table->string('background_color')->nullable();
            $table->string('logo')->nullable();
            $table->string('logo_path')->nullable();
            $table->smallInteger('status');
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('mobile_no')->nullable();
            $table->string('button_colors')->nullable();
            $table->string('screen_bg_color')->nullable();
            $table->string('app_bar_color')->nullable();
            $table->string('tabs_selection_color')->nullable();
            $table->string('home_screen_options_color')->nullable();
            $table->string('menu_header_colors')->nullable();
            $table->string('menu_bg_color')->nullable();
            $table->string('dark_text_color')->nullable();
            $table->string('light_text_color')->nullable();
            $table->string('header_text_color')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->enum('country', ['Austria', 'Germany', 'Switzerland'])->nullable();
            $table->string('url')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordination');
    }
}; 