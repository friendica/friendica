function ACL(backend_url, preset, automention, is_mobile){

	this.url = backend_url;
	this.automention = automention;
	this.is_mobile = is_mobile;


	this.kp_timer = null;

	if (preset==undefined) preset = [];
	this.allow_cid = (preset[0] || []);
	this.allow_gid = (preset[1] || []);
	this.deny_cid  = (preset[2] || []);
	this.deny_gid  = (preset[3] || []);
	this.group_uids = [];

	if (this.is_mobile) {
		this.nw = 1;
	} else {
		this.nw = 4;
	}


	this.list_content = $("#acl-list-content");
	this.item_tpl = unescape($(".acl-list-item[rel=acl-template]").html());
	this.showall = $("#acl-showall");

	if (preset.length==0) this.showall.addClass("selected");

	/*events*/
	this.showall.click(this.on_showall.bind(this));
	$(document).on("click", ".acl-button-show", this.on_button_show.bind(this));
	$(document).on("click", ".acl-button-hide", this.on_button_hide.bind(this));
	$("#acl-search").keypress(this.on_search.bind(this));
	$("#acl-wrapper").parents("form").submit(this.on_submit.bind(this));

	/* add/remove mentions  */
	this.element = $("#profile-jot-text");
	this.htmlelm = this.element.get()[0];

	/* startup! */
	this.get(0,100);
}

ACL.prototype.remove_mention = function(id) {
	if (!this.automention) {
		return;
	}
	var nick = this.data[id].nick;
	var searchText = "@" + nick + "+" + id + " ";
	var start = this.element.val().indexOf(searchText);
	if (start < 0) {
		return;
	}
	var end = start + searchText.length;
	this.element.setSelection(start, end).replaceSelectedText('').collapseSelection(false);
}

ACL.prototype.add_mention = function(id) {
	if (!this.automention) {
		return;
	}
	var nick = this.data[id].nick;
	var searchText =  "@" + nick + "+" + id + " ";
	if (this.element.val().indexOf( searchText) >= 0 ) {
		return;
	}
	this.element.val(searchText + this.element.val());
}

ACL.prototype.on_submit = function(){
	var aclfields = $("#acl-fields").html("");
	$(this.allow_gid).each(function(i,v){
		aclfields.append("<input type='hidden' name='group_allow[]' value='"+v+"'>");
	});
	$(this.allow_cid).each(function(i,v){
		aclfields.append("<input type='hidden' name='contact_allow[]' value='"+v+"'>");
	});
	$(this.deny_gid).each(function(i,v){
		aclfields.append("<input type='hidden' name='group_deny[]' value='"+v+"'>");
	});
	$(this.deny_cid).each(function(i,v){
		aclfields.append("<input type='hidden' name='contact_deny[]' value='"+v+"'>");
	});
}

ACL.prototype.search = function(){
	var srcstr = $("#acl-search").val();
	this.list_content.html("");
	this.get(0,100, srcstr);
}

ACL.prototype.on_search = function(event){
	if (this.kp_timer) clearTimeout(this.kp_timer);
	this.kp_timer = setTimeout( this.search.bind(this), 1000);
}

ACL.prototype.on_showall = function(event){
	event.preventDefault()
	event.stopPropagation();

	if (this.showall.hasClass("selected")){
		return false;
	}
	this.showall.addClass("selected");

	this.allow_cid = [];
	this.allow_gid = [];
	this.deny_cid  = [];
	this.deny_gid  = [];

	this.update_view();

	return false;
}

ACL.prototype.on_button_show = function(event){
	event.preventDefault()
	event.stopImmediatePropagation()
	event.stopPropagation();

	this.set_allow($(event.target).parent().attr('id'));

	return false;
}
ACL.prototype.on_button_hide = function(event){
	event.preventDefault()
	event.stopImmediatePropagation()
	event.stopPropagation();

	this.set_deny($(event.target).parent().attr('id'));

	return false;
}

ACL.prototype.set_allow = function(itemid){
	type = itemid[0];
	id     = parseInt(itemid.substr(1));

	switch(type){
		case "g":
			if (this.allow_gid.indexOf(id)<0){
				this.allow_gid.push(id)
			}else {
				this.allow_gid.remove(id);
			}
			if (this.deny_gid.indexOf(id)>=0) this.deny_gid.remove(id);
			break;
		case "c":
			if (this.allow_cid.indexOf(id)<0){
				this.allow_cid.push(id)
				if (this.data[id].forum=="1") this.add_mention(id);
			} else {
				this.allow_cid.remove(id);
				if (this.data[id].forum=="1") this.remove_mention(id);
			}
			if (this.deny_cid.indexOf(id)>=0) this.deny_cid.remove(id);
			break;
	}
	this.update_view();
}

