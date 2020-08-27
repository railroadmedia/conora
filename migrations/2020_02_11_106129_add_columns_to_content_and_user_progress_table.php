<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddColumnsToContentAndUserProgressTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        //TODO: Should be migrated only for tests
        if(config('railcontent.database_connection_name') == 'testbench') {
                    Schema::connection(config('railcontent.database_connection_name'))
                        ->table(
                            config('railcontent.table_prefix') . 'content',
                            function (Blueprint $table) {
                                $table->string('total_xp')
                                    ->index()
                                    ->after('youtube_video_id')
                                    ->nullable();
                            }
                        );

                    Schema::connection(config('railcontent.database_connection_name'))
                        ->table(
                            config('railcontent.table_prefix') . 'user_content_progress',
                            function (Blueprint $table) {
                                $table->string('higher_key_progress')
                                    ->index()
                                    ->after('progress_percent')
                                    ->nullable();
                            }
                        );
        }
    }

    /**
     * Reverse the migrations.
     *
     *
     * @return void
     */
    public function down()
    {
        if(config('railcontent.database_connection_name') == 'testbench') {
            Schema::connection(config('usora.database_connection_name'))
                ->table(
                    config('railcontent.table_prefix') . 'content',
                    function ($table) {
                        /**
                         * @var $table \Illuminate\Database\Schema\Blueprint
                         */
                        $table->dropColumn('total_xp');
                    }
                );

                    Schema::connection(config('usora.database_connection_name'))
                        ->table(
                            config('railcontent.table_prefix') . 'user_content_progress',
                            function ($table) {
                                /**
                                 * @var $table \Illuminate\Database\Schema\Blueprint
                                 */
                                $table->dropColumn('higher_key_progress');
                            }
                        );
        }
    }
}