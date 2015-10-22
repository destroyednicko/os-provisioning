<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMtaTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('mta', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('modem_id')->unsigned();
			$table->string('mac', 17);
			$table->string('hostname');
			$table->integer('configfile_id')->unsigned();
			$table->enum('type', ['sip','packetcable']);
			$table->timestamps();
		});

		DB::update("ALTER TABLE mta AUTO_INCREMENT = 100000;");
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('mta');
	}

}