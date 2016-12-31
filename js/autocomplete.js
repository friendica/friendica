/**
 * @brief Friendica people autocomplete
 *
 * require jQuery, jquery.textcomplete
 * 
 * for further documentation look at:
 * http://yuku-t.com/jquery-textcomplete/
 * 
 * https://github.com/yuku-t/jquery-textcomplete/blob/master/doc/how_to_use.md
 */


function contact_search(term, callback, backend_url, type, mode) {

	// Check if there is a conversation id to include the unkonwn contacts of the conversation
	var conv_id = document.activeElement.id.match(/\d+$/);

	// Check if there is a cached result that contains the same information we would get with a full server-side search
	var bt = backend_url+type;
	if(!(bt in contact_search.cache)) contact_search.cache[bt] = {};

	var lterm = term.toLowerCase(); // Ignore case
	for(var t in contact_search.cache[bt]) {
		if(lterm.indexOf(t) >= 0) { // A more broad search has been performed already, so use those results
			// Filter old results locally
			var matching = contact_search.cache[bt][t].filter(function (x) { return (x.name.toLowerCase().indexOf(lterm) >= 0 || (typeof x.nick !== 'undefined' && x.nick.toLowerCase().indexOf(lterm) >= 0)); }); // Need to check that nick exists because groups don't have one
			matching.unshift({forum:false, text: term, replace: term});
			setTimeout(function() { callback(matching); } , 1); // Use "pseudo-thread" to avoid some problems
			return;
		}
	}

	var postdata = {
		start:0,
		count:100,
		search:term,
		type:type,
	};

	if(conv_id !== null)
		postdata['conversation'] = conv_id[0];

	if(mode !== null)
		postdata['smode'] = mode;


	$.ajax({
		type:'POST',
		url: backend_url,
		data: postdata,
		dataType: 'json',
		success: function(data){
			// Cache results if we got them all (more information would not improve results)
			// data.count represents the maximum number of items
			if(data.items.length -1 < data.count) {
				contact_search.cache[bt][lterm] = data.items;
			}
			var items = data.items.slice(0);
			items.unshift({taggable:false, text: term, replace: term});
			callback(items);
		},
	}).fail(function () {callback([]); }); // Callback must be invoked even if something went wrong.
}
contact_search.cache = {};


function contact_format(item) {
	// Show contact information if not explicitly told to show something else
	if(typeof item.text === 'undefined') {
		var desc = ((item.label) ? item.nick + ' ' + item.label : item.nick);
		var forum = ((item.forum) ? 'forum' : '');
		if(typeof desc === 'undefined') desc = '';
		if(desc) desc = ' ('+desc+')';
		return "<div class='{0}' title='{4}'><img class='acpopup-img' src='{1}'><span class='acpopup-contactname'>{2}</span><span class='acpopup-sub-text'>{3}</span><div class='clear'></div></div>".format(forum, item.photo, item.name, desc, item.link);
	}
	else
		return "<div>" + item.text + "</div>";
}

function editor_replace(item) {
	if(typeof item.replace !== 'undefined') {
		return '$1$2' + item.replace;
	}

	// $2 ensures that prefix (@,@!) is preserved
	var id = item.id;

	// don't add the id if it is empty (the id empty eg. if there are unknow contacts in thread)
	if(id.length < 1)
		return '$1$2' + item.nick.replace(' ', '') + ' ';

	// 16 chars of hash should be enough. Full hash could be used if it can be done in a visually appealing way.
	// 16 chars is also the minimum length in the backend (otherwise it's interpreted as a local id).
	if(id.length > 16) 
		id = item.id.substring(0,16);

	return '$1$2' + item.nick.replace(' ', '') + '+' + id + ' ';
}

function basic_replace(item) {
	if(typeof item.replace !== 'undefined')
		return '$1'+item.replace;

	return '$1'+item.name+' ';
}

function webbie_replace(item) {
	if(typeof item.replace !== 'undefined')
		return '$1'+item.replace;

	return '$1'+item.nick+' ';
}

