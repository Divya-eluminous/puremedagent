<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Session;

class CreateOrdinationTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ordination', function(Blueprint $table)
		{
			$table->increments('id', true);
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
			$table->string('header_text_color')->nullable(); //added on 30-july-24			
			$table->string('latitude')->nullable();
			$table->string('longitude')->nullable();
			$table->enum('country', ['Austria', 'Germany', 'Switzerland'])->nullable();//added on 10-dec-24
			$table->timestamps();
			$table->softDeletes();
		});

		// REMOVED: Data copying from system database to tenant database
		// This was causing duplicate ordination records in tenant databases
		// Each tenant should have their own ordination record created separately
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ordination');
	}

}
