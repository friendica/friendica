	function resizeIframe(obj) {
		//obj.style.height = 0;
		_resizeIframe(obj, 0);
	}

	function _resizeIframe(obj, desth) {
		var h = obj.style.height;
		var ch = obj.contentWindow.document.body.scrollHeight;
		if (h == (ch + 'px')) {
			return;
		}
		if (desth == ch && ch>0) {
			obj.style.height  = ch + 'px';
		}
		setTimeout(_resizeIframe, 100, obj, ch);
	}

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

	function decodeHtml(html) {
		var txt = document.createElement("textarea");
		txt.innerHTML = html;
		return txt.value;
	}


	var src = null;
	var prev = null;
	var livetime = null;
	var force_update = false;
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
	var lockLoadContent = false;

	$(function() {
		$.ajaxSetup({cache: false});

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

		/* setup comment textarea buttons */
		/* comment textarea buttons needs some "data-*" attributes to work:
		 * 		data-role="insert-formatting" : to mark the element as a formatting button
		 * 		data-comment="<string>" : string for "Comment", used by insertFormatting() function
		 * 		data-bbcode="<string>" : name of the bbcode element to insert. insertFormatting() will insert it as "[name][/name]"
		 * 		data-id="<string>" : id of the comment, used to find other comment-related element, like the textarea
		 * */
		$('body').on('click','[data-role="insert-formatting"]', function(e) {
			e.preventDefault();
			var o = $(this);
			var comment = o.data('comment');
			var bbcode  = o.data('bbcode');
			var id = o.data('id');
			if (bbcode=="img") {
				Dialog.doImageBrowser("comment", id);
				return;
			}
			insertFormatting(comment, bbcode, id);
		});

		/* event from comment textarea button popups */
		/* insert returned bbcode at cursor position or replace selected text */
		$("body").on("fbrowser.image.comment", function(e, filename, bbcode, id) {
			console.log("on", id);
			$.colorbox.close();
			var textarea = document.getElementById("comment-edit-text-" +id);
			var start = textarea.selectionStart;
			var end = textarea.selectionEnd;
			textarea.value = textarea.value.substring(0, start) + bbcode + textarea.value.substring(end, textarea.value.length);
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
		function close_last_popup_menu() {
			if(last_popup_menu) {
				last_popup_menu.hide();
				last_popup_menu.off('click', function(e) {e.stopPropagation()});
				last_popup_button.removeClass("selected");
				last_popup_menu = null;
				last_popup_button = null;
			}
		}
		$('a[rel^=#]').click(function(e){
			e.preventDefault();
			var parent = $(this).parent();
			var isSelected = (last_popup_button && parent.attr('id') == last_popup_button.attr('id'));
			close_last_popup_menu();
			if(isSelected) return false;
			menu = $( $(this).attr('rel') );
			e.preventDefault();
			e.stopPropagation();
			if (menu.attr('popup')=="false") return false;
			parent.toggleClass("selected");
			menu.toggle();
			if (menu.css("display") == "none") {
				last_popup_menu = null;
				last_popup_button = null;
			} else {
				last_popup_menu = menu;
				last_popup_menu.on('click', function(e) {e.stopPropagation()});
				last_popup_button = parent;
				$('#nav-notifications-menu').perfectScrollbar('update');
			}
			return false;
		});
		$('html').click(function() {
			close_last_popup_menu();
		});

		// fancyboxes
		$("a.popupbox").colorbox({
			'inline' : true,
			'transition' : 'elastic'
		});
		$("a.ajax-popupbox").colorbox({
			'transition' : 'elastic'
		});

		/* notifications template */
		var notifications_tpl= unescape($("#nav-notifications-template[rel=template]").html());
		var notifications_all = unescape($('<div>').append( $("#nav-notifications-see-all").clone() ).html()); //outerHtml hack
		var notifications_mark = unescape($('<div>').append( $("#nav-notifications-mark-all").clone() ).html()); //outerHtml hack
		var notifications_empty = unescape($("#nav-notifications-menu").html());

		/* enable perfect-scrollbars for different elements */
		$('#nav-notifications-menu, aside').perfectScrollbar();

		/* nav update event  */
		$('nav').bind('nav-update', function(e,data){
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


			var allevents = $(data).find('all-events').text();
			if(allevents == 0) { allevents = ''; $('#allevents-update').removeClass('show') } else { $('#allevents-update').addClass('show') }
			$('#allevents-update').html(allevents);

			var alleventstoday = $(data).find('all-events-today').text();
			if(alleventstoday == 0) { $('#allevents-update').removeClass('notif-allevents-today') } else { $('#allevents-update').addClass('notif-allevents-today') }

			var events = $(data).find('events').text();
			if(events == 0) { events = ''; $('#events-update').removeClass('show') } else { $('#events-update').addClass('show') }
			$('#events-update').html(events);

			var eventstoday = $(data).find('events-today').text();
			if(eventstoday == 0) { $('#events-update').removeClass('notif-events-today') } else { $('#events-update').addClass('notif-events-today') }

			var birthdays = $(data).find('birthdays').text();
			if(birthdays == 0) {birthdays = ''; $('#birthdays-update').removeClass('show') } else { $('#birthdays-update').addClass('show') }
			$('#birthdays-update').html(birthdays);

			var birthdaystoday = $(data).find('birthdays-today').text();
			if(birthdaystoday == 0) { $('#birthdays-update').removeClass('notif-birthdays-today') } else { $('#birthdays-update').addClass('notif-birthdays-today') }

			$(".sidebar-group-li .notify").removeClass("show");
			$(data).find("group").each(function() {
				var gid = this.id;
				var gcount = this.innerHTML;
				$(".group-"+gid+" .notify").addClass("show").text(gcount);
			});

			$(".forum-widget-entry .notify").removeClass("show");
			$(data).find("forum").each(function() {
				var fid = this.id;
				var fcount = this.innerHTML;
				$(".forum-"+fid+" .notify").addClass("show").text(fcount);
			});


			var eNotif = $(data).find('notif')

			if (eNotif.children("note").length==0){
				$("#nav-notifications-menu").html(notifications_empty);
			} else {
				nnm = $("#nav-notifications-menu");
				nnm.html(notifications_all + notifications_mark);
				//nnm.attr('popup','true');

				var notification_lastitem = parseInt(localStorage.getItem("notification-lastitem"));
				var notification_id = 0;
				eNotif.children("note").each(function(){
					e = $(this);
					var text = e.text().format("<span class='contactname'>"+e.attr('name')+"</span>");
					var contact = ("<a href="+e.attr('url')+"><span class='contactname'>"+e.attr('name')+"</span></a>");
					var seenclass = (e.attr('seen')==1)?"notify-seen":"notify-unseen";
					var html = notifications_tpl.format(
						e.attr('href'),                     // {0}  // link to the source
						e.attr('photo'),                    // {1}  // photo of the contact
						text,                               // {2}  // preformatted text (autor + text)
						e.attr('date'),                     // {3}  // date of notification (time ago)
						seenclass,                          // {4}  // visited status of the notification
						new Date(e.attr('timestamp')*1000), // {5}  // date of notification
						e.attr('url'),                      // {6}  // profile url of the contact
						e.text().format(contact),           // {7}  // preformatted html (text including author profile url)
						''                                  // {8}  // Deprecated
					);
					nnm.append(html);
				});
				$(eNotif.children("note").get().reverse()).each(function(){
					e = $(this);
					notification_id = parseInt(e.attr('timestamp'));
					if (notification_lastitem!== null && notification_id > notification_lastitem) {
						if (getNotificationPermission()==="granted") {
							var notification = new Notification(document.title, {
											  body: decodeHtml(e.text().replace('&rarr; ','').format(e.attr('name'))),
											  icon: e.attr('photo'),
											 });
							notification['url'] = e.attr('href');
							notification.addEventListener("click", function(ev){
								window.location = ev.target.url;
							});
						}
					}

				});
				notification_lastitem = notification_id;
				localStorage.setItem("notification-lastitem", notification_lastitem)

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
				$.jGrowl(text, { sticky: true, theme: 'notice' });
			});
			eSysmsg.children("info").each(function(){
				text = $(this).text();
				$.jGrowl(text, { sticky: false, theme: 'info', life: 5000 });
			});

			/* update the js scrollbars */
			$('#nav-notifications-menu').perfectScrollbar('update');

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

		// Set an event listener for infinite scroll
		if(typeof infinite_scroll !== 'undefined') {
			$(window).scroll(function(e){
				if ($(document).height() != $(window).height()) {
					// First method that is expected to work - but has problems with Chrome
					if ($(window).scrollTop() > ($(document).height() - $(window).height() * 1.5))
						loadScrollContent();
				} else {
					// This method works with Chrome - but seems to be much slower in Firefox
					if ($(window).scrollTop() > (($("section").height() + $("header").height() + $("footer").height()) - $(window).height() * 1.5))
						loadScrollContent();
				}
			});
		}


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
					if($('#live-display').length)     { src = 'display'; liveUpdate(); }
/*					if($('#live-display').length) {
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
		if((src == null) || (stopped) || (! profile_uid)) { $('.like-rotator').hide(); return; }
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

		if ($(document).scrollTop() == 0)
			force_update = true;

		var udargs = ((netargs.length) ? '/' + netargs : '');
		var update_url = 'update_' + src + udargs + '&p=' + profile_uid + '&page=' + profile_page + '&force=' + ((force_update) ? 1 : 0);

		$.get(update_url,function(data) {
			in_progress = false;
			force_update = false;
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
				prev = ident;
			});

			// reset vars for inserting individual items

			/*			prev = 'live-' + src;

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
			});
			*/
			$('.like-rotator').hide();
			if(commentBusy) {
				commentBusy = false;
				$('body').css('cursor', 'auto');
			}
			/* autocomplete @nicknames */
			$(".comment-edit-form  textarea").editor_autocomplete(baseurl+"/acl");
			/* autocomplete bbcode */
			$(".comment-edit-form  textarea").bbco_autocomplete('bbcode');

			// setup videos, since VideoJS won't take care of any loaded via AJAX
			if(typeof videojs != 'undefined') videojs.autoSetup();
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
		$.get('like/' + ident.toString() + '?verb=' + verb, NavUpdate );
		liking = 1;
		force_update = true;
	}

	function dosubthread(ident) {
		unpause();
		$('#like-rotator-' + ident.toString()).show();
		$.get('subthread/' + ident.toString(), NavUpdate );
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

	function doignore(ident) {
		ident = ident.toString();
		$('#like-rotator-' + ident).show();
		$.get('ignored/' + ident, function(data) {
			if(data.match(/1/)) {
				$('#ignored-' + ident).addClass('ignored');
				$('#ignored-' + ident).removeClass('unignored');
				$('#ignore-' + ident).addClass('hidden');
				$('#unignore-' + ident).removeClass('hidden');
			}
			else {
				$('#ignored-' + ident).addClass('unignored');
				$('#ignored-' + ident).removeClass('ignored');
				$('#ignore-' + ident).removeClass('hidden');
				$('#unignore-' + ident).addClass('hidden');
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
					force_update = true;
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
		}
	}



	function preview_post() {
		$("#jot-preview").val("1");
		$("#jot-preview-content").show();
		tinyMCE.triggerSave();
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

	// load more network content (used for infinite scroll)
	function loadScrollContent() {
		if (lockLoadContent) return;
		lockLoadContent = true;

		$("#scroll-loader").fadeIn('normal');

		// the page number to load is one higher than the actual
		// page number
		infinite_scroll.pageno+=1;

		console.log('Loading page ' + infinite_scroll.pageno);

		// get the raw content from the next page and insert this content
		// right before "#conversation-end"
		$.get('network?mode=raw' + infinite_scroll.reload_uri + '&page=' + infinite_scroll.pageno, function(data) {
			$("#scroll-loader").hide();
			if ($(data).length > 0) {
				$(data).insertBefore('#conversation-end');
				lockLoadContent = false;
			} else {
				$("#scroll-end").fadeIn('normal');
			}
		});
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
		force_update = true;
	});
}


// code from http://www.tinymce.com/wiki.php/How-to_implement_a_custom_file_browser
function fcFileBrowser (field_name, url, type, win) {
    /* TODO: If you work with sessions in PHP and your client doesn't accept cookies you might need to carry
       the session name and session ID in the request string (can look like this: "?PHPSESSID=88p0n70s9dsknra96qhuk6etm5").
       These lines of code extract the necessary parameters and add them back to the filebrowser URL again. */


    var cmsURL = baseurl+"/fbrowser/"+type+"/";

    tinyMCE.activeEditor.windowManager.open({
        file : cmsURL,
        title : 'File Browser',
        width : 420,  // Your dimensions may differ - toy around with them!
        height : 400,
        resizable : "yes",
        inline : "yes",  // This parameter only has an effect if you use the inlinepopups plugin!
        close_previous : "no"
    }, {
        window : win,
        input : field_name
    });
    return false;
  }

function setupFieldRichtext(){
	tinyMCE.init({
		theme : "advanced",
		mode : "specific_textareas",
		editor_selector: "fieldRichtext",
		plugins : "bbcode,paste, inlinepopups",
		theme_advanced_buttons1 : "bold,italic,underline,undo,redo,link,unlink,image,forecolor,formatselect,code",
		theme_advanced_buttons2 : "",
		theme_advanced_buttons3 : "",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "center",
		theme_advanced_blockformats : "blockquote,code",
		theme_advanced_resizing : true,
		paste_text_sticky : true,
		entity_encoding : "raw",
		add_unload_trigger : false,
		remove_linebreaks : false,
		//force_p_newlines : false,
		//force_br_newlines : true,
		forced_root_block : 'div',
		convert_urls: false,
		content_css: baseurl+"/view/custom_tinymce.css",
		theme_advanced_path : false,
		file_browser_callback : "fcFileBrowser",
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

// notification permission settings in localstorage
// set by settings page
function getNotificationPermission() {
	if (window["Notification"] === undefined) {
		return null;
	}
    if (Notification.permission === 'granted') {
        var val = localStorage.getItem('notification-permissions');
		if (val === null) return 'denied';
		return val;
    } else {
        return Notification.permission;
    }
}

/**
 * Show a dialog loaded from an url
 * By defaults this load the url in an iframe in colorbox
 * Themes can overwrite `show()` function to personalize it
 */
var Dialog = {
	/**
	 * Show the dialog
	 *
	 * @param string url
	 * @return object colorbox
	 */
	show : function (url) {
		var size = Dialog._get_size();
		return $.colorbox({href: url, iframe:true,innerWidth: size.width+'px',innerHeight: size.height+'px'})
	},

	/**
	 * Show the Image browser dialog
	 *
	 * @param string name
	 * @param string id (optional)
	 * @return object
	 *
	 * The name will be used to build the event name
	 * fired by image browser dialog when the user select
	 * an image. The optional id will be passed as argument
	 * to the event handler
	 */
	doImageBrowser : function (name, id) {
		var url = Dialog._get_url("image",name,id);
		return Dialog.show(url);
	},

	/**
	 * Show the File browser dialog
	 *
	 * @param string name
	 * @param string id (optional)
	 * @return object
	 *
	 * The name will be used to build the event name
	 * fired by file browser dialog when the user select
	 * a file. The optional id will be passed as argument
	 * to the event handler
	 */
	doFileBrowser : function (name, id) {
		var url = Dialog._get_url("file",name,id);
		return Dialog.show(url);
	},

	_get_url : function(type, name, id) {
		var hash = name;
		if (id !== undefined) hash = hash + "-" + id;
		return baseurl + "/fbrowser/"+type+"/?mode=minimal#"+hash;
	},

	_get_size: function() {
		return {
			width: window.innerWidth-50,
			height: window.innerHeight-100
		};
	}
}
