
		<div class="comment-wwedit-wrapper" id="comment-edit-wrapper-{{$id}}" style="display: block;">
			<form class="comment-edit-form" id="comment-edit-form-{{$id}}" action="item" method="post" onsubmit="post_comment({{$id}}); return false;">
				<input type="hidden" name="type" value="{{$type}}" />
				<input type="hidden" name="profile_uid" value="{{$profile_uid}}" />
				<input type="hidden" name="parent" value="{{$parent}}" />
				<input type="hidden" name="return" value="{{$return_path}}" />
				<input type="hidden" name="jsreload" value="{{$jsreload}}" />
				<input type="hidden" name="preview" id="comment-preview-inp-{{$id}}" value="0" />

				<div class="comment-edit-photo" id="comment-edit-photo-{{$id}}">
					<a class="comment-edit-photo-link" href="{{$mylink}}" title="{{$mytitle}}"><img class="my-comment-photo" src="{{$myphoto}}" alt="{{$mytitle}}" title="{{$mytitle}}" /></a>
				</div>
				<div class="comment-edit-photo-end"></div>
				<div id="mod-cmnt-wrap-{{$id}}" class="mod-cmnt-wrap" style="display:none">
					<div id="mod-cmnt-name-lbl-{{$id}}" class="mod-cmnt-name-lbl">{{$lbl_modname}}</div>
					<input type="text" id="mod-cmnt-name-{{$id}}" class="mod-cmnt-name" name="mod-cmnt-name" value="{{$modname}}" />
					<div id="mod-cmnt-email-lbl-{{$id}}" class="mod-cmnt-email-lbl">{{$lbl_modemail}}</div>
					<input type="text" id="mod-cmnt-email-{{$id}}" class="mod-cmnt-email" name="mod-cmnt-email" value="{{$modemail}}" />
					<div id="mod-cmnt-url-lbl-{{$id}}" class="mod-cmnt-url-lbl">{{$lbl_modurl}}</div>
					<input type="text" id="mod-cmnt-url-{{$id}}" class="mod-cmnt-url" name="mod-cmnt-url" value="{{$modurl}}" />
				</div>
				<ul class="comment-edit-bb-{{$id}}">
					<li><a class="editicon boldbb shadow"
						style="cursor: pointer;" title="{{$edbold}}"
						onclick="insertFormatting('b', {{$id}});"></a></li>
					<li><a class="editicon italicbb shadow"
						style="cursor: pointer;" title="{{$editalic}}"
						onclick="insertFormatting('i', {{$id}});"></a></li>
					<li><a class="editicon underlinebb shadow"
						style="cursor: pointer;" title="{{$eduline}}"
						onclick="insertFormatting('u', {{$id}});"></a></li>
					<li><a class="editicon quotebb shadow"
						style="cursor: pointer;" title="{{$edquote}}"
						onclick="insertFormatting('quote', {{$id}});"></a></li>
					<li><a class="editicon codebb shadow"
						style="cursor: pointer;" title="{{$edcode}}"
						onclick="insertFormatting('code', {{$id}});"></a></li>
					<li><a class="editicon imagebb shadow"
						style="cursor: pointer;" title="{{$edimg}}"
						onclick="insertFormatting('img', {{$id}});"></a></li>
					<li><a class="editicon urlbb shadow"
						style="cursor: pointer;" title="{{$edurl}}"
						onclick="insertFormatting('url', {{$id}});"></a></li>
					<li><a class="editicon videobb shadow"
						style="cursor: pointer;" title="{{$edvideo}}"
						onclick="insertFormatting('video', {{$id}});"></a></li>
				</ul>
				<div class="comment-edit-bb-end"></div>
				<textarea id="comment-edit-text-{{$id}}" class="comment-edit-text-empty" name="body" placeholder="{{$comment}}" onFocus="commentOpen(this,{{$id}});cmtBbOpen({{$id}});" onBlur="commentClose(this,{{$id}});"></textarea>

				<div class="comment-edit-text-end"></div>
				<div class="comment-edit-submit-wrapper" id="comment-edit-submit-wrapper-{{$id}}" style="display: none;">
					<input type="submit" onclick="post_comment({{$id}}); return false;" id="comment-edit-submit-{{$id}}" class="comment-edit-submit" name="submit" value="{{$submit}}" />
					<span onclick="preview_comment({{$id}});" id="comment-edit-preview-link-{{$id}}" class="fakelink">{{$preview}}</span>
					<div id="comment-edit-preview-{{$id}}" class="comment-edit-preview" style="display:none;"></div>
				</div>

				<div class="comment-edit-end"></div>
			</form>

		</div>