ACL.prototype.set_deny = function(itemid){
	type = itemid[0];
	id     = parseInt(itemid.substr(1));

	switch(type){
		case "g":
			if (this.deny_gid.indexOf(id)<0){
				this.deny_gid.push(id)
			} else {
				this.deny_gid.remove(id);
			}
			if (this.allow_gid.indexOf(id)>=0) this.allow_gid.remove(id);
			break;
		case "c":
			if (this.data[id].forum=="1") this.remove_mention(id);
			if (this.deny_cid.indexOf(id)<0){
				this.deny_cid.push(id)
			} else {
				this.deny_cid.remove(id);
			}
			if (this.allow_cid.indexOf(id)>=0) this.allow_cid.remove(id);
			break;
	}
	this.update_view();
}

ACL.prototype.is_show_all = function() {
	return (this.allow_gid.length==0 && this.allow_cid.length==0 &&
		this.deny_gid.length==0 && this.deny_cid.length==0);
}

ACL.prototype.update_view = function(){
	if (this.is_show_all()){
			this.showall.addClass("selected");
			/* jot acl */
				$('#jot-perms-icon').removeClass('lock').addClass('unlock');
				$('#jot-public').show();
				$('.profile-jot-net input').attr('disabled', false);
				if(typeof editor != 'undefined' && editor != false) {
					$('#profile-jot-desc').html(ispublic);
				}

	} else {
			this.showall.removeClass("selected");
			/* jot acl */
				$('#jot-perms-icon').removeClass('unlock').addClass('lock');
				$('#jot-public').hide();
				$('.profile-jot-net input').attr('disabled', 'disabled');
				$('#profile-jot-desc').html('&nbsp;');
	}
	$("#acl-list-content .acl-list-item").each(function(){
		$(this).removeClass("groupshow grouphide");
	});

	$("#acl-list-content .acl-list-item").each(function(index, element){
		itemid = $(element).attr('id');
		type = itemid[0];
		id 	 = parseInt(itemid.substr(1));

		btshow = $(element).children(".acl-button-show").removeClass("selected");
		bthide = $(element).children(".acl-button-hide").removeClass("selected");

		switch(type){
			case "g":
				var uclass = "";
				if (this.allow_gid.indexOf(id)>=0){
					btshow.addClass("selected");
					bthide.removeClass("selected");
					uclass="groupshow";
				}
				if (this.deny_gid.indexOf(id)>=0){
					btshow.removeClass("selected");
					bthide.addClass("selected");
					uclass="grouphide";
				}

				$(this.group_uids[id]).each(function(i,v) {
					if(uclass == "grouphide")
						$("#c"+v).removeClass("groupshow");
					if(uclass != "") {
						var cls = $("#c"+v).attr('class');
						if( cls == undefined)
							return true;
						var hiding = cls.indexOf('grouphide');
						if(hiding == -1)
							$("#c"+v).addClass(uclass);
					}
				});

				break;
			case "c":
				if (this.allow_cid.indexOf(id)>=0){
					btshow.addClass("selected");
					bthide.removeClass("selected");
				}
				if (this.deny_cid.indexOf(id)>=0){
					btshow.removeClass("selected");
					bthide.addClass("selected");
				}
		}

	}.bind(this));

}


ACL.prototype.get = function(start,count, search){
	var postdata = {
		start:start,
		count:count,
		search:search,
	}

	$.ajax({
		type:'POST',
		url: this.url,
		data: postdata,
		dataType: 'json',
		success:this.populate.bind(this)
	});
}

ACL.prototype.populate = function(data){
	var height = Math.ceil(data.tot / this.nw) * 42;
	this.list_content.height(height);
	this.data = {};
	$(data.items).each(function(index, item){
		html = "<div class='acl-list-item {4} {5} type{2}' title='{6}' id='{2}{3}'>"+this.item_tpl+"</div>";
		html = html.format(item.photo, item.name, item.type, item.id, (item.forum=='1'?'forum':''), item.network, item.link);
		if (item.uids!=undefined) this.group_uids[item.id] = item.uids;

		this.list_content.append(html);
		this.data[item.id] = item;
	}.bind(this));
	$(".acl-list-item img[data-src]", this.list_content).each(function(i, el){
		// Add src attribute for images with a data-src attribute
		$(el).attr('src', $(el).data("src"));
	});

	this.update_view();
}