function trim_replace(item) {
	if(typeof item.replace !== 'undefined')
		return '$1'+item.replace;

	return '$1'+item.name;
}


function submit_form(e) {
	$(e).parents('form').submit();
}

function getWord(text, caretPos) {
	var index = text.indexOf(caretPos);
	var postText = text.substring(caretPos, caretPos+8);
	if ((postText.indexOf("[/list]") > 0) || postText.indexOf("[/ul]") > 0 || postText.indexOf("[/ol]") > 0) {
		return postText;
	}
}

function getCaretPosition(ctrl) {
	var CaretPos = 0;   // IE Support
	if (document.selection) {
		ctrl.focus();
		var Sel = document.selection.createRange();
		Sel.moveStart('character', -ctrl.value.length);
		CaretPos = Sel.text.length;
	}
	// Firefox support
	else if (ctrl.selectionStart || ctrl.selectionStart == '0')
		CaretPos = ctrl.selectionStart;
	return (CaretPos);
}

function setCaretPosition(ctrl, pos){
	if(ctrl.setSelectionRange) {
		ctrl.focus();
		ctrl.setSelectionRange(pos,pos);
	}
	else if (ctrl.createTextRange) {
		var range = ctrl.createTextRange();
		range.collapse(true);
		range.moveEnd('character', pos);
		range.moveStart('character', pos);
		range.select();
	}
}

function listNewLineAutocomplete(id) {
	var text = document.getElementById(id);
	var caretPos = getCaretPosition(text)
	var word = getWord(text.value, caretPos);
	if (word != null) {
		var textBefore = text.value.substring(0, caretPos);
		var textAfter  = text.value.substring(caretPos, text.length);
		$('#' + id).val(textBefore + '\r\n[*] ' + textAfter);
		setCaretPosition(text, caretPos + 5);
		return true;
	}
	else {
		return false;
	}
}

function string2bb(element) {
	if(element == 'bold') return 'b';
	else if(element == 'italic') return 'i';
	else if(element == 'underline') return 'u';
	else if(element == 'overline') return 'o';
	else if(element == 'strike') return 's';
	else return element;
}

/**
 * jQuery plugin 'editor_autocomplete'
 */
(function( $ ) {
	$.fn.editor_autocomplete = function(backend_url) {

		// Autocomplete contacts
		contacts = {
			match: /(^|\s)(@\!*)([^ \n]+)$/,
			index: 3,
			search: function(term, callback) { contact_search(term, callback, backend_url, 'c'); },
			replace: editor_replace,
			template: contact_format,
		};

		// Autocomplete smilies e.g. ":like"
		smilies = {
			match: /(^|\s)(:[a-z]{2,})$/,
			index: 2,
			search: function(term, callback) { $.getJSON('smilies/json').done(function(data) { callback($.map(data, function(entry) { return entry.text.indexOf(term) === 0 ? entry : null; })); }); },
			template: function(item) { return item.icon + ' ' + item.text; },
			replace: function(item) { return "$1" + item.text + ' '; },
		};

		this.attr('autocomplete','off');
		this.textcomplete([contacts,smilies], {className:'acpopup', zIndex:10000});
	};
})( jQuery );

/**
 * jQuery plugin 'search_autocomplete'
 */
(function( $ ) {
	$.fn.search_autocomplete = function(backend_url) {
		// Autocomplete contacts
		contacts = {
			match: /(^@)([^\n]{2,})$/,
			index: 2,
			search: function(term, callback) { contact_search(term, callback, backend_url, 'x', 'contact'); },
			replace: webbie_replace,
			template: contact_format,
		};

		// Autocomplete forum accounts
		community = {
			match: /(^!)([^\n]{2,})$/,
			index: 2,
			search: function(term, callback) { contact_search(term, callback, backend_url, 'x', 'community'); },
			replace: webbie_replace,
			template: contact_format,
		};
		this.attr('autocomplete', 'off');
		var a = this.textcomplete([contacts, community], {className:'acpopup', maxCount:100, zIndex: 10000, appendTo:'nav'});
		a.on('textComplete:select', function(e, value, strategy) { submit_form(this); });
	};
})( jQuery );

