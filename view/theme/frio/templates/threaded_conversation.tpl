<script type="text/javascript" src="view/theme/frio/frameworks/jquery-color/jquery.color.js"></script>
{{if $mode == display}}<script type="text/javascript" src="view/theme/frio/js/mod_display.js"></script>{{/if}}

{{$live_update}}

{{foreach $threads as $thread}}
<hr class="sr-only" />
<div id="tread-wrapper-{{$thread.id}}" class="tread-wrapper {{if $thread.threaded}}threaded{{/if}} {{$thread.toplevel}} {{$thread.network}} {{if $thread.thread_level==1}}panel-default panel{{/if}} {{if $thread.thread_level!=1}}comment-wrapper{{/if}}" style="{{if $item.thread_level>2}}margin-left: -15px; margin-right:-16px; margin-bottom:-16px;{{/if}}"><!-- panel -->

		{{* {{if $thread.type == tag}}
			{{include file="wall_item_tag.tpl" item=$thread}}
		{{else}}
			{{include file="{{$thread.template}}" item=$thread}}
		{{/if}} *}} {{include file="{{$thread.template}}" item=$thread}}

</div><!--./tread-wrapper-->
{{/foreach}}

<div id="conversation-end"></div>

{{if $dropping}}
<button type="button" id="item-delete-selected" class="btn btn-link" title="{{$dropping}}" onclick="deleteCheckedItems();" data-toggle="tooltip">
	<i class="fa fa-trash" aria-hidden="true"></i>
</button>
<img id="item-delete-selected-rotator" class="like-rotator" src="images/rotator.gif" style="display: none;" />
{{/if}}
