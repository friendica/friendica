/*!
 * TipTip
 * Copyright 2010 Drew Wilson
 * www.drewwilson.com
 * code.drewwilson.com/entry/tiptip-jquery-plugin
 *
 * Version 1.3   -   Updated: Mar. 23, 2010
 *
 * This Plug-In will create a custom tooltip to replace the default
 * browser tooltip. It is extremely lightweight and very smart in
 * that it detects the edges of the browser window and will make sure
 * the tooltip stays within the current window size. As a result the
 * tooltip will adjust itself to be displayed above, below, to the left
 * or to the right depending on what is necessary to stay within the
 * browser window. It is completely customizable as well via CSS.
 *
 * This TipTip jQuery plug-in is dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */
(function(a){a.fn.tipTip=function(c){var g={activation:"hover",keepAlive:false,maxWidth:"200px",edgeOffset:3,defaultPosition:"bottom",delay:400,fadeIn:200,fadeOut:200,attribute:"title",content:false,enter:function(){},exit:function(){}};var e=a.extend(g,c);if(a("#tiptip_holder").length<=0){var b=a('<div id="tiptip_holder" style="max-width:'+e.maxWidth+';"></div>');var d=a('<div id="tiptip_content"></div>');var f=a('<div id="tiptip_arrow"></div>');a("body").append(b.html(d).prepend(f.html('<div id="tiptip_arrow_inner"></div>')))}else{var b=a("#tiptip_holder");var d=a("#tiptip_content");var f=a("#tiptip_arrow")}return this.each(function(){var i=a(this);if(e.content){var l=e.content}else{var l=i.attr(e.attribute)}if(l&&l!=""){if(!e.content){i.removeAttr(e.attribute)}var h=false;if(e.activation=="hover"){i.hover(function(){k()},function(){if(!e.keepAlive){j()}});if(e.keepAlive){b.hover(function(){},function(){j()})}}else{if(e.activation=="focus"){i.focus(function(){k()}).blur(function(){j()})}else{if(e.activation=="click"){i.click(function(){k();return false}).hover(function(){},function(){if(!e.keepAlive){j()}});if(e.keepAlive){b.hover(function(){},function(){j()})}}}}function k(){e.enter.call(this);d.html(l);b.hide().removeAttr("class").css("margin","0");f.removeAttr("style");var y=parseInt(i.offset()["top"]);var p=parseInt(i.offset()["left"]);var v=parseInt(i.outerWidth());var A=parseInt(i.outerHeight());var x=b.outerWidth();var s=b.outerHeight();var w=Math.round((v-x)/2);var o=Math.round((A-s)/2);var n=Math.round(p+w);var m=Math.round(y+A+e.edgeOffset);var t="";var C="";var u=Math.round(x-12)/2;if(e.defaultPosition=="bottom"){t="_bottom"}else{if(e.defaultPosition=="top"){t="_top"}else{if(e.defaultPosition=="left"){t="_left"}else{if(e.defaultPosition=="right"){t="_right"}}}}var r=(w+p)<parseInt(a(window).scrollLeft());var q=(x+p)>parseInt(a(window).width());if((r&&w<0)||(t=="_right"&&!q)||(t=="_left"&&p<(x+e.edgeOffset+5))){t="_right";C=Math.round(s-13)/2;u=-12;n=Math.round(p+v+e.edgeOffset);m=Math.round(y+o)}else{if((q&&w<0)||(t=="_left"&&!r)){t="_left";C=Math.round(s-13)/2;u=Math.round(x);n=Math.round(p-(x+e.edgeOffset+5));m=Math.round(y+o)}}var z=(y+A+e.edgeOffset+s+8)>parseInt(a(window).height()+a(window).scrollTop());var B=((y+A)-(e.edgeOffset+s+8))<0;if(z||(t=="_bottom"&&z)||(t=="_top"&&!B)){if(t=="_top"||t=="_bottom"){t="_top"}else{t=t+"_top"}C=s;m=Math.round(y-(s+5+e.edgeOffset))}else{if(B|(t=="_top"&&B)||(t=="_bottom"&&!z)){if(t=="_top"||t=="_bottom"){t="_bottom"}else{t=t+"_bottom"}C=-12;m=Math.round(y+A+e.edgeOffset)}}if(t=="_right_top"||t=="_left_top"){m=m+5}else{if(t=="_right_bottom"||t=="_left_bottom"){m=m-5}}if(t=="_left_top"||t=="_left_bottom"){n=n+5}f.css({"margin-left":u+"px","margin-top":C+"px"});b.css({"margin-left":n+"px","margin-top":m+"px"}).attr("class","tip"+t);if(h){clearTimeout(h)}h=setTimeout(function(){b.stop(true,true).fadeIn(e.fadeIn)},e.delay)}function j(){e.exit.call(this);if(h){clearTimeout(h)}b.fadeOut(e.fadeOut)}}})}})(jQuery);
