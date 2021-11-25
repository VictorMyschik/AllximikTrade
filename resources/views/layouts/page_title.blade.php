@if(isset($page_title) || isset($back_link))
	<div class="row no-gutters margin-t-15 mr-bold" style="text-align:left;">
		<div class="col" style="font-size: 1.4rem;">{!! $page_title ?? '' !!}</div>
		@if(isset($back_link))
			<div style="font-size: 1.4rem;" class="col text-lg-right p-l-5 p-r-5 mr-border-radius-5 m-t-5 m-r-5">
        {!! $back_link !!}
      </div>
		@endif
	</div>
	<hr class="m-t-5">
@endif
{!! MrMessage::GetMessage() !!}