<?php

class Authcore extends BaseModel {


	public function metas() {
		return $this->belongsToMany('Authmeta', 'authmetacore');
	}


	/**
	 * Update models in database table
	 */
	public function updateModels() {

		print_r($this->get_models());
	}
}