<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCountryEnumToMultipleTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::table('patients', function (Blueprint $table) {
        //     $table->enum('country', ['Austria', 'Germany', 'Switzerland'])->nullable()->after('email');
        // });

        // Schema::table('ordination', function (Blueprint $table) {
        //     $table->enum('country', ['Austria', 'Germany', 'Switzerland'])->nullable()->after('email');
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::table('patients', function (Blueprint $table) {
        //     $table->dropColumn('country');
        // });

        // Schema::table('ordination', function (Blueprint $table) {
        //     $table->dropColumn('country');
        // });
    }
}
