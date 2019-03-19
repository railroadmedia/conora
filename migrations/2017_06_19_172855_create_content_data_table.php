<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class CreateContentDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(config('railcontent.database_connection_name'))->create(
            config('railcontent.table_prefix'). 'content_data',
            function (Blueprint $table) {
                $table->increments('id');
                $table->integer('content_id')->index();
                $table->string('key', 255)->index();
                $table->text('value');
                $table->integer('position')->index()->nullable();
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('railcontent.table_prefix'). 'content_data');
    }
}
