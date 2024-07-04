<span id="tagblock-inflated" class="widget inflated fakelink" onclick="openCloseWidget('tagblock', 'tagblock-inflated');">
	<h3>{{$title}}</h3>
</span>
<div id="tagblock" class="tagblock widget">
	<span class="fakelink" onclick="openCloseWidget('tagblock', 'tagblock-inflated');">
		<h3>{{$title}}</h3>
        </span>

	<div class="tag-cloud">
		{{foreach $tags as $tag}}
		<span class="tags">
			<span class="tag{{$tag.level}}">#</span><a href="{{$tag.url}}" class="tag{{$tag.level}}">{{$tag.name}}</a>
		</span>
		{{/foreach}}
	</div>
	<div class="tagblock-widget-end clear"></div>
</div>
<script>
initWidget('tagblock', 'tagblock-inflated');
</script>
