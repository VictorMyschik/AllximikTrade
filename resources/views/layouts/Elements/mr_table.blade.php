<div class="padding-horizontal-0 m-t-15">
	@if($form)
		@include('layouts.Elements.table_filter',['form' => $form])
	@endif

	<mr-table :mr_object="{{json_encode($mr_object)}}" :mr_route="'{{$route_url}}'"></mr-table>
</div>

