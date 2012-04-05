/* jQuery ajax stream plugin
* Version 0.1
* Copyright (C) 2009 Chris Tarquini
* Licensed under a Creative Commons Attribution-Share Alike 3.0 Unported License (http://creativecommons.org/licenses/by-sa/3.0/)
* Permissions beyond the scope of this license may be available by contacting petros000[at]hotmail.com.
*/

(function($) {

// Save the original AJAX function
var ajax_old = $.ajax;
var get_old = $.get;
var post_old =  $.post;
var active = true;
// Add our settings
$.ajaxSetup({stream: false,pollInterval: 500/*, onDataRecieved: function(){}*/ });
$.enableAjaxStream = function(enable)
{
if(typeof enable == 'undefined') enable = !active;
if(!enable)
{
$.ajax = ajax_old;
$.get = get_old;
$.post = post_old;
active = false;
}
else
{
$.ajax = ajax_stream;
$.get = ajax_get_stream;
$.post = ajax_post_stream;
active = true;
}

}
var ajax_stream = $.ajax = function(options)
{
//copied from original ajax function
        options  = jQuery.extend(true, options, jQuery.extend(true, {}, jQuery.ajaxSettings, options));
if(options.stream)
{
var timer = 0;
var offset = 0;
var xmlhttp = null;
var lastlen = 0;
var done = false;
var hook = function(xhr)
{
xmlhttp = xhr;
checkagain();
}
var fix = function(){ check('stream'); };// fixes weird bug with random numbers as arg
var checkagain = function(){if(!done) timer = setTimeout(fix,options.pollInterval);}
var check = function(status)
{
if(typeof status == 'undefined') status = "stream";
if(xmlhttp.status < 3) return; //only get the latest packet if data has been sent
var text = xmlhttp.responseText;
if(status == 'stream') //if we arent streaming then just flush the buffer
{
if(text.length <= lastlen) { checkagain(); return;}
lastlength = text.length;
if(offset == text.length) { checkagain(); return;}
}
var pkt = text.substr(offset);
offset =  text.length;
if($.isFunction(options.OnDataRecieved))
{
options.OnDataRecieved(pkt, status, xmlhttp.responseText, xmlhttp);
}
if(xmlhttp.status != 4)
checkagain();
}
var complete = function(xhr,s)
{
clearTimeout(timer);//done..stop polling
done = true;
// send final call
check(s);
}
// If the complete callback is set create a new callback that calls the users and outs
if($.isFunction(options.success))
{
var oc = options.success;
options.success = function(xhr,s){ complete(xhr,s); oc(xhr,s);};

}
else options.success = complete;
// Set up our hook on the beforeSend
if($.isFunction(options.beforeSend))
{
var obs = options.beforeSend;
options.beforeSend = function(xhr){ obs(xhr); hook(xhr);};
}
else options.beforeSend = hook;

}
ajax_old(options);
}

var ajax_get_stream = $.get = function(url,data,callback,type,stream)
{
	if($.isFunction(data))
	{
		var orgcb = callback;
		callback = data;
		if($.isFunction(orgcb))
		{
			stream = orgcb;
		}
		data = null;
	}
	if($.isFunction(type))
	{
		stream = type;
		type = undefined;
	}
	var dostream = $.isFunction(stream);
	return jQuery.ajax({
					type: "GET",
					url: url,
					data: data,
					success: callback,
					dataType: type,
					stream: dostream,
					OnDataRecieved: stream
			});

}

var ajax_post_stream = $.post = function(url,data,callback,type,stream)
{
        if($.isFunction(data))
        {
				var orgcb = callback;
                callback = data;
        }
		if($.isFunction(type))
		{
			stream = type;
			type = undefined;
		}
		var dostream = $.isFunction(stream);
		return jQuery.ajax({
				type: "POST",
				url: url,
				data: data,
				success: callback,
				dataType: type,
				stream: dostream,
				OnDataRecieved: stream
		});

}

})(jQuery);	
	
