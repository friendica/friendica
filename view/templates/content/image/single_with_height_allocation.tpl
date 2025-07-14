{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
{{* The padding-top height allocation trick only works if the <figure> fills its parent's width completely or with flex. 🤷‍♂️
	As a result, we need to add a wrapping element for non-flex (non-image grid) environments, mostly single-image cases.
 *}}
{{if $allocated_max_width}}
	<div class="img-allocated-max-width" style="max-width: {{$allocated_max_width|default:"auto"}};">
{{/if}}

	<figure class="img-allocated-height" style="width: {{$allocated_width|default:"auto"}}; padding-bottom: {{$allocated_height}}">
		{{if $image->preview}}
			<a data-fancybox="uri-id-{{$image->uriId}}" href="{{$image->url}}">
				<img src="{{$image->preview}}" alt="{{$image->description}}" title="{{$image->description}}" {{if $image->description}}class="has-alt-description"{{else}}class="empty-description"{{/if}} loading="lazy">
			</a>
		{{else}}
			<img src="{{$image->url}}" alt="{{$image->description}}" title="{{$image->description}}" {{if $image->description}}class="has-alt-description"{{else}}class="empty-description"{{/if}} loading="lazy">
			{{if $image->description}}
				<figcaption>{{$image->description}}</figcaption>
			{{/if}}
		{{/if}}
		{{if $image->description}}
			<button class="alt-tag-button" aria-hidden="true" data-toggle="popover" data-title="{{$alt_text_title}}" data-content="{{$image->description}}">
				ALT
			</button>
		{{/if}}
</figure>

{{if $allocated_max_width}}
	</div>
{{/if}}
