<link rel="stylesheet" href="view/theme/frio/css/mod_admin.css?v={{constant('\Friendica\App::VERSION')}}" type="text/css" media="screen"/>

<div id="adminpage" class="adminpage generic-page-wrapper">
	<h1>{{$title}} - {{$page}}</h1>

	<div class="well well-lg">
        	{{$label_current}}: <b>{{$storagebackend}}</b>
			{{if $storagebackend_ro_txt}}
			<br><i>{{$storagebackend_ro_txt nofilter}}</i>
			{{/if}}
	</div>

		<h2>{{$label_config}}</h2>

		{{foreach from=$availablestorageforms item=$storage}}
	<form action="{{$baseurl}}/admin/storage/{{$storage.prefix}}" method="post">
		<input type='hidden' name='form_security_token' value="{{$form_security_token}}">
		<div class="panel">
			<div class="section-subtitle-wrapper panel-title" role="tab" id="admin-settings-{{$storage.prefix}}">
				<h3>
					<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#admin-settings" href="#admin-settings-{{$storage.prefix}}-collapse" aria-expanded="false" aria-controls="admin-settings-{{$storage.prefix}}-collapse">
						{{$storage.name}}
					</button>
				</h3>
			</div>
			<div id="admin-settings-{{$storage.prefix}}-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="admin-settings-{{$storage.prefix}}">
				<div class="panel-body">
					{{if $storage.form}}
						{{foreach from=$storage.form item=$field}}
							{{include file=$field.field field=$field}}
						{{/foreach}}
					{{else}}
						 {{$noconfig}}
					{{/if}}
				</div>
				<div class="panel-footer">
					{{if $storage.form}}
					<input type="submit" name="submit_save" class="btn btn-primary" value="{{$save}}"/>
	                    {{if $is_writable}}
							{{if $storage.active}}
					<input type="submit" name="submit_save_set" class="btn btn-primary" value="{{$save_reload}}"/>
							{{else}}
					<input type="submit" name="submit_save_set" class="btn btn-primary" value="{{$save_use}}"/>
							{{/if}}
						{{/if}}
					{{elseif $is_writable}}
					<input type="submit" name="submit_save_set" class="btn btn-primary" {{if $storage.active}}disabled="disabled"{{/if}} value="{{$use}}"/>
					{{/if}}
				</div>
			</div>
		</div>
	</form>

		{{/foreach}}

	</form>
</div>
