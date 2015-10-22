<?php

use Models\Configfile;

class ConfigfileController extends \BaseController {

	/**
	 * Display a listing of configfiles
	 *
	 * @return Response
	 */
	public function index()
	{
		$configfiles = Configfile::all();

		return View::make('Configfile.index', compact('configfiles'));
	}

	/**
	 * Show the form for creating a new configfile
	 *
	 * @return Response
	 */
	public function create()
	{
		$parents = array('0' => 'Null');
		foreach (Configfile::all() as $cf)
		{
			$parents[$cf->id] = $cf->name;	
		}
		
		return View::make('Configfile.create', compact('parents'));
	}

	/**
	 * Store a newly created configfile in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		$validator = Validator::make($data = Input::all(), Configfile::rules());

		if ($validator->fails())
		{
			return Redirect::back()->withErrors($validator)->withInput();
		}

		$id = Configfile::create($data)->id;

		return View::make('Configfile.edit', $id);
	}


	/**
	 * Show the form for editing the specified configfile.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		$configfile = Configfile::find($id);

		$parents = array('0' => 'Null');
		foreach (Configfile::all() as $cf)
		{
			if ($cf->id != $id)
				$parents[$cf->id] = $cf->name;	
		}

		return View::make('Configfile.edit', compact('configfile', 'parents'));
	}

	/**
	 * Update the specified configfile in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		$configfile = Configfile::findOrFail($id);

		$validator = Validator::make($data = Input::all(), Configfile::rules($id));

		if ($validator->fails())
		{
			return Redirect::back()->withErrors($validator)->withInput();
		}

		$configfile->update($data);

		return View::make('Configfile.edit', $id)->with(DB::getQueryLog());
	}

	/**
	 * Remove the specified configfile from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		if ($id == 0)
		{
			// bulk delete
			foreach (Input::all()['ids'] as $id => $val)
				Configfile::destroy($id);
		}
		else
			Configfile::destroy($id);

		return $this->index();
	}

}