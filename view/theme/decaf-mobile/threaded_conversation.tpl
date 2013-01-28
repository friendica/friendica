$live_update

{{ for $threads as $thread }}
{{ if $mode == display }}
{{ inc $thread.template with $item=$thread }}{{ endinc }}
{{ else }}
{{ inc wall_thread_toponly.tpl with $item=$thread }}{{ endinc }}
{{ endif }}
{{ endfor }}

<div id="conversation-end"></div>

