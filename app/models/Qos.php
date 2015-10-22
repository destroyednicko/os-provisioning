<?php

namespace Models;
use Log;

class Qos extends \Eloquent {

    // The associated SQL table for this Model
    protected $table = 'qos';

	// Add your validation rules here
	public static $rules = [
		'name' => 'required'
	];

	// Don't forget to fill this array
	protected $fillable = ['ds_rate_max', 'us_rate_max', 'name'];


	public function modem()
	{
		return $this->hasMany("Models\Modem");
	}


    /**
     * BOOT:
     * - init quality observer
     */
    public static function boot()
    {
        parent::boot();

        Qos::observe(new QosObserver);
    }
}

/**
 * Qos Observer Class
 * Handles changes on CMs
 *
 */
class QosObserver 
{
    public function creating($q)
    {
    	$q->ds_rate_max_help = $q->ds_rate_max * 1024 * 1024;
    	$q->us_rate_max_help = $q->us_rate_max * 1024 * 1024;
    }

    public function updating($q)
    {	
    	$q->ds_rate_max_help = $q->ds_rate_max * 1024 * 1024;
    	$q->us_rate_max_help = $q->us_rate_max * 1024 * 1024;
    }
}