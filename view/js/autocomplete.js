// @license magnet:?xt=urn:btih:d3d9a9a6595521f9666a5e94cc830dab83b65699&dn=expat.txt Expat
/**
 * Friendica people autocomplete
 *
 * require jQuery, jquery.textcomplete
 *
 * for further documentation look at:
 * http://yuku-t.com/jquery-textcomplete/
 *
 * https://github.com/yuku-t/jquery-textcomplete/blob/master/doc/how_to_use.md
 */


function contact_search(term, callback, backend_url, type, mode) {

	// Check if there is a conversation id to include the unknown contacts of the conversation
	var conv_id = document.activeElement.id.match(/\d+$/);

	// Check if there is a cached result that contains the same information we would get with a full server-side search
	var bt = backend_url+type;
	if(!(bt in contact_search.cache)) contact_search.cache[bt] = {};

	var lterm = term.toLowerCase(); // Ignore case
	for(var t in contact_search.cache[bt]) {
		if(lterm.indexOf(t) >= 0) { // A more broad search has been performed already, so use those results
			// Filter old results locally
			var matching = contact_search.cache[bt][t].filter(function (x) { return (x.name.toLowerCase().indexOf(lterm) >= 0 || (typeof x.nick !== 'undefined' && x.nick.toLowerCase().indexOf(lterm) >= 0)); }); // Need to check that nick exists because circles don't have one
			matching.unshift({group: false, text: term, replace: term});
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
		var group = ((item.group) ? 'group' : '');
		if(typeof desc === 'undefined') desc = '';
		if(desc) desc = ' ('+desc+')';
		return "<div class='{0}' title='{4}'><img class='acpopup-img' src='{1}'><span class='acpopup-contactname'>{2}</span><span class='acpopup-sub-text'>{3}</span><div class='clear'></div></div>".format(group, item.photo, item.name, desc, item.link);
	}
	else
		return "<div>" + item.text + "</div>";
}

function tag_format(item) {
	return "<div class='dropdown-item'>" + '#' + item.text + "</div>";
}

function editor_replace(item) {
	if (typeof item.replace !== 'undefined') {
		return '$1$2' + item.replace;
	}

	if (typeof item.addr !== 'undefined') {
		return '$1$2' + item.addr + ' ';
	}

	// $2 ensures that prefix (@,@!) is preserved
	var id = item.id;

	// don't add the id if it is empty (the id empty eg. if there are unknow contacts in thread)
	if (id.length < 1) {
		return '$1$2' + item.nick.replace(' ', '') + ' ';
	}
	// 16 chars of hash should be enough. Full hash could be used if it can be done in a visually appealing way.
	// 16 chars is also the minimum length in the backend (otherwise it's interpreted as a local id).
	if (id.length > 16) {
		id = item.id.substring(0,16);
	}
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
		$('#' + id).val(textBefore + '\r\n[*] ' + textAfter).trigger('change');
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
	let textcompleteObjects = [];

	// jQuery wrapper for yuku/old-textcomplete
	// uses a local object directory to avoid recreating Textcomplete objects
	$.fn.textcomplete = function (strategies, options) {
		return this.each(function () {
			let $this = $(this);
			if (!($this.data('textcompleteId') in textcompleteObjects)) {
				let editor = new Textcomplete.editors.Textarea($this.get(0));

				$this.data('textcompleteId', textcompleteObjects.length);
				textcompleteObjects.push(new Textcomplete(editor, options));
			}

			textcompleteObjects[$this.data('textcompleteId')].register(strategies);
		});
	};

	/**
	 * This function should be called immediately after $.textcomplete() to prevent the escape key press to propagate
	 * after the autocompletion dropdown has closed.
	 * This avoids the input textarea to lose focus, the modal window to close, etc... when the expected behavior is
	 * to just close the autocomplete dropdown.
	 *
	 * The custom event listener name allows removing this specific event listener, the "real" event this listens to
	 * is the part before the first dot.
	 *
	 * @returns {*}
	 */
	$.fn.fixTextcompleteEscape = function () {
		if (this.data('textcompleteEscapeFixed')) {
			return this;
		}

		this.data('textcompleteEscapeFixed', true);

		return this.on({
			'textComplete:show': function (e) {
				$(this).on('keydown.friendica.escape', function (e) {
					if (e.key === 'Escape') {
						e.stopPropagation();
					}
				});
			},
			'textComplete:hide': function (e) {
				$(this).off('keydown.friendica.escape');
			},
		});
	}

	$.fn.editor_autocomplete = function(backend_url) {

		// Autocomplete contacts
		contacts = {
			match: /(^|\s)(@\!*)([^ \n]+)$/,
			index: 3,
			search: function(term, callback) { contact_search(term, callback, backend_url, 'c'); },
			replace: editor_replace,
			template: contact_format,
		};

		// Autocomplete groups
		groups = {
			match: /(^|\s)(!\!*)([^ \n]+)$/,
			index: 3,
			search: function(term, callback) { contact_search(term, callback, backend_url, 'f'); },
			replace: editor_replace,
			template: contact_format,
		};

		// Autocomplete hashtags
		tags = {
			match: /(^|\s)(\#)([^ \n]{2,})$/,
			index: 3,
			search: function(term, callback) {
				$.getJSON(baseurl + '/hashtag/' + '?t=' + term)
				.done(function(data) {
					callback($.map(data, function(entry) {
						// .toLowerCase() enables case-insensitive search
						return entry.text.toLowerCase().indexOf(term.toLowerCase()) === 0 ? entry : null;
					}));
				});
			},
			replace: function(item) { return "$1$2" + item.text + ' '; },
			template: tag_format
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
		this.textcomplete([contacts, groups, smilies, tags], {dropdown: {className:'acpopup'}});
		this.fixTextcompleteEscape();

		return this;
	};

	$.fn.search_autocomplete = function(backend_url) {
		// Autocomplete contacts
		contacts = {
			match: /(^@)([^\n]{2,})$/,
			index: 2,
			search: function(term, callback) { contact_search(term, callback, backend_url, 'x', 'contact'); },
			replace: webbie_replace,
			template: contact_format,
		};

		// Autocomplete group accounts
		community = {
			match: /(^!)([^\n]{2,})$/,
			index: 2,
			search: function(term, callback) { contact_search(term, callback, backend_url, 'x', 'community'); },
			replace: webbie_replace,
			template: contact_format,
		};

		// Autocomplete hashtags
		tags = {
			match: /(^|\s)(\#)([^ \n]{2,})$/,
			index: 3,
			search: function(term, callback) { $.getJSON(baseurl + '/hashtag/' + '?t=' + term).done(function(data) { callback($.map(data, function(entry) { return entry.text.indexOf(term) === 0 ? entry : null; })); }); },
			replace: function(item) { return "$1$2" + item.text; },
			template: tag_format
		};

		this.attr('autocomplete', 'off');
		this.textcomplete([contacts, community, tags], {dropdown: {className:'acpopup', maxCount:100}});
		this.fixTextcompleteEscape();
		this.on('textComplete:select', function(e, value, strategy) { submit_form(this); });

		return this;
	};

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
		this.textcomplete([names], {dropdown: {className:'acpopup'}});
		this.fixTextcompleteEscape();

		if(autosubmit) {
			this.on('textComplete:select', function(e,value,strategy) { submit_form(this); });
		}

		if(typeof onselect !== 'undefined') {
			this.on('textComplete:select', function(e, value, strategy) { onselect(value); });
		}

		return this;
	};

	$.fn.bbco_autocomplete = function(type) {
		if (type === 'bbcode') {
			var open_close_elements = ['bold', 'italic', 'underline', 'overline', 'strike', 'quote', 'code', 'spoiler', 'map', 'img', 'url', 'audio', 'video', 'embed', 'youtube', 'vimeo', 'list', 'ul', 'ol', 'li', 'table', 'tr', 'th', 'td', 'center', 'color', 'font', 'size', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'nobb', 'noparse', 'pre', 'abstract', 'share'];
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
		this.textcomplete([bbco], {dropdown: {className:'acpopup'}});
		this.fixTextcompleteEscape();

		this.on('textComplete:select', function(e, value, strategy) { value; });

		this.keypress(function(e){
			if (e.keyCode == 13) {
				var x = listNewLineAutocomplete(this.id);
				if(x) {
					e.stopImmediatePropagation();
					e.preventDefault();
				}
			}
		});

		return this;
	};
})( jQuery );
// @license-end
