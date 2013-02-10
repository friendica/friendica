
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

		if($j(listID).is(":visible")) {
			$j(listID).hide();
			$j(listID+"-wrapper").show();
			alert($j(listID+"-wrapper").attr("id"));
		}
		else {
			$j(listID).show();
			$j(listID+"-wrapper").hide();
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
	var last_popup_menu = null;
	var last_popup_button = null;

	$j(function() {
		$j.ajaxSetup({cache: false});

		msie = $j.browser.msie ;
		
		collapseHeight();

		/* setup tooltips *//*
		$j("a,.tt").each(function(){
			var e = $j(this);
			var pos="bottom";
			if (e.hasClass("tttop")) pos="top";
			if (e.hasClass("ttbottom")) pos="bottom";
			if (e.hasClass("ttleft")) pos="left";
			if (e.hasClass("ttright")) pos="right";
			e.tipTip({defaultPosition: pos, edgeOffset: 8});
		});*/
		
		
		
		/* setup onoff widgets */
		$j(".onoff input").each(function(){
			val = $j(this).val();
			id = $j(this).attr("id");
			$j("#"+id+"_onoff ."+ (val==0?"on":"off")).addClass("hidden");
			
		});
		$j(".onoff > a").click(function(event){
			event.preventDefault();	
			var input = $j(this).siblings("input");
			var val = 1-input.val();
			var id = input.attr("id");
			$j("#"+id+"_onoff ."+ (val==0?"on":"off")).addClass("hidden");
			$j("#"+id+"_onoff ."+ (val==1?"on":"off")).removeClass("hidden");
			input.val(val);
			//console.log(id);
		});
		
		/* setup field_richtext */
		/*setupFieldRichtext();*/

		/* popup menus */
		function close_last_popup_menu(e) {

 			if( last_popup_menu ) {
				if( '#' + last_popup_menu.attr('id') !== $j(e.target).attr('rel')) {
		 			last_popup_menu.hide();
					if (last_popup_menu.attr('id') == "nav-notifications-menu" ) $j('.main-container').show();
		 			last_popup_button.removeClass("selected");
		 			last_popup_menu = null;
		 			last_popup_button = null;
				}
			}
		}
		$j('img[rel^=#]').click(function(e){

			close_last_popup_menu(e);
			menu = $j( $j(this).attr('rel') );
			e.preventDefault();
			e.stopPropagation();

			if (menu.attr('popup')=="false") return false;

//			$j(this).parent().toggleClass("selected");
//			menu.toggle();

			if (menu.css("display") == "none") {
				$j(this).parent().addClass("selected");
				menu.show();
				if (menu.attr('id') == "nav-notifications-menu" ) $j('.main-container').hide();
				last_popup_menu = menu;
				last_popup_button = $j(this).parent();
			} else {
				$j(this).parent().removeClass("selected");
				menu.hide();
				if (menu.attr('id') == "nav-notifications-menu" ) $j('.main-container').show();
				last_popup_menu = null;
				last_popup_button = null;
			}
			return false;
		});
		$j('html').click(function(e) {
			close_last_popup_menu(e);
		});
		
		// fancyboxes
		/*$j("a.popupbox").colorbox({
			'inline' : true,
			'transition' : 'none'
		});*/
		

		/* notifications template */
		var notifications_tpl= unescape($j("#nav-notifications-template[rel=template]").html());
		var notifications_all = unescape($j('<div>').append( $j("#nav-notifications-see-all").clone() ).html()); //outerHtml hack
		var notifications_mark = unescape($j('<div>').append( $j("#nav-notifications-mark-all").clone() ).html()); //outerHtml hack
		var notifications_empty = unescape($j("#nav-notifications-menu").html());
		
		/* nav update event  */
		$j('nav').bind('nav-update', function(e,data){;
			var invalid = $j(data).find('invalid').text();
			if(invalid == 1) { window.location.href=window.location.href }

			var net = $j(data).find('net').text();
			if(net == 0) { net = ''; $j('#net-update').removeClass('show') } else { $j('#net-update').addClass('show') }
			$j('#net-update').html(net);

			var home = $j(data).find('home').text();
			if(home == 0) { home = '';  $j('#home-update').removeClass('show') } else { $j('#home-update').addClass('show') }
			$j('#home-update').html(home);
			
			var intro = $j(data).find('intro').text();
			if(intro == 0) { intro = '';  $j('#intro-update').removeClass('show') } else { $j('#intro-update').addClass('show') }
			$j('#intro-update').html(intro);

			var mail = $j(data).find('mail').text();
			if(mail == 0) { mail = '';  $j('#mail-update').removeClass('show') } else { $j('#mail-update').addClass('show') }
			$j('#mail-update').html(mail);
			
			var intro = $j(data).find('intro').text();
			if(intro == 0) { intro = '';  $j('#intro-update-li').removeClass('show') } else { $j('#intro-update-li').addClass('show') }
			$j('#intro-update-li').html(intro);

			var mail = $j(data).find('mail').text();
			if(mail == 0) { mail = '';  $j('#mail-update-li').removeClass('show') } else { $j('#mail-update-li').addClass('show') }
			$j('#mail-update-li').html(mail);

			var eNotif = $j(data).find('notif')
			
			if (eNotif.children("note").length==0){
				$j("#nav-notifications-menu").html(notifications_empty);
			} else {
				nnm = $j("#nav-notifications-menu");
				nnm.html(notifications_all + notifications_mark);
				//nnm.attr('popup','true');
				eNotif.children("note").each(function(){
					e = $j(this);
					text = e.text().format("<span class='contactname'>"+e.attr('name')+"</span>");
					html = notifications_tpl.format(e.attr('href'),e.attr('photo'), text, e.attr('date'), e.attr('seen'));
					nnm.append(html);
				});

				$j("img[data-src]", nnm).each(function(i, el){
					// Add src attribute for images with a data-src attribute
					$j(el).attr('src', $j(el).data("src"));
				});
			}
			notif = eNotif.attr('count');
			if (notif>0){
				$j("#nav-notifications-linkmenu").addClass("on");
			} else {
				$j("#nav-notifications-linkmenu").removeClass("on");
			}
			if(notif == 0) { notif = ''; $j('#notify-update').removeClass('show') } else { $j('#notify-update').addClass('show') }
			$j('#notify-update').html(notif);
			
			var eSysmsg = $j(data).find('sysmsgs');
			eSysmsg.children("notice").each(function(){
				text = $j(this).text();
				$j.jGrowl(text, { sticky: false, theme: 'notice', life: 1000 });
			});
			eSysmsg.children("info").each(function(){
				text = $j(this).text();
				$j.jGrowl(text, { sticky: false, theme: 'info', life: 1000 });
			});
			
		});
		
		
 		NavUpdate(); 
		// Allow folks to stop the ajax page updates with the pause/break key
/*		$j(document).keydown(function(event) {
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
					$j('#pause').html('<img src="images/pause.gif" alt="pause" style="border: 1px solid black;" />');
				} else {
					unpause();
				}
			} else {
				if (!totStopped) {
					unpause();
				}
			}
		});*/
		
		
	});

	function NavUpdate() {

		if(! stopped) {
			var pingCmd = 'ping' + ((localUser != 0) ? '?f=&uid=' + localUser : '');
			$j.get(pingCmd,function(data) {
				$j(data).find('result').each(function() {
					// send nav-update event
					$j('nav').trigger('nav-update', this);
					
					
					// start live update

					

					if($j('#live-network').length)   { src = 'network'; liveUpdate(); }
					if($j('#live-profile').length)   { src = 'profile'; liveUpdate(); }
					if($j('#live-community').length) { src = 'community'; liveUpdate(); }
					if($j('#live-notes').length)     { src = 'notes'; liveUpdate(); }
					if($j('#live-display').length) { src = 'display'; liveUpdate(); }
					/*if($j('#live-display').length) {
						if(liking) {
							liking = 0;
							window.location.href=window.location.href 
						}
					}*/
					if($j('#live-photos').length) {
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
		if((src == null) || (stopped) || (typeof profile_uid == 'undefined') || (! profile_uid)) { $j('.like-rotator').hide(); return; }
		if(($j('.comment-edit-text-full').length) || (in_progress)) {
			if(livetime) {
				clearTimeout(livetime);
			}
			livetime = setTimeout(liveUpdate, 10000);
			return;
		}
		if(livetime != null)
			livetime = null;

		prev = 'live-' + src;

		in_progress = true;
		var udargs = ((netargs.length) ? '/' + netargs : '');
		var update_url = 'update_' + src + udargs + '&p=' + profile_uid + '&page=' + profile_page + '&msie=' + ((msie) ? 1 : 0);

		$j.get(update_url,function(data) {
			in_progress = false;
			//			$j('.collapsed-comments',data).each(function() {
			//	var ident = $j(this).attr('id');
			//	var is_hidden = $j('#' + ident).is(':hidden');
			//	if($j('#' + ident).length) {
			//		$j('#' + ident).replaceWith($j(this));
			//		if(is_hidden)
			//			$j('#' + ident).hide();
			//	}
			//});

			// add a new thread

			$j('.toplevel_item',data).each(function() {
				var ident = $j(this).attr('id');

				if($j('#' + ident).length == 0 && profile_page == 1) {
					$j('img',this).each(function() {
						$j(this).attr('src',$j(this).attr('dst'));
					});
					$j('#' + prev).after($j(this));
				}
				else {
					// Find out if the hidden comments are open, so we can keep it that way
					// if a new comment has been posted
					var id = $j('.hide-comments-total', this).attr('id');
					if(typeof id != 'undefined') {
						id = id.split('-')[3];
						var commentsOpen = $j("#collapsed-comments-" + id).is(":visible");
					}

					$j('img',this).each(function() {
						$j(this).attr('src',$j(this).attr('dst'));
					});
					//vScroll = $j(document).scrollTop();
					$j('html').height($j('html').height());
					$j('#' + ident).replaceWith($j(this));

					if(typeof id != 'undefined') {
						if(commentsOpen) showHideComments(id);
					}
					$j('html').height('auto');
					//$j(document).scrollTop(vScroll);
				}
				prev = ident;
			});


			collapseHeight();

			// reset vars for inserting individual items

			/*prev = 'live-' + src;

			$j('.wall-item-outside-wrapper',data).each(function() {
				var ident = $j(this).attr('id');

				if($j('#' + ident).length == 0 && prev != 'live-' + src) {
						$j('img',this).each(function() {
							$j(this).attr('src',$j(this).attr('dst'));
						});
						$j('#' + prev).after($j(this));
				}
				else { 
					$j('#' + ident + ' ' + '.wall-item-ago').replaceWith($j(this).find('.wall-item-ago')); 
					if($j('#' + ident + ' ' + '.comment-edit-text-empty').length)
						$j('#' + ident + ' ' + '.wall-item-comment-wrapper').replaceWith($j(this).find('.wall-item-comment-wrapper'));
					$j('#' + ident + ' ' + '.hide-comments-total').replaceWith($j(this).find('.hide-comments-total'));
					$j('#' + ident + ' ' + '.wall-item-like').replaceWith($j(this).find('.wall-item-like'));
					$j('#' + ident + ' ' + '.wall-item-dislike').replaceWith($j(this).find('.wall-item-dislike'));
					$j('#' + ident + ' ' + '.my-comment-photo').each(function() {
						$j(this).attr('src',$j(this).attr('dst'));
					});
				}
				prev = ident; 
			});*/
			
			$j('.like-rotator').hide();
			if(commentBusy) {
				commentBusy = false;
				$j('body').css('cursor', 'auto');
			}
			/* autocomplete @nicknames */
			$j(".comment-edit-form  textarea").contact_autocomplete(baseurl+"/acl");
		});
	}

	function collapseHeight(elems) {
		var elemName = '.wall-item-body:not(.divmore)';
		if(typeof elems != 'undefined') {
			elemName = elems + ' ' + elemName;
		}
		$j(elemName).each(function() {
			if($j(this).height() > 350) {
				$j('html').height($j('html').height());
				$j(this).divgrow({ initialHeight: 300, showBrackets: false, speed: 0 });
				$j(this).addClass('divmore');
				$j('html').height('auto');
			}
		});
	}

/*	function imgbright(node) {
		$j(node).removeClass("drophide").addClass("drop");
	}

	function imgdull(node) {
		$j(node).removeClass("drop").addClass("drophide");
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
		$j('#like-rotator-' + ident.toString()).show();
		$j.get('like/' + ident.toString() + '?verb=' + verb, NavUpdate );
//		if(timer) clearTimeout(timer);
//		timer = setTimeout(NavUpdate,3000);
		liking = 1;
	}

	function dostar(ident) {
		ident = ident.toString();
		//$j('#like-rotator-' + ident).show();
		$j.get('starred/' + ident, function(data) {
			if(data.match(/1/)) {
				$j('#starred-' + ident).addClass('starred');
				$j('#starred-' + ident).removeClass('unstarred');
				$j('#star-' + ident).addClass('hidden');
				$j('#unstar-' + ident).removeClass('hidden');
			}
			else {			
				$j('#starred-' + ident).addClass('unstarred');
				$j('#starred-' + ident).removeClass('starred');
				$j('#star-' + ident).removeClass('hidden');
				$j('#unstar-' + ident).addClass('hidden');
			}
			//$j('#like-rotator-' + ident).hide();	
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
			$j.get('lockview/' + id, function(data) {
				$j('#panel').html(data);
				$j('#panel').css({ 'left': 10 , 'top': cursor.y + 20});
				$j('#panel').show();
			});
		}
	}

	function lockviewhide() {
		lockvisible = false;
		$j('#panel').hide();
	}

	function post_comment(id) {
		unpause();
		commentBusy = true;
		$j('body').css('cursor', 'wait');
		$j("#comment-preview-inp-" + id).val("0");
		$j.post(  
             "item",  
             $j("#comment-edit-form-" + id).serialize(),
			function(data) {
				if(data.success) {
					$j("#comment-edit-wrapper-" + id).hide();
					$j("#comment-edit-text-" + id).val('');
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
		$j("#comment-preview-inp-" + id).val("1");
		$j("#comment-edit-preview-" + id).show();
		$j.post(  
             "item",  
             $j("#comment-edit-form-" + id).serialize(),
			function(data) {
				if(data.preview) {
						
					$j("#comment-edit-preview-" + id).html(data.preview);
					$j("#comment-edit-preview-" + id + " a").click(function() { return false; });
				}
			},
			"json"  
         );  
         return true;  
	}


	function showHideComments(id) {
		if( $j("#collapsed-comments-" + id).is(":visible")) {
			$j("#collapsed-comments-" + id).hide();
			$j("#hide-comments-" + id).html(window.showMore);
		}
		else {
			$j("#collapsed-comments-" + id).show();
			$j("#hide-comments-" + id).html(window.showFewer);
			collapseHeight("#collapsed-comments-" + id);
		}
	}


	function preview_post() {
		$j("#jot-preview").val("1");
		$j("#jot-preview-content").show();
		tinyMCE.triggerSave();
		$j.post(  
			"item",  
			$j("#profile-jot-form").serialize(),
			function(data) {
				if(data.preview) {			
					$j("#jot-preview-content").html(data.preview);
					$j("#jot-preview-content" + " a").click(function() { return false; });
				}
			},
			"json"  
		);  
		$j("#jot-preview").val("0");
		return true;  
	}


	function unpause() {
		// unpause auto reloads if they are currently stopped
		totStopped = false;
		stopped = false;
	    $j('#pause').html('');
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
		$j('body .fakelink').css('cursor', 'wait');
		$j.get('group/' + gid + '/' + cid + "?t=" + sec_token, function(data) {
				$j('#group-update-wrapper').html(data);
				$j('body .fakelink').css('cursor', 'auto');				
		});
	}

	function profChangeMember(gid,cid) {
		$j('body .fakelink').css('cursor', 'wait');
		$j.get('profperm/' + gid + '/' + cid, function(data) {
				$j('#prof-update-wrapper').html(data);
				$j('body .fakelink').css('cursor', 'auto');				
		});
	}

	function contactgroupChangeMember(gid,cid) {
		$j('body').css('cursor', 'wait');
		$j.get('contactgroup/' + gid + '/' + cid, function(data) {
				$j('body').css('cursor', 'auto');
		});
	}


function checkboxhighlight(box) {
  if($j(box).is(':checked')) {
	$j(box).addClass('checkeditem');
  }
  else {
	$j(box).removeClass('checkeditem');
  }
}

function notifyMarkAll() {
	$j.get('notify/mark/all', function(data) {
		if(timer) clearTimeout(timer);
		timer = setTimeout(NavUpdate,1000);
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
	theme = $j(elm).val();
	$j.getJSON('pretheme?f=&theme=' + theme,function(data) {
			$j('#theme-preview').html('<div id="theme-desc">' + data.desc + '</div><div id="theme-version">' + data.version + '</div><div id="theme-credits">' + data.credits + '</div>');
	});

}
