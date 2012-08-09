{{ for $threads as $item }}
<div id="tread-wrapper-$item.id" class="tread-wrapper {{ if $item.threaded }}threaded{{ endif }}">
       
		{{ if $item.type == tag }}
			{{ inc wall_item_tag.tpl }}{{ endinc }}
		{{ else }}
			{{ inc $item.template }}{{ endinc }}
		{{ endif }}
		
</div>
{{ endfor }}

<div id="conversation-end"></div>

{{ if $dropping }}
<a href="#" onclick="deleteCheckedItems();return false;">
	<span class="icon s22 delete text">$dropping</span>
</a>
{{ endif }}

<script>
// jquery color plugin from https://raw.github.com/gist/1891361/17747b50ad87f7a59a14b4e0f38d8f3fb6a18b27/gistfile1.js
    (function(d){d.each(["backgroundColor","borderBottomColor","borderLeftColor","borderRightColor","borderTopColor","color","outlineColor"],function(f,e){d.fx.step[e]=function(g){if(!g.colorInit){g.start=c(g.elem,e);g.end=b(g.end);g.colorInit=true}g.elem.style[e]="rgb("+[Math.max(Math.min(parseInt((g.pos*(g.end[0]-g.start[0]))+g.start[0]),255),0),Math.max(Math.min(parseInt((g.pos*(g.end[1]-g.start[1]))+g.start[1]),255),0),Math.max(Math.min(parseInt((g.pos*(g.end[2]-g.start[2]))+g.start[2]),255),0)].join(",")+")"}});function b(f){var e;if(f&&f.constructor==Array&&f.length==3){return f}if(e=/rgb\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*\)/.exec(f)){return[parseInt(e[1]),parseInt(e[2]),parseInt(e[3])]}if(e=/rgb\(\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*\)/.exec(f)){return[parseFloat(e[1])*2.55,parseFloat(e[2])*2.55,parseFloat(e[3])*2.55]}if(e=/#([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})/.exec(f)){return[parseInt(e[1],16),parseInt(e[2],16),parseInt(e[3],16)]}if(e=/#([a-fA-F0-9])([a-fA-F0-9])([a-fA-F0-9])/.exec(f)){return[parseInt(e[1]+e[1],16),parseInt(e[2]+e[2],16),parseInt(e[3]+e[3],16)]}if(e=/rgba\(0, 0, 0, 0\)/.exec(f)){return a.transparent}return a[d.trim(f).toLowerCase()]}function c(g,e){var f;do{f=d.curCSS(g,e);if(f!=""&&f!="transparent"||d.nodeName(g,"body")){break}e="backgroundColor"}while(g=g.parentNode);return b(f)}var a={transparent:[255,255,255]}})(jQuery);
    var colWhite = {backgroundColor:'#EFF0F1'};
    var colShiny = {backgroundColor:'#FCE94F'};
</script>

{{ if $mode == display }}
<script>
    var id = window.location.pathname.split("/").pop();
    $(window).scrollTop($('#item-'+id).position().top);
    $('#item-'+id).animate(colWhite, 1000).animate(colShiny).animate(colWhite, 2000);   
</script>
{{ endif }}

