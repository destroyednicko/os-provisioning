<?php

use Models\Phonenumber;
use Models\Mta;

class PhonenumberController extends \BaseController {


	/**
	 * if set to true a create button on index view is available - set to true in BaseController as standard
	 */
    protected $index_create_allowed = false;


    /**
     * defines the formular fields for the edit and create view
     */
	public function get_form_fields($model = null)
	{
		if (!$model)
			$model = new Phonenumber;

		// label has to be the same like column in sql table
		return array(
			array('form_type' => 'select', 'name' => 'country_code', 'description' => 'Country Code', 'value' => Phonenumber::getPossibleEnumValues('country_code')),
			array('form_type' => 'text', 'name' => 'prefix_number', 'description' => 'Prefix Number'),
			array('form_type' => 'text', 'name' => 'number', 'description' => 'Number'),
			array('form_type' => 'select', 'name' => 'mta_id', 'description' => 'MTA', 'value' => $model->mtas_list_with_dummies()),
			array('form_type' => 'text', 'name' => 'port', 'description' => 'Port'),
			array('form_type' => 'text', 'name' => 'username', 'description' => 'Username'),
			array('form_type' => 'text', 'name' => 'password', 'description' => 'Password'),
			array('form_type' => 'select', 'name' => 'active', 'description' => 'Active?', 'value' => array( '1' => 'Yes', '0' => 'No'))
		);
	}

}