/**
 * editor_plugin_src.js
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://tinymce.moxiecode.com/license
 * Contributing: http://tinymce.moxiecode.com/contributing
 */

/* Macgirvin Aug-2010 changed from punbb to dfrn dialect */

(function() {
	tinymce.create('tinymce.plugins.BBCodePlugin', {
		init : function(ed, url) {
			var t = this, dialect = ed.getParam('bbcode_dialect', 'dfrn').toLowerCase();

			ed.onBeforeSetContent.add(function(ed, o) {
				o.content = t['_' + dialect + '_bbcode2html'](o.content);
			});

			ed.onPostProcess.add(function(ed, o) {
				if (o.set)
					o.content = t['_' + dialect + '_bbcode2html'](o.content);

				if (o.get)
					o.content = t['_' + dialect + '_html2bbcode'](o.content);
			});
		},

		getInfo : function() {
			return {
				longname : 'BBCode Plugin',
				author : 'Moxiecode Systems AB',
				authorurl : 'http://tinymce.moxiecode.com',
				infourl : 'http://wiki.moxiecode.com/index.php/TinyMCE:Plugins/bbcode',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		},

		// Private methods

		// HTML -> BBCode in DFRN dialect
		_dfrn_html2bbcode : function(s) {
			s = tinymce.trim(s);

			function rep(re, str) {

				//modify code to keep stuff intact within [code][/code] blocks
				//Waitman Gobble NO WARRANTY


				var o = new Array();
				var x = s.split("[code]");
				var i = 0;

				var si = "";
				si = x.shift();
				si = si.replace(re,str);
				o.push(si);

				for (i = 0; i < x.length; i++) {
					var no = new Array();
					var j = x.shift();
					var g = j.split("[/code]");
					no.push(g.shift());
					si = g.shift();
					si = si.replace(re,str);
					no.push(si);
					o.push(no.join("[/code]"));
				}

				s = o.join("[code]");

			};




			/* oembed */
			function _h2b_cb(match) {
				/*
				function s_h2b(data) {
						match = data;
				}
				$.ajax({
					type:"POST",
					url: 'oembed/h2b',
					data: {text: match},
					async: false,
					success: s_h2b,
					dataType: 'html'
				});
				*/
				
				var f, g, tof = [], tor = [];
				var find_spanc = /<span [^>]*class *= *[\"'](?:[^\"']* )*oembed(?: [^\"']*)*[\"'][^>]*>(.*?(?:<span[^>]*>(.*?)<\/span *>)*.*?)<\/span *>/ig;
				while (f = find_spanc.exec(match)) {
					var find_a = /<a([^>]* rel=[\"']oembed[\"'][^>]*)>.*?<\/a *>/ig;
					if (g = find_a.exec(f[1])) {
						var find_href = /href=[\"']([^\"']*)[\"']/ig;
						var m2 = find_href.exec(g[1]);
						if (m2[1]) {
							tof.push(f[0]);
							tor.push("[EMBED]" + m2[1] + "[/EMBED]");
						}
					}
				}
				for (var i = 0; i < tof.length; i++) match = match.replace(tof[i], tor[i]);
				
				return match;
			}
			if (s.indexOf('class="oembed')>=0){
				//alert("request oembed html2bbcode");
				s = _h2b_cb(s);
			}
			
			/* /oembed */


			// example: <strong> to [b]
			rep(/<a class=\"bookmark\" href=\"(.*?)\".*?>(.*?)<\/a>/gi,"[bookmark=$1]$2[/bookmark]");
			rep(/<a.*?href=\"(.*?)\".*?>(.*?)<\/a>/gi,"[url=$1]$2[/url]");
			rep(/<span style=\"font-size:(.*?);\">(.*?)<\/span>/gi,"[size=$1]$2[/size]");
			rep(/<span style=\"color:(.*?);\">(.*?)<\/span>/gi,"[color=$1]$2[/color]");
			rep(/<font>(.*?)<\/font>/gi,"$1");
			rep(/<img.*?width=\"(.*?)\".*?height=\"(.*?)\".*?src=\"(.*?)\".*?\/>/gi,"[img=$1x$2]$3[/img]");
			rep(/<img.*?height=\"(.*?)\".*?width=\"(.*?)\".*?src=\"(.*?)\".*?\/>/gi,"[img=$2x$1]$3[/img]");
			rep(/<img.*?src=\"(.*?)\".*?height=\"(.*?)\".*?width=\"(.*?)\".*?\/>/gi,"[img=$3x$2]$1[/img]");
			rep(/<img.*?src=\"(.*?)\".*?width=\"(.*?)\".*?height=\"(.*?)\".*?\/>/gi,"[img=$2x$3]$1[/img]");
			rep(/<img.*?src=\"(.*?)\".*?\/>/gi,"[img]$1[/img]");

			rep(/<ul class=\"listbullet\" style=\"list-style-type\: circle\;\">(.*?)<\/ul>/gi,"[list]$1[/list]");
			rep(/<ul class=\"listnone\" style=\"list-style-type\: none\;\">(.*?)<\/ul>/gi,"[list=]$1[/list]");
			rep(/<ul class=\"listdecimal\" style=\"list-style-type\: decimal\;\">(.*?)<\/ul>/gi,"[list=1]$1[/list]");
			rep(/<ul class=\"listlowerroman\" style=\"list-style-type\: lower-roman\;\">(.*?)<\/ul>/gi,"[list=i]$1[/list]");
			rep(/<ul class=\"listupperroman\" style=\"list-style-type\: upper-roman\;\">(.*?)<\/ul>/gi,"[list=I]$1[/list]");
			rep(/<ul class=\"listloweralpha\" style=\"list-style-type\: lower-alpha\;\">(.*?)<\/ul>/gi,"[list=a]$1[/list]");
			rep(/<ul class=\"listupperalpha\" style=\"list-style-type\: upper-alpha\;\">(.*?)<\/ul>/gi,"[list=A]$1[/list]");
			rep(/<li>(.*?)<\/li>/gi,'[li]$1[/li]');

			rep(/<code>(.*?)<\/code>/gi,"[code]$1[/code]");
			rep(/<\/(strong|b)>/gi,"[/b]");
			rep(/<(strong|b)>/gi,"[b]");
			rep(/<\/(em|i)>/gi,"[/i]");
			rep(/<(em|i)>/gi,"[i]");
			rep(/<\/u>/gi,"[/u]");
			rep(/<span style=\"text-decoration: ?underline;\">(.*?)<\/span>/gi,"[u]$1[/u]");
			rep(/<u>/gi,"[u]");
			rep(/<blockquote[^>]*>/gi,"[quote]");
			rep(/<\/blockquote>/gi,"[/quote]");
			rep(/<hr \/>/gi,"[hr]");
			rep(/<br (.*?)\/>/gi,"\n\n");
			rep(/<br\/>/gi,"\n\n");
			rep(/<br>/gi,"\n");
			rep(/<p>/gi,"");
			rep(/<\/p>/gi,"\n");
			rep(/&nbsp;/gi," ");
			rep(/&quot;/gi,"\"");
			rep(/&lt;/gi,"<");
			rep(/&gt;/gi,">");
			rep(/&amp;/gi,"&");

			return s; 
		},

		// BBCode -> HTML from DFRN dialect
		_dfrn_bbcode2html : function(s) {
			s = tinymce.trim(s);


                        function rep(re, str) {

                                //modify code to keep stuff intact within [code][/code] blocks
                                //Waitman Gobble NO WARRANTY


                                var o = new Array();
                                var x = s.split("[code]");
                                var i = 0;

                                var si = "";
                                si = x.shift();
                                si = si.replace(re,str);
                                o.push(si);

                                for (i = 0; i < x.length; i++) {
                                        var no = new Array();
                                        var j = x.shift();
                                        var g = j.split("[/code]");
                                        no.push(g.shift());
                                        si = g.shift();
                                        si = si.replace(re,str);
                                        no.push(si);
                                        o.push(no.join("[/code]"));
                                }

                                s = o.join("[code]");

                        };





			// example: [b] to <strong>
			rep(/\n/gi,"<br />");
			rep(/\[b\]/gi,"<strong>");
			rep(/\[\/b\]/gi,"</strong>");
			rep(/\[i\]/gi,"<em>");
			rep(/\[\/i\]/gi,"</em>");
			rep(/\[u\]/gi,"<u>");
			rep(/\[\/u\]/gi,"</u>");
			rep(/\[hr\]/gi,"<hr />");
			rep(/\[bookmark=([^\]]+)\](.*?)\[\/bookmark\]/gi,"<a class=\"bookmark\" href=\"$1\">$2</a>");
			rep(/\[url=([^\]]+)\](.*?)\[\/url\]/gi,"<a href=\"$1\">$2</a>");
			rep(/\[url\](.*?)\[\/url\]/gi,"<a href=\"$1\">$1</a>");
			rep(/\[img=(.*?)x(.*?)\](.*?)\[\/img\]/gi,"<img width=\"$1\" height=\"$2\" src=\"$3\" />");
			rep(/\[img\](.*?)\[\/img\]/gi,"<img src=\"$1\" />");

			rep(/\[list\](.*?)\[\/list\]/gi, '<ul class="listbullet" style="list-style-type: circle;">$1</ul>');
			rep(/\[list=\](.*?)\[\/list\]/gi, '<ul class="listnone" style="list-style-type: none;">$1</ul>');
			rep(/\[list=1\](.*?)\[\/list\]/gi, '<ul class="listdecimal" style="list-style-type: decimal;">$1</ul>');
			rep(/\[list=i\](.*?)\[\/list\]/gi,'<ul class="listlowerroman" style="list-style-type: lower-roman;">$1</ul>');
			rep(/\[list=I\](.*?)\[\/list\]/gi, '<ul class="listupperroman" style="list-style-type: upper-roman;">$1</ul>');
			rep(/\[list=a\](.*?)\[\/list\]/gi, '<ul class="listloweralpha" style="list-style-type: lower-alpha;">$1</ul>');
			rep(/\[list=A\](.*?)\[\/list\]/gi, '<ul class="listupperalpha" style="list-style-type: upper-alpha;">$1</ul>');
			rep(/\[li\](.*?)\[\/li\]/gi, '<li>$1</li>');
			rep(/\[color=(.*?)\](.*?)\[\/color\]/gi,"<span style=\"color: $1;\">$2</span>");
			rep(/\[size=(.*?)\](.*?)\[\/size\]/gi,"<span style=\"font-size: $1;\">$2</span>");
			rep(/\[code\](.*?)\[\/code\]/gi,"<code>$1</code>");
			rep(/\[quote.*?\](.*?)\[\/quote\]/gi,"<blockquote>$1</blockquote>");

			/* oembed */
			function _b2h_cb(match, url) {
				url = bin2hex(url);
				function s_b2h(data) {
						match = data;
				}
				$.ajax({
					url: 'oembed/b2h?url=' + url,
					async: false,
					success: s_b2h,
					dataType: 'html'
				});
				return match;
			}
			s = s.replace(/\[embed\](.*?)\[\/embed\]/gi, _b2h_cb);
			
			/* /oembed */

			return s; 
		}
	});

	// Register plugin
	tinymce.PluginManager.add('bbcode', tinymce.plugins.BBCodePlugin);
})();
