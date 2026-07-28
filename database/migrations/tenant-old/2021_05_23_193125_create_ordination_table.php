<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

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
			$table->integer('id', true);
			$table->string('name');
			$table->string('text_color_code');
			$table->string('background_color')->nullable();
			$table->integer('login_user');
			$table->string('logo')->nullable();
			$table->string('logo_path')->nullable();
			$table->smallInteger('status');
			$table->text('address', 65535)->nullable();
			$table->integer('postal_code')->nullable();
			$table->timestamps();
			$table->softDeletes();
		});
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
