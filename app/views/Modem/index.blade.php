@extends ('Layout.split')

@section('content_top')

		{{ HTML::linkRoute('Modem.index', 'Modems') }}

@stop

@section('content_left')

	<h2>Modem List</h2>

	{{ Form::open(array('route' => 'Modem.create', 'method' => 'GET')) }}
	{{ Form::submit('Create') }}
	{{ Form::close() }}
	
	{{ Form::open(array('route' => array('Modem.destroy', 0), 'method' => 'delete')) }}

		@foreach ($modems as $modem)

				<table>
				<tr>
					<td> 
						{{ Form::checkbox('ids['.$modem->id.']') }}
						{{ HTML::linkRoute('Modem.edit', $modem->hostname, $modem->id) }}
					</td>
				</tr>

				</table>
			
		@endforeach

	<br>


	{{ Form::submit('Delete') }}
	{{ Form::close() }}

@stop