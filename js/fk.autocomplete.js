/**
 * Friendica people autocomplete
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

	var w = 530;
	var h = 130;


	if(typeof elm.editorId == "undefined") {	
		style = $(elm).offset();
		w = $(elm).width();
		h = $(elm).height();
	}
	else {
		var container = elm.getContainer();
		if(typeof container != "undefined") {
			style = $(container).offset();
			w = $(container).width();
	    	h = $(container).height();
		}
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
	
	this.cont = $("<div class='acpopup'></div>");
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
					html = "<img src='{0}' height='16px' width='16px'>{1} ({2})".format(this.photo, this.name, this.nick)
						that.add(html, this.nick.replace(' ','') + '+' + this.id + ' - ' + this.link);
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
			newtxt = txt.replace(that.searchText,t+' ');
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

function ContactAutocomplete(element,backend_url){
	this.pattern=/@([^ \n]+)$/;
	this.popup=null;
	var that = this;
	
	$(element).unbind('keydown');
	$(element).unbind('keyup');
	
	$(element).keydown(function(event){
		if (that.popup!==null) that.popup.onkey(event);
	});
	
	$(element).keyup(function(event){
		cpos = $(this).getSelection();
		if (cpos.start==cpos.end){
			match = $(this).val().substring(0,cpos.start).match(that.pattern);
			if (match!==null){
				if (that.popup===null){
					that.popup = new ACPopup(this, backend_url);
				}
				if (that.popup.ready && match[1]!==that.popup.searchText) that.popup.search(match[1]);
				if (!that.popup.ready) that.popup=null;
				
			} else {
				if (that.popup!==null) {that.popup.close(); that.popup=null;}
			}
			
			
		}
	});		
	
}


/**
 * jQuery plugin 'contact_autocomplete'
 */
(function( $ ){
  $.fn.contact_autocomplete = function(backend_url) {
    this.each(function(){
		new ContactAutocomplete(this, backend_url);
	});
  };
})( jQuery );



		
