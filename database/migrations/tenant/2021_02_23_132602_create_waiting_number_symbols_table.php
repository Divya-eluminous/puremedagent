<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateWaitingNumberSymbolsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('waiting_number_symbols', function(Blueprint $table)
		{
			// $table->integer('id')->primary();
			$table->increments('id',true); // added on 11-april-23
			$table->string('name', 100);
			// $table->string('url', 100);
			$table->string('url', 255);  //changed on 19-sept-24 
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
		Schema::drop('waiting_number_symbols');
	}

}
