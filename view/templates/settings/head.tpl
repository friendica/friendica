{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<script type="text/javascript" src="view/asset/es-jquery-sortable/source/js/jquery-sortable.js"></script>
<script>
	var ispublic = "{{$ispublic nofilter}}";


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
      				console.log(container);
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
		// initial order index
		indexList('#feature_widgetorder','.network','.field');
		indexList('#widget_timelineorder','.timelines-widget','.field');
		indexList('#menu_timelineorder','.timelines-menu','.field');
		// accessible sorting with keyboard gives feedback to screenreaders
		$('.network .field, .timelines-widget .field, .timelines-menu .field').bind('keydown', function(event){
			let total = $('.network .field').length;
			console.log('total = '+total);
			// get the currently selected item index
			let index = parseInt($(this).attr('data-order'));
			let before = parseInt(index-1);
			let after  = parseInt(index+1);
			if(event.which == 38){ // up arrow
				console.log('up arrow');
				if (index != 0){
					console.log('insert before '+before);
					$(this).attr('title','Moved up one space, position '+index+' in list');
					$(this).insertBefore( $('[data-order="'+before+'"]') );
					setTimeout(function(that){
      					if ($(".network").length > 0){
      						indexList('#feature_widgetorder','.network','.field');
      					}
      					if ($(".timelines-widget").length > 0){
      						indexList('#widget_timelineorder','.timelines-widget','.field');
      					}
      					if ($(".timelines-menu").length > 0){
      						indexList('#menu_timelineorder','.timelines-menu','.field');
      					} 
						$(that).attr('title','');
					},1000, $(this));
				}
				$(this).focus();
			}
			if (event.which == 40){ //down arrow
				console.log('down arrow');
				if (index != (total-1)){
					console.log('insert after'+after);
					$(this).attr('title','Moved down one space, position '+parseInt(after+1)+' in list');
					$(this).insertAfter( $('[data-order="'+after+'"]') );
					setTimeout(function(that){
      					if ($(".network").length > 0){
      						indexList('#feature_widgetorder','.network','.field');
      					}
      					if ($(".timelines-widget").length > 0){
      						indexList('#widget_timelineorder','.timelines-widget','.field');
      					}
      					if ($(".timelines-menu").length > 0){
      						indexList('#menu_timelineorder','.timelines-menu','.field');
      					} 
						$(that).attr('title','');
					},1000, $(this));
				}
				$(this).focus();
			}
		});	
	});

</script>

<style>
/* styling for sortable lists */
.placeholder {
	height: 60px;
	width: 100%;
	background-color: rgba(255,255,255,.3);
	border: 1px dashed black;
	list-style: none;
}
.sortable > .field {
	position: relative;
	border: 1px solid #eee;
	padding: 10px 30px 10px 30px;
	margin: 0;
	box-sizing: border-box;
}
	.sortable > .field:first-of-type {
		border-top-left-radius: 8px;
		border-top-right-radius: 8px;
	}
	.sortable > .field:nth-last-of-type(2) {
		border-bottom-left-radius: 8px;
		border-bottom-right-radius: 8px;
	}
	.sortable > .field::after {
		content: '\2B0D';	/* unicode double-arrow */
		position: absolute;
		font-size: 18px;
		right: 15px;
		top: 40%;
		opacity: .2;
	}
	.sortable > .field:hover::after,
	.sortable > .field:focus::after,
	.sortable > .field:active::after,
	.sortable > .field:focus-within::after {
		opacity: 1;
	}
	.dragged {
		position: absolute !important;
	}
</style>

<script>
/*
 * Content-Type:text/javascript
 *
 * A bridge between iPad and iPhone touch events and jquery draggable, 
 * sortable etc. mouse interactions.
 * @author Oleg Slobodskoi  
 * 
 * modified by John Hardy to use with any touch device
 * fixed breakage caused by jquery.ui so that mouseHandled internal flag is reset 
 * before each touchStart event
 * 
 */
(function( $ ) {

    $.support.touch = typeof Touch === 'object';

    if (!$.support.touch) {
        return;
    }

    var proto =  $.ui.mouse.prototype,
    _mouseInit = proto._mouseInit;

    $.extend( proto, {
        _mouseInit: function() {
            this.element
            .bind( "touchstart." + this.widgetName, $.proxy( this, "_touchStart" ) );
            _mouseInit.apply( this, arguments );
        },

        _touchStart: function( event ) {
            if ( event.originalEvent.targetTouches.length != 1 ) {
                return false;
            }

            this.element
            .bind( "touchmove." + this.widgetName, $.proxy( this, "_touchMove" ) )
            .bind( "touchend." + this.widgetName, $.proxy( this, "_touchEnd" ) );

            this._modifyEvent( event );

            $( document ).trigger($.Event("mouseup")); //reset mouseHandled flag in ui.mouse
            this._mouseDown( event );

            return false;           
        },

        _touchMove: function( event ) {
            this._modifyEvent( event );
            this._mouseMove( event );   
        },

        _touchEnd: function( event ) {
            this.element
            .unbind( "touchmove." + this.widgetName )
            .unbind( "touchend." + this.widgetName );
            this._mouseUp( event ); 
        },

        _modifyEvent: function( event ) {
            event.which = 1;
            var target = event.originalEvent.targetTouches[0];
            event.pageX = target.clientX;
            event.pageY = target.clientY;
        }

    });

})( jQuery );
</script>
