
		{{if $threaded}}
		<div class="comment-wwedit-wrapper threaded" id="comment-edit-wrapper-{{$id}}" style="display: block;">
		{{else}}
		<div class="comment-wwedit-wrapper" id="comment-edit-wrapper-{{$id}}" style="display: block;">
		{{/if}}
			<form class="comment-edit-form" style="display: block;" id="comment-edit-form-{{$id}}" action="item" method="post" onsubmit="post_comment({{$id}}); return false;">
				<input type="hidden" name="type" value="{{$type}}" />
				<input type="hidden" name="profile_uid" value="{{$profile_uid}}" />
				<input type="hidden" name="parent" value="{{$parent}}" />
				{{*<!--<input type="hidden" name="return" value="{{$return_path}}" />-->*}}
				<input type="hidden" name="jsreload" value="{{$jsreload}}" />
				<input type="hidden" name="preview" id="comment-preview-inp-{{$id}}" value="0" />
				<input type="hidden" name="post_id_random" value="{{$rand_num}}" />

				<div class="comment-edit-photo" id="comment-edit-photo-{{$id}}">
					<a class="comment-edit-photo-link" href="{{$mylink}}" title="{{$mytitle}}"><img class="my-comment-photo" src="{{$myphoto}}" alt="{{$mytitle}}" title="{{$mytitle}}" /></a>
				</div>
				<div class="comment-edit-photo-end"></div>
				<textarea id="comment-edit-text-{{$id}}" class="comment-edit-text-empty" name="body" placeholder="{{$comment}}" onFocus="commentOpen(this,{{$id}});"></textarea>
				{{if $qcomment}}
					<select id="qcomment-select-{{$id}}" name="qcomment-{{$id}}" class="qcomment" onchange="qCommentInsert(this,{{$id}});">
					<option value=""></option>
				{{foreach $qcomment as $qc}}
					<option value="{{$qc}}">{{$qc}}</option>
				{{/foreach}}
					</select>
				{{/if}}

				<div class="comment-edit-text-end"></div>
				<div class="comment-edit-submit-wrapper" id="comment-edit-submit-wrapper-{{$id}}" style="display: none;">

				<div class="comment-edit-bb">
	                                <a title="{{$edimg}}" data-role="insert-formatting" data-bbcode="img" data-id="{{$id}}"><i class="icon-picture"></i></a>
	                                <a title="{{$edurl}}" data-role="insert-formatting" data-bbcode="url" data-id="{{$id}}"><i class="icon-link"></i></a>
	                                <a title="{{$edvideo}}" data-role="insert-formatting" data-bbcode="video" data-id="{{$id}}"><i class="icon-film"></i></a>

	                                <a title="{{$eduline}}" data-role="insert-formatting" data-bbcode="u" data-id="{{$id}}"><i class="icon-underline"></i></a>
	                                <a title="{{$editalic}}" data-role="insert-formatting" data-bbcode="i" data-id="{{$id}}"><i class="icon-italic"></i></a>
	                                <a title="{{$edbold}}" data-role="insert-formatting" data-bbcode="b" data-id="{{$id}}"><i class="icon-bold"></i></a>
	                                <a title="{{$edquote}}" data-role="insert-formatting" data-bbcode="quote" data-id="{{$id}}"><i class="icon-quote-left"></i></a>

                                </div>
					<input type="submit" onclick="post_comment({{$id}}); return false;" id="comment-edit-submit-{{$id}}" class="comment-edit-submit" name="submit" value="{{$submit}}" />
					{{if $preview}}<input type="submit" onclick="preview_comment({{$id}}); return false;" id="comment-edit-preview-link-{{$id}}" class="comment-edit-submit" value="{{$preview}}" />{{/if}}
					<!-- {{if $preview}}<span onclick="preview_comment({{$id}});" id="comment-edit-preview-link-{{$id}}" class="fakelink">{{$preview}}</span>{{/if}} -->
					<div id="comment-edit-preview-{{$id}}" class="comment-edit-preview" style="display:none;"></div>
				</div>

				<div class="comment-edit-end"></div>
			</form>

		</div>
