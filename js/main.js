
  function openClose(theID) {
    if(document.getElementById(theID).style.display == "block") { 
      document.getElementById(theID).style.display = "none" 
    }
    else { 
      document.getElementById(theID).style.display = "block" 
    } 
  }

  function openMenu(theID) {
      document.getElementById(theID).style.display = "block" 
  }

  function closeMenu(theID) {
      document.getElementById(theID).style.display = "none" 
  }

	var src = null;
	var prev = null;
	var livetime = null;
	var msie = false;
	var stopped = false;
	var totStopped = false;
	var timer = null;
	var pr = 0;
	var liking = 0;
	var in_progress = false;
	var langSelect = false;
	var commentBusy = false;

	$(function() {
		$.ajaxSetup({cache: false});

		msie = $.browser.msie ;
		
		/* setup tooltips */
		$("a,.tt").each(function(){
			var e = $(this);
			var pos="bottom";
			if (e.hasClass("tttop")) pos="top";
			if (e.hasClass("ttbottom")) pos="bottom";
			if (e.hasClass("ttleft")) pos="left";
			if (e.hasClass("ttright")) pos="right";
			e.tipTip({defaultPosition: pos, edgeOffset: 8});
		});
		
		
		
		/* setup onoff widgets */
		$(".onoff input").each(function(){
			val = $(this).val();
			id = $(this).attr("id");
			$("#"+id+"_onoff ."+ (val==0?"on":"off")).addClass("hidden");
			
		});
		$(".onoff > a").click(function(event){
			event.preventDefault();	
			var input = $(this).siblings("input");
			var val = 1-input.val();
			var id = input.attr("id");
			$("#"+id+"_onoff ."+ (val==0?"on":"off")).addClass("hidden");
			$("#"+id+"_onoff ."+ (val==1?"on":"off")).removeClass("hidden");
			input.val(val);
			//console.log(id);
		});
		
		/* setup field_richtext */
		setupFieldRichtext();

		/* popup menus */
		$('a[rel^=#]').click(function(e){
			menu = $( $(this).attr('rel') );
			e.preventDefault();
			e.stopPropagation();
			if (menu.attr('popup')=="false") return false;
			$(this).parent().toggleClass("selected");
			menu.toggle();
			return false;
		});
		
		// fancyboxes
		$("a.popupbox").fancybox({
			'transitionIn' : 'elastic',
			'transitionOut' : 'elastic'
		});
		

		/* notifications template */
		var notifications_tpl= unescape($("#nav-notifications-template[rel=template]").html());
		var notifications_empty = unescape($("#nav-notifications-menu").html());
		
		/* nav update event  */
		$('nav').bind('nav-update', function(e,data){;
			var net = $(data).find('net').text();
			if(net == 0) { net = ''; $('#net-update').removeClass('show') } else { $('#net-update').addClass('show') }
			$('#net-update').html(net);

			var home = $(data).find('home').text();
			if(home == 0) { home = '';  $('#home-update').removeClass('show') } else { $('#home-update').addClass('show') }
			$('#home-update').html(home);

 

			var intro = $(data).find('intro').text();
			if(intro == 0) { intro = '';  $('#intro-update').removeClass('show') } else { $('#intro-update').addClass('show') }
			$('#intro-update').html(intro);

			var mail = $(data).find('mail').text();
			if(mail == 0) { mail = '';  $('#mail-update').removeClass('show') } else { $('#mail-update').addClass('show') }
			$('#mail-update').html(mail);

			var eNotif = $(data).find('notif')
			notif = eNotif.attr('count');
			if (notif>0){
				$("#nav-notifications-linkmenu").addClass("on");
				nnm = $("#nav-notifications-menu");
				
				nnm.html("<li><a href='"+baseurl+"/notifications/network'>Show All Notifications</a></li>");
				
				//nnm.attr('popup','true');
				eNotif.children("note").each(function(){
					e = $(this);
					text = e.text().format("<span class='contactname'>"+e.attr('name')+"</span>");
					html = notifications_tpl.format(e.attr('href'),e.attr('photo'), text, e.attr('date'));
					nnm.append(html);
				});
				
			} else {
				$("#nav-notifications-linkmenu").removeClass("on");
				$("#nav-notifications-menu").html(notifications_empty);
			}
			if(notif == 0) { notif = ''; $('#notify-update').removeClass('show') } else { $('#notify-update').addClass('show') }
			$('#notify-update').html(notif);
			
			var eSysmsg = $(data).find('sysmsgs');
			eSysmsg.children("notice").each(function(){
				text = $(this).text();
				$.jGrowl(text, { sticky: true, theme: 'notice' });
			});
			eSysmsg.children("info").each(function(){
				text = $(this).text();
				$.jGrowl(text, { sticky: false, theme: 'info', life: 10000 });
			});
			
		});
		
		
 		NavUpdate(); 
		// Allow folks to stop the ajax page updates with the pause/break key
		$(document).keydown(function(event) {
			if(event.keyCode == '19' || (event.ctrlKey && event.which == '32')) {
				event.preventDefault();
				if(stopped == false) {
					stopped = true;
					if (event.ctrlKey) {
						totStopped = true;
					}
					$('#pause').html('<img src="images/pause.gif" alt="pause" style="border: 1px solid black;" />');
				} else {
					unpause();
				}
			} else {
				if (!totStopped) {
					unpause();
				}
			}
		});
		
		
	});

	function NavUpdate() {

		if(! stopped) {
			$.get("ping",function(data) {
				$(data).find('result').each(function() {
					// send nav-update event
					$('nav').trigger('nav-update', this);
					
					
					// start live update

					if($('#live-network').length)   { src = 'network'; liveUpdate(); }
					if($('#live-profile').length)   { src = 'profile'; liveUpdate(); }
					if($('#live-community').length) { src = 'community'; liveUpdate(); }
					if($('#live-notes').length)     { src = 'notes'; liveUpdate(); }
					if($('#live-display').length) {
						if(liking) {
							liking = 0;
							window.location.href=window.location.href 
						}
					}
					if($('#live-photos').length) { 
						if(liking) {
							liking = 0;
							window.location.href=window.location.href 
						}
					}

					
					
					
				});
			}) ;
		}
		timer = setTimeout(NavUpdate,30000);
	}

	function liveUpdate() {
		if((src == null) || (stopped) || (! profile_uid)) { $('.like-rotator').hide(); return; }
		if(($('.comment-edit-text-full').length) || (in_progress)) {
			livetime = setTimeout(liveUpdate, 10000);
			return;
		}
		prev = 'live-' + src;

		in_progress = true;
		var udargs = ((netargs.length) ? '/' + netargs : '');
		var update_url = 'update_' + src + udargs + '&p=' + profile_uid + '&page=' + profile_page + '&msie=' + ((msie) ? 1 : 0);

		$.get(update_url,function(data) {
			in_progress = false;
			//			$('.collapsed-comments',data).each(function() {
			//	var ident = $(this).attr('id');
			//	var is_hidden = $('#' + ident).is(':hidden');
			//	if($('#' + ident).length) {
			//		$('#' + ident).replaceWith($(this));
			//		if(is_hidden)
			//			$('#' + ident).hide();
			//	}
			//});

			// add a new thread

			$('.tread-wrapper',data).each(function() {
				var ident = $(this).attr('id');
				if($('#' + ident).length == 0) {
					$('img',this).each(function() {
						$(this).attr('src',$(this).attr('dst'));
					});
					$('#' + prev).after($(this));
				}
				prev = ident;
			});

			// reset vars for inserting individual items

			prev = 'live-' + src;

			$('.wall-item-outside-wrapper',data).each(function() {
				var ident = $(this).attr('id');
				if($('#' + ident).length == 0) {
					$('img',this).each(function() {
						$(this).attr('src',$(this).attr('dst'));
					});
					$('#' + prev).after($(this));
				}
				else { 
					$('#' + ident + ' ' + '.wall-item-ago').replaceWith($(this).find('.wall-item-ago')); 
					if($('#' + ident + ' ' + '.comment-edit-text-empty').length)
						$('#' + ident + ' ' + '.wall-item-comment-wrapper').replaceWith($(this).find('.wall-item-comment-wrapper'));
					$('#' + ident + ' ' + '.hide-comments-total').replaceWith($(this).find('.hide-comments-total'));
					$('#' + ident + ' ' + '.wall-item-like').replaceWith($(this).find('.wall-item-like'));
					$('#' + ident + ' ' + '.wall-item-dislike').replaceWith($(this).find('.wall-item-dislike'));
					$('#' + ident + ' ' + '.my-comment-photo').each(function() {
						$(this).attr('src',$(this).attr('dst'));
					});
				}
				prev = ident; 
			});

			$('.like-rotator').hide();
			if(commentBusy) {
				commentBusy = false;
				$('body').css('cursor', 'auto');
			}
			/* autocomplete @nicknames */
			$(".comment-edit-wrapper  textarea").contact_autocomplete(baseurl+"/acl");
		});
	}

	function imgbright(node) {
		$(node).removeClass("drophide").addClass("drop");
	}

	function imgdull(node) {
		$(node).removeClass("drop").addClass("drophide");
	}

	// Since our ajax calls are asynchronous, we will give a few 
	// seconds for the first ajax call (setting like/dislike), then 
	// run the updater to pick up any changes and display on the page.
	// The updater will turn any rotators off when it's done. 
	// This function will have returned long before any of these
	// events have completed and therefore there won't be any
	// visible feedback that anything changed without all this
	// trickery. This still could cause confusion if the "like" ajax call
	// is delayed and NavUpdate runs before it completes.

	function dolike(ident,verb) {
		unpause();
		$('#like-rotator-' + ident.toString()).show();
		$.get('like/' + ident.toString() + '?verb=' + verb );
		if(timer) clearTimeout(timer);
		timer = setTimeout(NavUpdate,3000);
		liking = 1;
	}

	function dostar(ident) {
		ident = ident.toString();
		$('#like-rotator-' + ident).show();
		$.get('starred/' + ident, function(data) {
			if(data.match(/1/)) {
				$('#starred-' + ident).addClass('starred');
				$('#starred-' + ident).removeClass('unstarred');
				$('#star-' + ident).addClass('hidden');
				$('#unstar-' + ident).removeClass('hidden');
			}
			else {			
				$('#starred-' + ident).addClass('unstarred');
				$('#starred-' + ident).removeClass('starred');
				$('#star-' + ident).removeClass('hidden');
				$('#unstar-' + ident).addClass('hidden');
			}
			$('#like-rotator-' + ident).hide();	
		});
	}

	function getPosition(e) {
		var cursor = {x:0, y:0};
		if ( e.pageX || e.pageY  ) {
			cursor.x = e.pageX;
			cursor.y = e.pageY;
		}
		else {
			if( e.clientX || e.clientY ) {
				cursor.x = e.clientX + (document.documentElement.scrollLeft || document.body.scrollLeft) - document.documentElement.clientLeft;
				cursor.y = e.clientY + (document.documentElement.scrollTop  || document.body.scrollTop)  - document.documentElement.clientTop;
			}
			else {
				if( e.x || e.y ) {
					cursor.x = e.x;
					cursor.y = e.y;
				}
			}
		}
		return cursor;
	}

	var lockvisible = false;

	function lockview(event,id) {
		event = event || window.event;
		cursor = getPosition(event);
		if(lockvisible) {
			lockviewhide();
		}
		else {
			lockvisible = true;
			$.get('lockview/' + id, function(data) {
				$('#panel').html(data);
				$('#panel').css({ 'left': cursor.x + 5 , 'top': cursor.y + 5});
				$('#panel').show();
			});
		}
	}

	function lockviewhide() {
		lockvisible = false;
		$('#panel').hide();
	}

	function post_comment(id) {
		unpause();
		commentBusy = true;
		$('body').css('cursor', 'wait');
		$.post(  
             "item",  
             $("#comment-edit-form-" + id).serialize(),
			function(data) {
				if(data.success) {
					$("#comment-edit-wrapper-" + id).hide();
					$("#comment-edit-text-" + id).val('');
    	  			var tarea = document.getElementById("comment-edit-text-" + id);
					if(tarea)
						commentClose(tarea,id);
					if(timer) clearTimeout(timer);
					timer = setTimeout(NavUpdate,10);
				}
				if(data.reload) {
					window.location.href=data.reload;
				}
			},
			"json"  
         );  
         return false;  
	}

	function unpause() {
		// unpause auto reloads if they are currently stopped
		totStopped = false;
		stopped = false;
	    $('#pause').html('');
	}
		

    function bin2hex(s){  
        // Converts the binary representation of data to hex    
        //   
        // version: 812.316  
        // discuss at: http://phpjs.org/functions/bin2hex  
        // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)  
        // +   bugfixed by: Onno Marsman  
        // +   bugfixed by: Linuxworld  
        // *     example 1: bin2hex('Kev');  
        // *     returns 1: '4b6576'  
        // *     example 2: bin2hex(String.fromCharCode(0x00));  
        // *     returns 2: '00'  
        var v,i, f = 0, a = [];  
        s += '';  
        f = s.length;  
          
        for (i = 0; i<f; i++) {  
            a[i] = s.charCodeAt(i).toString(16).replace(/^([\da-f])$/,"0$1");  
        }  
          
        return a.join('');  
    }  

	function groupChangeMember(gid,cid) {
		$('body .fakelink').css('cursor', 'wait');
		$.get('group/' + gid + '/' + cid, function(data) {
				$('#group-update-wrapper').html(data);
				$('body .fakelink').css('cursor', 'auto');				
		});
	}

	function profChangeMember(gid,cid) {
		$('body .fakelink').css('cursor', 'wait');
		$.get('profperm/' + gid + '/' + cid, function(data) {
				$('#prof-update-wrapper').html(data);
				$('body .fakelink').css('cursor', 'auto');				
		});
	}

	function contactgroupChangeMember(gid,cid) {
		$('body').css('cursor', 'wait');
		$.get('contactgroup/' + gid + '/' + cid, function(data) {
				$('body').css('cursor', 'auto');
		});
	}


