<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFulltextIndexToThreadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */   
    public function up()
    {
        Schema::table('threads', function (Blueprint $table) {
            // $table->fullText('body');
            DB::statement('ALTER TABLE '.DB::getTablePrefix().'threads ADD FULLTEXT fulltext_body(body)');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */   
    public function down()
    {
        Schema::table('threads', function (Blueprint $table) {
            // $table->dropFullText('body');
            DB::statement('ALTER TABLE '.DB::getTablePrefix().'threads DROP INDEX fulltext_body');
        });
    }
}