<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFullNameColumnToCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */   
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            // $table->string('full_name')->nullable()->storedAs('CONCAT_WS(" ", first_name, last_name)');
            DB::statement('ALTER TABLE '.DB::getTablePrefix().'customers ADD COLUMN full_name VARCHAR(512) AS (CONCAT_WS(" ", first_name, last_name)) STORED');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */   
    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            // $table->dropColumn('full_name');
            DB::statement('ALTER TABLE '.DB::getTablePrefix().'customers DROP COLUMN full_name');
        });
    }
}