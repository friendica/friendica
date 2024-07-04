<span id="sidebar-accounts-inflated" class="widget inflated fakelink" onclick="openCloseWidget('sidebar-accounts', 'sidebar-accounts-inflated');">
	<h3>{{$title}}</h3>
</span>
<div id="sidebar-accounts" class="widget">
	<span class="fakelink" onclick="openCloseWidget('sidebar-accounts', 'sidebar-accounts-inflated');">
		<h3>{{$title}}</h3>
	</span>
	<ul class="sidebar-accounts-ul">
		<li role="menuitem" class="sidebar-accounts-li{{if !$accounttype}} selected{{/if}}"><a href="{{$content}}">{{$all}}</a></li>
		<li role="menuitem" class="sidebar-accounts-li{{if $accounttype == 'person'}} selected{{/if}}"><a href="{{$content}}/person">{{$person}}</a></li>
		<li role="menuitem" class="sidebar-accounts-li{{if $accounttype == 'organisation'}} selected{{/if}}"><a href="{{$content}}/organisation">{{$organisation}}</a></li>
		<li role="menuitem" class="sidebar-accounts-li{{if $accounttype == 'news'}} selected{{/if}}"><a href="{{$content}}/news">{{$news}}</a></li>
		<li role="menuitem" class="sidebar-accounts-li{{if $accounttype == 'community'}} selected{{/if}}"><a href="{{$content}}/community">{{$community}}</a></li>
	</ul>
</div>
<script>
initWidget('sidebar-accounts', 'sidebar-accounts-inflated');
</script>
