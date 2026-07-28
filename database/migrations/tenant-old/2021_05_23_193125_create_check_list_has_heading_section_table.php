<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCheckListHasHeadingSectionTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('check_list_has_heading_section', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('fk_check_list_id');
			$table->string('heading_section')->nullable();
			$table->timestamp('status')->default(DB::raw('CURRENT_TIMESTAMP'));
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
		Schema::drop('check_list_has_heading_section');
	}

}
