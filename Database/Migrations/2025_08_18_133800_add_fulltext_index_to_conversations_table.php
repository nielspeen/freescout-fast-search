<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddFulltextIndexToConversationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */   
    public function up()
    {

        Schema::table('conversations', function (Blueprint $table) {
            // This syntax requires Laravel 8 or higher. Using raw SQL for now.
            // $table->fullText(['subject', 'customer_email', 'number']);
            
            DB::statement('ALTER TABLE ' . DB::getTablePrefix() . 'conversations ADD FULLTEXT fulltext_subject_customer_email (subject, customer_email)');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */   
    public function down()
    {
        Schema::table('conversations', function (Blueprint $table) {
            // $table->dropFullText(['subject', 'customer_email', 'number']);
            DB::statement('ALTER TABLE ' . DB::getTablePrefix() . 'conversations DROP INDEX fulltext_subject_customer_email');
        });
    }
}