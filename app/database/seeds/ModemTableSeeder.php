<?php

// Composer: "fzaninotto/faker": "v1.3.0"
use Faker\Factory as Faker;
use Models\Modem;

class ModemTableSeeder extends Seeder {

	public function run()
	{
		$faker = Faker::create();

		foreach(range(1, 10) as $index)
		{
			Modem::create([
				'mac' => $faker->macAddress(),
				'description' => $faker->realText(200),
				'network_access' => $faker->boolean(),
				'public' => (rand(0,100) < 5 ? 1 : 0),
				'serial_num' => $faker->sentence(),
				'inventar_num' => $faker->sentence(),
				'contract_id' => rand(1,500),
				'configfile_id' => rand(1,10),
				'qos_id' => rand(1,10)
			]);
		}
	}

}