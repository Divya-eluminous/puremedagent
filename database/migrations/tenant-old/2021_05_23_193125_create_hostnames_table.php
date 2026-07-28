<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateHostnamesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('hostnames', function(Blueprint $table)
		{
			$table->bigInteger('id', true)->unsigned();
			$table->string('fqdn', 191);
			$table->integer('ordination_id');
			$table->string('redirect_to', 191)->nullable();
			$table->boolean('force_https')->default(0);
			$table->dateTime('under_maintenance_since')->nullable();
			$table->bigInteger('website_id')->unsigned()->nullable();
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
		Schema::drop('hostnames');
	}

}
