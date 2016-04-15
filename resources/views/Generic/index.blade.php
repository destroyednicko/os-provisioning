@extends ('Layout.split84')

@section('content_top')

	{{ HTML::linkRoute($route_name.'.index', $view_header) }}

@stop

@section('content_left')

	<!-- Search Field -->
	@DivOpen(12)
		@DivOpen(6)
			{{ Form::model(null, array('route'=>$route_name.'.fulltextSearch', 'method'=>'GET')) }}
				@include('Generic.searchform')
			{{ Form::close() }}
		@DivClose()
	@DivClose()

	@DivOpen(12)
		<br>
	@DivClose()

	<!-- Create Form -->
	@DivOpen(12)
		@DivOpen(3)
			@if ($create_allowed)
				{{ Form::open(array('route' => $route_name.'.create', 'method' => 'GET')) }}
				{{ Form::submit('Create', ['style' => 'simple']) }}
				{{ Form::close() }}
			@endif
		@DivClose()
	@DivClose()

	<!-- database entries inside a form with checkboxes to be able to delete one or more entries -->
	@DivOpen(12)

		{{ Form::open(array('route' => array($route_name.'.destroy', 0), 'method' => 'delete')) }}

			@if (isset($query) && isset($scope))
				<h4>Matches for <tt>{{ $query }}</tt> in <tt>{{ $scope }}</tt></h4>
			@endif

			<table class="table">

				<!-- TODO: add concept to parse header fields for index table - like firstname, lastname, ..-->
				<thead>
					<tr>
					<th></th>
					<th></th>
					</tr>
				</thead>

				<?php $color_array = ['success', 'warning', 'danger', 'info']; ?>

				@foreach ($view_var as $object)
					<?php
						// Bootstrap Class -> Color Line
						// TODO: move to controller
						if (isset($object->get_view_link_title()['bsclass']))
							$class = $object->get_view_link_title()['bsclass'];
						else
						{
							if (!isset($i))
								$i = 0;

							if ($i++ % 5 == 0)
							{
								$color_array = array_merge( array(array_pop($color_array)), $color_array);
								$class = $color_array[0];
							}
						}
					?>

					<tr class={{$class}}>
						<td>
							{{ Form::checkbox('ids['.$object->id.']', 1, null, null, ['style' => 'simple']) }}
						</td>

						@foreach (is_array($object->get_view_link_title()) ? $object->get_view_link_title()['index'] : [$object->get_view_link_title()] as $field)
							<td>
								{{ HTML::linkRoute($route_name.'.edit', $field, $object->id) }}
							</td>
						@endforeach
					</tr>
				@endforeach
			</table>

			<br>

		<!-- delete/submit button of form-->
		@DivOpen(3)
			{{ Form::submit('Delete', ['style' => 'simple']) }}
			{{ Form::close() }}
		@DivClose()

	@DivClose()

@stop
