{{ if $indent }}{{ else }}
<div class="wall-item-decor">
	<img id="like-rotator-$id" class="like-rotator" src="images/rotator.gif" alt="$wait" title="$wait" style="display: none;" />
</div>
{{ endif }}

<div class="wall-item-photo-container $indent">
	<div class="wall-item-item">
		<div class="wall-item-info">
			<div class="contact-photo-wrapper" >
				<a href="$profile_url" target="redir" title="" class="contact-photo-link" id="wall-item-photo-link-$id">
					<img src="$thumb" class="contact-photo$sparkle" id="wall-item-photo-$id" alt="$name" />
				</a>
				<a href="#" rel="#wall-item-photo-menu-$id" class="contact-photo-menu-button icon s16 menu" id="wall-item-photo-menu-button-$id">menu</a>
				<ul class="contact-menu menu-popup" id="wall-item-photo-menu-$id">
				$photo_menu
				</ul>
				
			</div>
		</div>
			<div class="wall-item-actions-author">
				<a href="$profile_url" target="redir" title="$name" class="wall-item-name-link"><span class="wall-item-name$sparkle">$name</span></a> 
			<span class="wall-item-ago">-
			{{ if $plink }}<a class="link" title="$plink.title" href="$plink.href" style="color: #999">$ago</a>{{ else }} $ago {{ endif }}
			{{ if $lock }} - <span class="fakelink" style="color: #999" onclick="lockview(event,$id);">$lock</span> {{ endif }}
			</span>
			</div>
		<div class="wall-item-content">
			{{ if $title }}<h2><a href="$plink.href">$title</a></h2>{{ endif }}
			$body
		</div>
	</div>
	<div class="wall-item-bottom">
		<div class="wall-item-links">
		</div>
		<div class="wall-item-tags">
			{{ for $tags as $tag }}
				<span class='tag'>$tag</span>
			{{ endfor }}
		</div>
	</div>
	
	<div class="wall-item-bottom" style="display: table-row;">
		<div class="wall-item-actions">
	   </div>
		<div class="wall-item-actions">
			
			<div class="wall-item-actions-tools">

				{{ if $drop.dropping }}
					<input type="checkbox" title="$drop.select" name="itemselected[]" class="item-select" value="$id" />
					<a href="item/drop/$id" onclick="return confirmDelete();" class="icon drop" title="$drop.delete">$drop.delete</a>
				{{ endif }}
				{{ if $edpost }}
					<a class="icon pencil" href="$edpost.0" title="$edpost.1"></a>
				{{ endif }}
			</div>

		</div>
	</div>
	<div class="wall-item-bottom">
			
	</div>
</div>