function checkboxhighlight(box) {
  if($(box).is(':checked')) {
	$(box).addClass('checkeditem');
  }
  else {
	$(box).removeClass('checkeditem');
  }
}

function setupFieldRichtext(){
	tinyMCE.init({
		theme : "advanced",
		mode : "specific_textareas",
		editor_selector: "fieldRichtext",
		plugins : "bbcode,paste",
		theme_advanced_buttons1 : "bold,italic,underline,undo,redo,link,unlink,image,forecolor,formatselect,code",
		theme_advanced_buttons2 : "",
		theme_advanced_buttons3 : "",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "center",
		theme_advanced_blockformats : "blockquote,code",
		paste_text_sticky : true,
		entity_encoding : "raw",
		add_unload_trigger : false,
		remove_linebreaks : false,
		force_p_newlines : false,
		force_br_newlines : true,
		forced_root_block : '',
		convert_urls: false,
		content_css: baseurl+"/view/custom_tinymce.css",
		theme_advanced_path : false,
	});
}


/** 
 * sprintf in javascript 
 *	"{0} and {1}".format('zero','uno'); 
 **/
String.prototype.format = function() {
    var formatted = this;
    for (var i = 0; i < arguments.length; i++) {
        var regexp = new RegExp('\\{'+i+'\\}', 'gi');
        formatted = formatted.replace(regexp, arguments[i]);
    }
    return formatted;
};
// Array Remove
Array.prototype.remove = function(item) {
  to=undefined; from=this.indexOf(item);
  var rest = this.slice((to || from) + 1 || this.length);
  this.length = from < 0 ? this.length + from : from;
  return this.push.apply(this, rest);
};

