
{{if $threaded}}
<div class="comment-wwedit-wrapper threaded" id="comment-edit-wrapper-{{$id}}" style="display: block;">
{{else}}
<div class="comment-wwedit-wrapper" id="comment-edit-wrapper-{{$id}}" style="display: block;">
{{/if}}
	<form class="comment-edit-form" style="display: block;" data-item-id="{{$id}}" id="comment-edit-form-{{$id}}" action="item" method="post" onsubmit="post_comment({{$id}}); return false;">
		<input type="hidden" name="type" value="{{$type}}" />
		<input type="hidden" name="profile_uid" value="{{$profile_uid}}" />
		<input type="hidden" name="parent" value="{{$parent}}" />
		{{*<!--<input type="hidden" name="return" value="{{$return_path}}" />-->*}}
		<input type="hidden" name="jsreload" value="{{$jsreload}}" />
		<input type="hidden" name="preview" id="comment-preview-inp-{{$id}}" value="0" />
		<input type="hidden" name="post_id_random" value="{{$rand_num}}" />

		<div class="bb form-group">
			<textarea id="comment-edit-text-{{$id}}" class="comment-edit-text-empty form-control text-autosize" name="body" placeholder="{{$comment}}" onFocus="commentOpenUI(this,{{$id}});"></textarea>
		</div>
		{{if $qcomment}}
			<select id="qcomment-select-{{$id}}" name="qcomment-{{$id}}" class="qcomment" onchange="qCommentInsert(this,{{$id}});">
			<option value=""></option>
		{{foreach $qcomment as $qc}}
			<option value="{{$qc}}">{{$qc}}</option>
		{{/foreach}}
			</select>
		{{/if}}

		<div class="comment-edit-text-end clear"></div>
		<div class="comment-edit-submit-wrapper" id="comment-edit-submit-wrapper-{{$id}}" style="display: none;">
			<button type="submit" class="btn btn-primary btn-sm" id="comment-edit-submit-{{$id}}" name="submit"><i class="fa fa-envelope"></i> {{$submit}}</button>
			{{if $preview}}
				<button type="button" class="btn btn-defaul btn-sm" onclick="preview_comment({{$id}});" id="comment-edit-preview-link-{{$id}}"><i class="fa fa-eye"></i> {{$preview}}</button>
			{{/if}}
			<ul class="comment-edit-bb-{{$id}} comment-icon-list nav nav-pills pull-right">
				<li>
					<button type="button" class="btn-link icon" style="cursor: pointer;" title="{{$edimg}}" data-role="insert-formatting" data-bbcode="img" data-id="{{$id}}">
						<i class="fa fa-picture-o"></i>
					</button>
				</li>
				<li>
					<button type="button" class="btn-link icon bb-url" style="cursor: pointer;" title="{{$edurl}}" onclick="insertFormatting('url',{{$id}});">
						<i class="fa fa-link"></i>
					</button>
				</li>
				<li>
					<button type="button" class="btn-link icon bb-video" style="cursor: pointer;" title="{{$edvideo}}" onclick="insertFormatting('video',{{$id}});">
						<i class="fa fa-video-camera"></i>
					</button>
				</li>

				<li>
					<button type="button" class="btn-link icon underline" style="cursor: pointer;" title="{{$eduline}}" onclick="insertFormatting('u',{{$id}});">
						<i class="fa fa-underline"></i>
					</button>
				</li>
				<li>
					<button type="button" class="btn-link icon italic" style="cursor: pointer;" title="{{$editalic}}" onclick="insertFormatting('i',{{$id}});">
						<i class="fa fa-italic"></i>
					</button>
				</li>
				<li>
					<button type="button" class="btn-link icon bold" style="cursor: pointer;"  title="{{$edbold}}" onclick="insertFormatting('b',{{$id}});">
						<i class="fa fa-bold"></i>
					</button>
				</li>
				<li>
					<button type="button" class="btn-link icon quote" style="cursor: pointer;" title="{{$edquote}}" onclick="insertFormatting('quote',{{$id}});">
						<i class="fa fa-quote-left"></i>
					</button>
				</li>
			</ul>
		</div>

		<div class="comment-edit-end clear"></div>
	</form>
	<div id="comment-edit-preview-{{$id}}" class="comment-edit-preview" style="display:none;"></div>
</div>