(function( $ ) {
	$.fn.contact_autocomplete = function(backend_url, typ, autosubmit, onselect) {
		if(typeof typ === 'undefined') typ = '';
		if(typeof autosubmit === 'undefined') autosubmit = false;

		// Autocomplete contacts
		contacts = {
			match: /(^)([^\n]+)$/,
			index: 2,
			search: function(term, callback) { contact_search(term, callback, backend_url, typ); },
			replace: basic_replace,
			template: contact_format,
		};

		this.attr('autocomplete','off');
		var a = this.textcomplete([contacts], {className:'acpopup', zIndex:10000});

		if(autosubmit)
			a.on('textComplete:select', function(e,value,strategy) { submit_form(this); });

		if(typeof onselect !== 'undefined')
			a.on('textComplete:select', function(e, value, strategy) { onselect(value); });
	};
})( jQuery );


(function( $ ) {
	$.fn.name_autocomplete = function(backend_url, typ, autosubmit, onselect) {
		if(typeof typ === 'undefined') typ = '';
		if(typeof autosubmit === 'undefined') autosubmit = false;

		// Autocomplete contacts
		names = {
			match: /(^)([^\n]+)$/,
			index: 2,
			search: function(term, callback) { contact_search(term, callback, backend_url, typ); },
			replace: trim_replace,
			template: contact_format,
		};

		this.attr('autocomplete','off');
		var a = this.textcomplete([names], {className:'acpopup', zIndex:10000});

		if(autosubmit)
			a.on('textComplete:select', function(e,value,strategy) { submit_form(this); });

		if(typeof onselect !== 'undefined')
			a.on('textComplete:select', function(e, value, strategy) { onselect(value); });
	};
})( jQuery );

