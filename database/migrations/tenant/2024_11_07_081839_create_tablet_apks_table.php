<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTabletApksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::create('tablet_apks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('app_name', 255);
            $table->text('apk_file_name');
            $table->string('apk_file_path', 255);
            $table->string('apk_version', 20);
            $table->enum('is_new', ['0', '1'])->default('1')->comment('0=>not new, 1=> new');
            $table->enum('is_downloaded', ['0', '1'])->default('0')->comment('0=>not downloaded, 1=>downloaded');
            $table->timestamp('uploaded_at')->useCurrent()->nullable();
            $table->softDeletes();
            $table->timestamps(0); // creates `created_at` and `updated_at` with CURRENT_TIMESTAMP as default
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tablet_apks');
    }
}
