{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<script type="text/javascript" src="view/asset/es-jquery-sortable/source/js/jquery-sortable.js"></script>
<script>
	var ispublic = "{{$ispublic nofilter}}";
	
	// make widget indexing generic
	function indexList(input,widgets,children){
		var items = $(widgets).children(children);
		var order = [];
		for(let i=0; i < items.length; i++){
			$(items[i]).attr('data-order',i);
			$(items[i]).attr('tabindex',0);
			order.push(items[i].id.replace('div_id_feature_','').replace('div_id_enable[','').replace('div_id_bookmark[','').replace(']',''));
		}
		$(input).val(JSON.stringify(order));
	}

	// arrow buttons and keyboard reordering
	function moveListItem(element,direction){
		// adjust target list item depending on button or keyboard control
		if (element.className == 'sorter-mvup' || element.className == 'sorter-mvdn'){
			var target = $(element).parent();
		} else {
			var target = $(element);
		}
		let total = $('.network .field').length;
		// get the currently selected item index
		let index = parseInt($(target).attr('data-order'));
		let before = parseInt(index-1);
		let after  = parseInt(index+1);
		if(direction == 'up'){ // insert before
			if (index != 0){
				$(target).addClass('borderani move-up');
				// this is for screen readers to announce action
				$(target).attr('title','Moved up one space, position '+index+' in list');
				previous = $(target).prev();
				$(target).insertBefore( previous );
			}
		}
		if (direction == 'down'){ // insert after
			if (index != (total-1)){
				$(target).addClass('borderani move-dn');
				// this is for screen readers to announce action
				$(target).attr('title','Moved down one space, position '+parseInt(after+1)+' in list');
				next = $(target).next();				
				$(target).insertAfter( next );
			}
		};
		// timeout to clear title for screen readers
		setTimeout(function(that){	$(that).attr('title','');$(that).removeClass('borderani move-up move-dn'); },1000, $(target));
		// immediately update list index numbers
		if ($(".network").length > 0){
			indexList('#feature_widgetorder','.network','.field');
		}
		if ($(".timelines-widget").length > 0){
			indexList('#widget_timelineorder','.timelines-widget','.field');
		}
		if ($(".timelines-menu").length > 0){
			indexList('#menu_timelineorder','.timelines-menu','.field');
		}
		// set focus on list item
		$(target).focus();
	}
	
	// check if icon font is loaded, if not load core stylesheet
	document.fonts.ready.then((fontFaceSet) => {
	  const fontFaces = [...fontFaceSet];
	  var awesomeLoaded = false;
	  for(var f=0; f < fontFaces.length; f++){
		if (fontFaces[f]['family'] == '"FontAwesome"' || fontFaces[f]['family'] == '"ForkAwesome"' ||
			fontFaces[f]['family'] == 'FontAwesome'   || fontFaces[f]['family'] == 'ForkAwesome' ){
			awesomeLoaded = true;
		}
	  }
	  if (!awesomeLoaded){
		$('head').append('<link rel="stylesheet" type="text/css" href="'+baseurl+'/view/asset/fork-awesome/css/fork-awesome.css">');
	  }
	});

	$(document).ready(function() {
		$('#contact_allow, #contact_deny, #circle_allow, #circle_deny').change(function() {
			var selstr;
			$('#contact_allow option:selected, #contact_deny option:selected, #circle_allow option:selected, #circle_deny option:selected').each( function() {
				selstr = $(this).html();
				$('#jot-perms-icon').removeClass('unlock').addClass('lock');
				$('#jot-public').hide();
			});
			if(selstr == null) {
				$('#jot-perms-icon').removeClass('lock').addClass('unlock');
				$('#jot-public').show();
			}

		}).trigger('change');

		$('.settings-content-block').hide();
		$('.settings-heading').click(function(){
			$('.settings-content-block').hide();
			$(this).next('.settings-content-block').toggle();
		});
		$( function() {
			// check if primary input is touch
			if(window.matchMedia("(hover: none) and (pointer: coarse)").matches){ 
				return; 
			} else if ('ontouchstart' in window){
				// fallback for older devices
				return;
			} else {
				// assume drag-n-drop will work
			}
			var $container; // or onDrop loses reference
			$(".sortable").sortable({
				connectWith : ".sortable",
				containerSelector: "div",
				containerPath : ".sortable > .field",
				itemSelector : ".field",
				placeholder : '<div class="placeholder"></div>',
				nested: false, 
    			onDragStart: function ($item, container, _super, event) {
      				$item.css({
        				height: $item.outerHeight(),
       				 	width: $item.outerWidth()
      				});
      				$item.addClass(container.group.options.draggedClass);
      				$("body").addClass(container.group.options.bodyClass);
      				$container = container;
    			},
				onDrop : function($item, container, _super, event){
					if(!container){ container = $container;};
      				$item.removeClass(container.group.options.draggedClass).removeAttr("style");
      				$("body").removeClass(container.group.options.bodyClass);
      			},
      			afterMove : function($placeholder, container, $closestItemOrContainer){
      				// need a delay so drag-n-drop is done before we re-index
      				setTimeout(function(){
      					if ($(".network").length > 0){
      						indexList('#feature_widgetorder','.network','.field');
      					}
      					if ($(".timelines-widget").length > 0){
      						indexList('#widget_timelineorder','.timelines-widget','.field');
      					}
      					if ($(".timelines-menu").length > 0){
      						indexList('#menu_timelineorder','.timelines-menu','.field');
      					} 
      				},1000);
      			}					
			});
		});

		// initial order index
		indexList('#feature_widgetorder','.network','.field');
		indexList('#widget_timelineorder','.timelines-widget','.field');
		indexList('#menu_timelineorder','.timelines-menu','.field');
		
		// add arrow buttons for touch devices that cannot drag-n-drop or keyboard sort
		$('.network .field, .timelines-widget .field, .timelines-menu .field').each(function(){
			$(this).append('<div class="sorter-mvup" onclick="moveListItem(this,\'up\');" aria-hidden="true"></div><div class="sorter-mvdn" onclick="moveListItem(this,\'down\');" aria-hidden="true"></div>');
		});

		// accessible sorting with keyboard gives feedback to screenreaders
		$('.network .field, .timelines-widget .field, .timelines-menu .field').bind('keydown', function(event){
			if(event.which == 38){
				moveListItem(this,'up');
			}
			if (event.which == 40){
				moveListItem(this,'down');
			}
		});	
	});

</script>

<style>
/* styling for sortable lists */
.placeholder {
	height: 75px;
	width: 100%;
	background-color: rgba(128,128,128,.1);
	border: 1px dashed var(--link-color, blue);
	list-style: none;
}
.sortable > .field {
	position: relative;
	border: 1px solid var(--border-color, #eee);
	padding: 10px 30px 10px 30px;
	margin: 0;
	box-sizing: border-box;
	min-height: 75px;
	overflow: visible;
}
	.sortable > .field:first-of-type {
		border-top-left-radius: 8px;
		border-top-right-radius: 8px;
	}
	.sortable > .field:nth-last-of-type(2) {
		border-bottom-left-radius: 8px;
		border-bottom-right-radius: 8px;
	}
	.dragged {
		position: absolute !important;
	}
	[class^="sorter-"] {
		position: absolute;
		right: 5px;
		height: 24px;
		width:  24px;
		border-radius: 100%;
		color: inherit;
		text-align: center;
		opacity: .5;
		z-index: 1;
	}
		[class^="sorter-"]:hover,
		[class^="sorter-"]:active {
			background-color: var(--dimbright, rgba(128,128,128,.2));
			color: var(--link-color, inherit);
			opacity: 1;
		}
	.sorter-mvup {
		top: 10px;
	}
		.sorter-mvup::before {
			content: '\f106';
			font-family: "ForkAwesome","FontAwesome";
			font-size: 14px;
			line-height: 24px;
		}
	.sorter-mvdn {
		bottom: 10px;
	}
		.sorter-mvdn::before {
			content: '\f107';
			font-family: "ForkAwesome","FontAwesome";
			font-size: 14px;
			line-height: 24px;	
		}
	/* motion indicator animations */
	@keyframes borderflash {
	  0% {
		border-color: var(--link-color, blue);
	  }
	  100% {
		border-color:  var(--border-color, #eee);
	  }
	}
	@keyframes aniUp {
		0% {
			top: 100%;
		}
		100% {
			top: 0%;
		}
	}
	@keyframes aniDn {
		0% {
			bottom: 100%;
		}
		100% {
			bottom: 0%;
		}
	}
	.borderani {
		animation: borderflash .5s ease .5s infinite normal none;
	}
		.borderani.move-up:before {
			content: '';
			display: block;
			position: absolute;
			top: 0%;
			left: 0;
			width: 100%;
			height: 100%;
			border: 1px solid var(--link-color, blue);
			animation: aniUp .5s ease 0s 1 normal none; 
		}
		.borderani.move-dn:after {
			content: '';
			display: block;
			position: absolute;
			bottom: 0%;
			left: 0;
			width: 100%;
			height: 100%;
			border: 1px solid var(--link-color, blue);
			animation: aniDn .5s ease 0s 1 normal none;
		}
</style>
