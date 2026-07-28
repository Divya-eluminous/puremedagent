<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFulltextIndexToPatients extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::table('patients', fn (Blueprint $table) =>
        //     $table->dropFullText(['first_name'])
        //           ->dropFullText(['family_name'])
        // );

        Schema::table('patients', fn (Blueprint $table) =>
            $table->fullText(['first_name', 'family_name'], 'full_name_index')
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('patients', fn (Blueprint $table) =>
            $table->dropFullText('full_name_index')
        );
    }
}
