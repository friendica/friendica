
{{* custom content from hook will replace everything. *}}
<div class="row">
{{if $content != '' }}
	<div class="col-sm-12">
		<div class="panel panel-default login-panel-content">
			<div class="panel-body">
				{{$content}}
			</div>
		</div>
	</div>
{{else}}

	<div class="col-md-7 col-sm-12 login-content">
		{{if $customhome != false }}
			{{include file="$customhome"}}
		{{else}}
			{{$defaultheader}}
		{{/if}}
	</div>
	<div class="col-md-5 col-sm-12 login-form">
	{{$login}}
	</div>
{{/if}}
</div>