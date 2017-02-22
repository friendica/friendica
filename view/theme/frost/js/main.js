
	function openClose(listID) {
/*		if(document.getElementById(theID).style.display == "block") {
			document.getElementById(theID).style.display = "none"
		}
		else {
			document.getElementById(theID).style.display = "block"
		}*/
		listID = "#" + listID.replace(/:/g, "\\:");
		listID = listID.replace(/\./g, "\\.");
		listID = listID.replace(/@/g, "\\@");

		if($(listID).is(":visible")) {
			$(listID).hide();
			$(listID+"-wrapper").show();
		}
		else {
			$(listID).show();
			$(listID+"-wrapper").hide();
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
	var stopped = false;
	var totStopped = false;
	var timer = null;
	var pr = 0;
	var liking = 0;
	var in_progress = false;
	var langSelect = false;
	var commentBusy = false;
	var last_popup_menu = null;
	var last_popup_button = null;

	$(function() {
		$.ajaxSetup({cache: false});

		collapseHeight();

		/* setup tooltips *//*
		$("a,.tt").each(function(){
			var e = $(this);
			var pos="bottom";
			if (e.hasClass("tttop")) pos="top";
			if (e.hasClass("ttbottom")) pos="bottom";
			if (e.hasClass("ttleft")) pos="left";
			if (e.hasClass("ttright")) pos="right";
			e.tipTip({defaultPosition: pos, edgeOffset: 8});
		});*/



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

		/* popup menus */
		function close_last_popup_menu(e) {

 			if( last_popup_menu ) {
				if( '#' + last_popup_menu.attr('id') !== $(e.target).attr('rel')) {
		 			last_popup_menu.hide();
		 			last_popup_button.removeClass("selected");
		 			last_popup_menu = null;
		 			last_popup_button = null;
				}
			}
		}
		$('a[rel^=#]').click(function(e){

			close_last_popup_menu(e);
			menu = $( $(this).attr('rel') );
			e.preventDefault();
			e.stopPropagation();

			if (menu.attr('popup')=="false") return false;

			$(this).parent().toggleClass("selected");
			menu.slideToggle('fast');

			if (menu.css("display") == "none") {
				last_popup_menu = null;
				last_popup_button = null;
			} else {
				last_popup_menu = menu;
				last_popup_button = $(this).parent();
			}
			return false;
		});
		$('html').click(function(e) {
			close_last_popup_menu(e);
		});

		// fancyboxes
		$("a.popupbox").colorbox({
			'inline' : true,
			'transition' : 'elastic'
		});


		/* notifications template */
		var notifications_tpl= unescape($("#nav-notifications-template[rel=template]").html());
		var notifications_all = unescape($('<div>').append( $("#nav-notifications-see-all").clone() ).html()); //outerHtml hack
		var notifications_mark = unescape($('<div>').append( $("#nav-notifications-mark-all").clone() ).html()); //outerHtml hack
		var notifications_empty = unescape($("#nav-notifications-menu").html());

		/* nav update event  */
		$('nav').bind('nav-update', function(e,data){;
			var invalid = $(data).find('invalid').text();
			if(invalid == 1) { window.location.href=window.location.href }

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

			var intro = $(data).find('intro').text();
			if(intro == 0) { intro = '';  $('#intro-update-li').removeClass('show') } else { $('#intro-update-li').addClass('show') }
			$('#intro-update-li').html(intro);

			var mail = $(data).find('mail').text();
			if(mail == 0) { mail = '';  $('#mail-update-li').removeClass('show') } else { $('#mail-update-li').addClass('show') }
			$('#mail-update-li').html(mail);

			var eNotif = $(data).find('notif')

			if (eNotif.children("note").length==0){
				$("#nav-notifications-menu").html(notifications_empty);
			} else {
				nnm = $("#nav-notifications-menu");
				nnm.html(notifications_all + notifications_mark);
				//nnm.attr('popup','true');
				eNotif.children("note").each(function(){
					e = $(this);
					text = e.text().format("<span class='contactname'>"+e.attr('name')+"</span>");
					html = notifications_tpl.format(e.attr('href'),e.attr('photo'), text, e.attr('date'), e.attr('seen'));
					nnm.append(html);
				});

				$("img[data-src]", nnm).each(function(i, el){
					// Add src attribute for images with a data-src attribute
					// However, don't bother if the data-src attribute is empty, because
					// an empty "src" tag for an image will cause some browsers
					// to prefetch the root page of the Friendica hub, which will
					// unnecessarily load an entire profile/ or network/ page
					if($(el).data("src") != '') $(el).attr('src', $(el).data("src"));
				});
			}
			notif = eNotif.attr('count');
			if (notif>0){
				$("#nav-notifications-linkmenu").addClass("on");
			} else {
				$("#nav-notifications-linkmenu").removeClass("on");
			}
			if(notif == 0) { notif = ''; $('#notify-update').removeClass('show') } else { $('#notify-update').addClass('show') }
			$('#notify-update').html(notif);

			var eSysmsg = $(data).find('sysmsgs');
			eSysmsg.children("notice").each(function(){
				text = $(this).text();
				$.jGrowl(text, { sticky: false, theme: 'notice', life: 3000 }); // originally: sticky: true,
			});
			eSysmsg.children("info").each(function(){
				text = $(this).text();
				$.jGrowl(text, { sticky: false, theme: 'info', life: 1000 });
			});

		});


 		NavUpdate();
		// Allow folks to stop the ajax page updates with the pause/break key
		$(document).keydown(function(event) {
			if(event.keyCode == '8') {
				var target = event.target || event.srcElement;
				if (!/input|textarea/i.test(target.nodeName)) {
					return false;
				}
			}
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
			var pingCmd = 'ping' + ((localUser != 0) ? '?f=&uid=' + localUser : '');
			$.get(pingCmd,function(data) {
				$(data).find('result').each(function() {
					// send nav-update event
					$('nav').trigger('nav-update', this);


					// start live update



					if($('#live-network').length)   { src = 'network'; liveUpdate(); }
					if($('#live-profile').length)   { src = 'profile'; liveUpdate(); }
					if($('#live-community').length) { src = 'community'; liveUpdate(); }
					if($('#live-notes').length)     { src = 'notes'; liveUpdate(); }
					if($('#live-display').length) { src = 'display'; liveUpdate(); }
					/*if($('#live-display').length) {
						if(liking) {
							liking = 0;
							window.location.href=window.location.href
						}
					}*/
					if($('#live-photos').length) {
						if(liking) {
							liking = 0;
							window.location.href=window.location.href
						}
					}




				});
			}) ;
		}
		timer = setTimeout(NavUpdate,updateInterval);
	}

	function liveUpdate() {
		if((src == null) || (stopped) || (typeof profile_uid == 'undefined') || (! profile_uid)) { $('.like-rotator').hide(); return; }
		if(($('.comment-edit-text-full').length) || (in_progress)) {
			if(livetime) {
				clearTimeout(livetime);
			}
			livetime = setTimeout(liveUpdate, 5000);
			return;
		}
		if(livetime != null)
			livetime = null;

		prev = 'live-' + src;

		in_progress = true;
		var udargs = ((netargs.length) ? '/' + netargs : '');
		var update_url = 'update_' + src + udargs + '&p=' + profile_uid + '&page=' + profile_page;

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

			$('.toplevel_item',data).each(function() {
				var ident = $(this).attr('id');

				if($('#' + ident).length == 0 && profile_page == 1) {
					$('img',this).each(function() {
						$(this).attr('src',$(this).attr('dst'));
					});
					$('#' + prev).after($(this));
				}
				else {
					// Find out if the hidden comments are open, so we can keep it that way
					// if a new comment has been posted
					var id = $('.hide-comments-total', this).attr('id');
					if(typeof id != 'undefined') {
						id = id.split('-')[3];
						var commentsOpen = $("#collapsed-comments-" + id).is(":visible");
					}

					$('img',this).each(function() {
						$(this).attr('src',$(this).attr('dst'));
					});
					//vScroll = $(document).scrollTop();
					$('html').height($('html').height());
					$('#' + ident).replaceWith($(this));

					if(typeof id != 'undefined') {
						if(commentsOpen) showHideComments(id);
					}
					$('html').height('auto');
					//$(document).scrollTop(vScroll);
				}

				// Add Colorbox for viewing Network page images
				$("#" + ident + " .wall-item-body a img").each(function(){
					var aElem = $(this).parent();
					var imgHref = aElem.attr("href");

					// We need to make sure we only put a Colorbox on links to Friendica images
					// We'll try to do this by looking for links of the form
					// .../photo/ab803d8eg08daf85023adfec08(-0.jpg) (with nothing more following), in hopes
					// that that will be unique enough
					if(imgHref.match(/\/photo\/[a-fA-F0-9]+(-[0-9]\.[\w]+?)?$/)) {

						// Add a unique class to all the images of a certain post, to allow scrolling through
						var cBoxClass = $(this).closest(".wall-item-body").attr("id") + "-lightbox";
						$(this).addClass(cBoxClass);

						aElem.colorbox({
							maxHeight: '90%',
							photo: true,
							rel: cBoxClass
						});
					}
				});

				prev = ident;
			});

			// reset vars for inserting individual items

			/*prev = 'live-' + src;

			$('.wall-item-outside-wrapper',data).each(function() {
				var ident = $(this).attr('id');

				if($('#' + ident).length == 0 && prev != 'live-' + src) {
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
			});*/

			$('.like-rotator').hide();
			if(commentBusy) {
				commentBusy = false;
				$('body').css('cursor', 'auto');
			}
			/* autocomplete @nicknames */
			$(".comment-edit-form  textarea").editor_autocomplete(baseurl+"/acl");

			collapseHeight();

			// setup videos, since VideoJS won't take care of any loaded via AJAX
			if(typeof videojs != 'undefined') videojs.autoSetup();
		});
	}

	function collapseHeight(elems) {
		var elemName = '.wall-item-body:not(.divmore)';
		if(typeof elems != 'undefined') {
			elemName = elems + ' ' + elemName;
		}
		$(elemName).each(function() {
			if($(this).height() > 450) {
				$('html').height($('html').height());
				$(this).divgrow({ initialHeight: 400, showBrackets: false, speed: 0 });
				$(this).addClass('divmore');
				$('html').height('auto');
			}
		});
	}

	/*function imgbright(node) {
		$(node).removeClass("drophide").addClass("drop");
	}

	function imgdull(node) {
		$(node).removeClass("drop").addClass("drophide");
	}*/

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
		$.get('like/' + ident.toString() + '?verb=' + verb, NavUpdate );
		liking = 1;
	}

	function dostar(ident) {
		ident = ident.toString();
//		$('#like-rotator-' + ident).show();
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
//			$('#like-rotator-' + ident).hide();
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
		$("#comment-preview-inp-" + id).val("0");
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


	function preview_comment(id) {
		$("#comment-preview-inp-" + id).val("1");
		$("#comment-edit-preview-" + id).show();
		$.post(
             "item",
             $("#comment-edit-form-" + id).serialize(),
			function(data) {
				if(data.preview) {

					$("#comment-edit-preview-" + id).html(data.preview);
					$("#comment-edit-preview-" + id + " a").click(function() { return false; });
				}
			},
			"json"
         );
         return true;
	}


	function showHideComments(id) {
		if( $("#collapsed-comments-" + id).is(":visible")) {
			$("#collapsed-comments-" + id).hide();
			$("#hide-comments-" + id).html(window.showMore);
		}
		else {
			$("#collapsed-comments-" + id).show();
			$("#hide-comments-" + id).html(window.showFewer);
			collapseHeight("#collapsed-comments-" + id);
		}
	}


	function preview_post() {
		$("#jot-preview").val("1");
		$("#jot-preview-content").show();
		$.post(
			"item",
			$("#profile-jot-form").serialize(),
			function(data) {
				if(data.preview) {
					$("#jot-preview-content").html(data.preview);
					$("#jot-preview-content" + " a").click(function() { return false; });
				}
			},
			"json"
		);
		$("#jot-preview").val("0");
		return true;
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

	function groupChangeMember(gid, cid, sec_token) {
		$('body .fakelink').css('cursor', 'wait');
		$.get('group/' + gid + '/' + cid + "?t=" + sec_token, function(data) {
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

function notifyMarkAll() {
	$.get('notify/mark/all', function(data) {
		if(timer) clearTimeout(timer);
		timer = setTimeout(NavUpdate,1000);
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

function previewTheme(elm) {
	theme = $(elm).val();
	$.getJSON('pretheme?f=&theme=' + theme,function(data) {
			$('#theme-preview').html('<div id="theme-desc">' + data.desc + '</div><div id="theme-version">' + data.version + '</div><div id="theme-credits">' + data.credits + '</div><a href="' + data.img + '"><img src="' + data.img + '" width="320" height="240" alt="' + theme + '" /></a>');
	});

}
