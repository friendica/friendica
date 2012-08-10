		<div class="comment-wwedit-wrapper" id="comment-edit-wrapper-$id" style="display: block;">
			<form class="comment-edit-form" id="comment-edit-form-$id" action="item" method="post" onsubmit="post_comment($id); return false;">
				<input type="hidden" name="type" value="$type" />
				<input type="hidden" name="profile_uid" value="$profile_uid" />
				<input type="hidden" name="parent" value="$parent" />
				<input type="hidden" name="return" value="$return_path" />
				<input type="hidden" name="jsreload" value="$jsreload" />
				<input type="hidden" name="preview" id="comment-preview-inp-$id" value="0" />

				<div class="comment-edit-photo" id="comment-edit-photo-$id" >
					<a class="comment-edit-photo-link" href="$mylink" title="$mytitle"><img class="my-comment-photo" src="$myphoto" alt="$mytitle" title="$mytitle" /></a>
				</div>
				<ul id="comment-edit-bb-$id"
					class="comment-edit-bb">
					<li><a class="editicon boldbb shadow"
						style="cursor: pointer;" title="$edbold"
						onclick="insertFormatting('$comment','b', $id);"></a></li>
					<li><a class="editicon italicbb shadow"
						style="cursor: pointer;" title="$editalic"
						onclick="insertFormatting('$comment','i', $id);"></a></li>
					<li><a class="editicon underlinebb shadow"
						style="cursor: pointer;" title="$eduline"
						onclick="insertFormatting('$comment','u', $id);"></a></li>
					<li><a class="editicon quotebb shadow"
						style="cursor: pointer;" title="$edquote"
						onclick="insertFormatting('$comment','quote', $id);"></a></li>
					<li><a class="editicon codebb shadow"
						style="cursor: pointer;" title="$edcode"
						onclick="insertFormatting('$comment','code', $id);"></a></li>
					<li><a class="editicon imagebb shadow"
						style="cursor: pointer;" title="$edimg"
						onclick="insertFormatting('$comment','img', $id);"></a></li>
					<li><a class="editicon urlbb shadow"
						style="cursor: pointer;" title="$edurl"
						onclick="insertFormatting('$comment','url', $id);"></a></li>
					<li><a class="editicon videobb shadow"
						style="cursor: pointer;" title="$edvideo"
						onclick="insertFormatting('$comment','video', $id);"></a></li>
				</ul>	
				<textarea id="comment-edit-text-$id" 
					class="comment-edit-text-empty" 
					name="body" 
					onFocus="commentOpen(this,$id) && cmtBbOpen($id);" 
					onBlur="commentClose(this,$id) && cmtBbClose($id);" >$comment</textarea>
				{{ if $qcomment }}
					<select id="qcomment-select-$id" name="qcomment-$id" class="qcomment" onchange="qCommentInsert(this,$id);" >
					<option value=""></option>
				{{ for $qcomment as $qc }}
					<option value="$qc">$qc</option>				
				{{ endfor }}
					</select>
				{{ endif }}

				<div class="comment-edit-submit-wrapper" id="comment-edit-submit-wrapper-$id" style="display: none;" >
					<input type="submit" onclick="post_comment($id); return false;" id="comment-edit-submit-$id" class="comment-edit-submit" name="submit" value="$submit" />
					<span onclick="preview_comment($id);" id="comment-edit-preview-link-$id" class="fakelink">$preview</span>
					<div id="comment-edit-preview-$id" class="comment-edit-preview" style="display:none;"></div>
				</div>

			</form>

		</div>
