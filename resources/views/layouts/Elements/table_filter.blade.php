<div role="button" data-toggle="collapse" href="#filter"
     class="row no-gutters col-md-12 mr-bg-muted-blue m-b-15">Фильтр
</div>

<div>
	{{ Form::open(['method' => 'get', 'enctype'=>'multipart/form-data', 'files' => true]) }}
	<div id="filter" class="collapse container col-md-12 padding-horizontal-0">
		@if(is_array($form))
			@include('Form.BaseForm.mr_logic_form',['form'=>$form])
		@else
			{!! $form !!}
		@endif
		<div>
			<button type="submit" class="btn btn-sm btn-primary mr-border-radius-5 m-t-20 m-b-20">Применить</button>
		</div>
	</div>
	{!! Form::close() !!}
</div>