(function( $ ) {
	$.fn.bbco_autocomplete = function(type) {

		if(type=='bbcode') {
			var open_close_elements = ['bold', 'italic', 'underline', 'overline', 'strike', 'quote', 'code', 'spoiler', 'map', 'img', 'url', 'audio', 'video', 'embed', 'youtube', 'vimeo', 'list', 'ul', 'ol', 'li', 'table', 'tr', 'th', 'td', 'center', 'color', 'font', 'size', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'nobb', 'noparse', 'pre', 'abstract'];
			var open_elements = ['*', 'hr'];

			var elements = open_close_elements.concat(open_elements);
		}

		bbco = {
			match: /\[(\w*\**)$/,
			search: function (term, callback) {
				callback($.map(elements, function (element) {
					return element.indexOf(term) === 0 ? element : null;
				}));
			},
			index: 1,
			replace: function (element) {
				element = string2bb(element);
				if(open_elements.indexOf(element) < 0) {
					if(element === 'list' || element === 'ol' || element === 'ul') {
						return ['\[' + element + '\]' + '\n\[*\] ', '\n\[/' + element + '\]'];
					}
					else if(element === 'table') {
						return ['\[' + element + '\]' + '\n\[tr\]', '\[/tr\]\n\[/' + element + '\]'];
					}
					else {
						return ['\[' + element + '\]', '\[/' + element + '\]'];
					}
				}
				else {
					return '\[' + element + '\] ';
				}
			}
		};

		this.attr('autocomplete','off');
		var a = this.textcomplete([bbco], {className:'acpopup', zIndex:10000});

		a.on('textComplete:select', function(e, value, strategy) { value; });

		a.keypress(function(e){
			if (e.keyCode == 13) {
				var x = listNewLineAutocomplete(this.id);
				if(x) {
					e.stopImmediatePropagation();
					e.preventDefault();
				}
			}
		});
	};
})( jQuery );

/**
 * Friendica people autocomplete legacy
 * code which is needed for tinymce
 *
 * require jQuery, jquery.textareas
 */

function ACPopup(elm,backend_url){
	this.idsel=-1;
	this.element = elm;
	this.searchText="";
	this.ready=true;
	this.kp_timer = false;
	this.url = backend_url;

	this.conversation_id = null;
	var conv_id = this.element.id.match(/\d+$/);
	if (conv_id) this.conversation_id = conv_id[0];
	console.log("ACPopup elm id",this.element.id,"conversation",this.conversation_id);

	var w = 530;
	var h = 130;


	if(tinyMCE.activeEditor == null) {
		style = $(elm).offset();
		w = $(elm).width();
		h = $(elm).height();
	}
	else {
		// I can't find an "official" way to get the element who get all
		// this fraking thing that is tinyMCE.
		// This code will broke again at some point...
		var container = $(tinyMCE.activeEditor.getContainer()).find("table");
		style = $(container).offset();
		w = $(container).width();
		h = $(container).height();
	}

	style.top=style.top+h;
	style.width = w;
	style.position = 'absolute';
	/*	style['max-height'] = '150px';
		style.border = '1px solid red';
		style.background = '#cccccc';

		style.overflow = 'auto';
		style['z-index'] = '100000';
	*/
	style.display = 'none';

	this.cont = $("<div class='acpopup-mce'></div>");
	this.cont.css(style);

	$("body").append(this.cont);
    }

ACPopup.prototype.close = function(){
	$(this.cont).remove();
	this.ready=false;
}
ACPopup.prototype.search = function(text){
	var that = this;
	this.searchText=text;
	if (this.kp_timer) clearTimeout(this.kp_timer);
	this.kp_timer = setTimeout( function(){that._search();}, 500);
}

ACPopup.prototype._search = function(){
	console.log("_search");
	var that = this;
	var postdata = {
		start:0,
		count:100,
		search:this.searchText,
		type:'c',
		conversation: this.conversation_id,
	}

	$.ajax({
		type:'POST',
		url: this.url,
		data: postdata,
		dataType: 'json',
		success:function(data){
			that.cont.html("");
			if (data.tot>0){
				that.cont.show();
				$(data.items).each(function(){
					var html = "<img src='{0}' height='16px' width='16px'>{1} ({2})".format(this.photo, this.name, this.nick);
					var nick = this.nick.replace(' ','');
					if (this.id!=='')  nick += '+' + this.id;
					that.add(html, nick + ' - ' + this.link);
				});
			} else {
				that.cont.hide();
			}
		}
	});

}

ACPopup.prototype.add = function(label, value){
	var that=this;
	var elm = $("<div class='acpopupitem' title='"+value+"'>"+label+"</div>");
	elm.click(function(e){
		t = $(this).attr('title').replace(new RegExp(' \- .*'),'');
		if(typeof(that.element.container) === "undefined") {
			el=$(that.element);
			sel = el.getSelection();
			sel.start = sel.start- that.searchText.length;
			el.setSelection(sel.start,sel.end).replaceSelectedText(t+' ').collapseSelection(false);
			that.close();
		}
		else {
			txt = tinyMCE.activeEditor.getContent();
			//			alert(that.searchText + ':' + t);
			newtxt = txt.replace('@' + that.searchText,'@' + t +' ');
			tinyMCE.activeEditor.setContent(newtxt);
			tinyMCE.activeEditor.focus();
			that.close();
		}
	});
	$(this.cont).append(elm);
}

ACPopup.prototype.onkey = function(event){
	if (event.keyCode == '13') {
		if(this.idsel>-1) {
			this.cont.children()[this.idsel].click();
			event.preventDefault();
		}
		else
			this.close();
	}
	if (event.keyCode == '38') { //cursor up
		cmax = this.cont.children().size()-1;
		this.idsel--;
		if (this.idsel<0) this.idsel=cmax;
		event.preventDefault();
	}
	if (event.keyCode == '40' || event.keyCode == '9') { //cursor down
		cmax = this.cont.children().size()-1;
		this.idsel++;
		if (this.idsel>cmax) this.idsel=0;
		event.preventDefault();
	}

	if (event.keyCode == '38' || event.keyCode == '40' || event.keyCode == '9') {
		this.cont.children().removeClass('selected');
		$(this.cont.children()[this.idsel]).addClass('selected');
	}

	if (event.keyCode == '27') { //ESC
		this.close();
	}
}

