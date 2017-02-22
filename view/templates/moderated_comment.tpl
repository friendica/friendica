
		<div class="comment-wwedit-wrapper" id="comment-edit-wrapper-{{$id}}" style="display: block;">
			<form class="comment-edit-form" id="comment-edit-form-{{$id}}" action="item" method="post" onsubmit="post_comment({{$id}}); return false;">
				<input type="hidden" name="type" value="{{$type}}" />
				<input type="hidden" name="profile_uid" value="{{$profile_uid}}" />
				<input type="hidden" name="parent" value="{{$parent}}" />
				<input type="hidden" name="return" value="{{$return_path|escape:'html'}}" />
				<input type="hidden" name="jsreload" value="{{$jsreload}}" />
				<input type="hidden" name="preview" id="comment-preview-inp-{{$id}}" value="0" />

				<div class="comment-edit-photo" id="comment-edit-photo-{{$id}}">
					<a class="comment-edit-photo-link" href="{{$mylink}}" title="{{$mytitle|escape:'html'}}"><img class="my-comment-photo" src="{{$myphoto}}" alt="{{$mytitle|escape:'html'}}" title="{{$mytitle|escape:'html'}}" /></a>
				</div>
				<div class="comment-edit-photo-end"></div>
				<div id="mod-cmnt-wrap-{{$id}}" class="mod-cmnt-wrap" style="display:none">
					<div id="mod-cmnt-name-lbl-{{$id}}" class="mod-cmnt-name-lbl">{{$lbl_modname}}</div>
					<input type="text" id="mod-cmnt-name-{{$id}}" class="mod-cmnt-name" name="mod-cmnt-name" value="{{$modname|escape:'html'}}" />
					<div id="mod-cmnt-email-lbl-{{$id}}" class="mod-cmnt-email-lbl">{{$lbl_modemail}}</div>
					<input type="text" id="mod-cmnt-email-{{$id}}" class="mod-cmnt-email" name="mod-cmnt-email" value="{{$modemail|escape:'html'}}" />
					<div id="mod-cmnt-url-lbl-{{$id}}" class="mod-cmnt-url-lbl">{{$lbl_modurl}}</div>
					<input type="text" id="mod-cmnt-url-{{$id}}" class="mod-cmnt-url" name="mod-cmnt-url" value="{{$modurl|escape:'html'}}" />
				</div>
				<textarea id="comment-edit-text-{{$id}}" class="comment-edit-text-empty" name="body" placeholder="{{$comment}}" onFocus="commentOpen(this,{{$id}});" onBlur="commentClose(this,{{$id}});"></textarea>

				<div class="comment-edit-text-end"></div>
				<div class="comment-edit-submit-wrapper" id="comment-edit-submit-wrapper-{{$id}}" style="display: none;">
					<input type="submit" onclick="post_comment({{$id}}); return false;" id="comment-edit-submit-{{$id}}" class="comment-edit-submit" name="submit" value="{{$submit|escape:'html'}}" />
					<span onclick="preview_comment({{$id}});" id="comment-edit-preview-link-{{$id}}" class="fakelink">{{$preview}}</span>
					<div id="comment-edit-preview-{{$id}}" class="comment-edit-preview" style="display:none;"></div>
				</div>

				<div class="comment-edit-end"></div>
			</form>

		</div>
