<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePhoneTariffTable extends BaseMigration {

	// name of the table to create
	protected $tablename = "phonetariff";


	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create($this->tablename, function(Blueprint $table)
		{
			$this->up_table_generic($table);

			$table->string('external_identifier');		// at Envia this is a integer or a string…
			$table->string('name');						// name to show in forms
			$table->enum('type', ['purchase', 'sale']);
			$table->string('description');
			$table->boolean('usable')->default(1);		// there are more Envia variations as we really use (e.g. MGCP stuff) – can be used for temporary deactivation of tariffs or to prevent a tariff from being assingned again

		});

		$this->set_fim_fields([
			'external_identifier',
			'name',
			'description',
		]);

		return parent::up();
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop($this->tablename);
	}
}
