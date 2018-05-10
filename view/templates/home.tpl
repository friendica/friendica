
{{* custom content from hook will replace everything. *}}
{{if $content != '' }}
	{{$content}}
{{else}}

	{{if $customhome != false }}
		{{include file="$customhome"}}
	{{else}}
		{{$defaultheader}}
	{{/if}}

	{{$login}}
{{/if}}
