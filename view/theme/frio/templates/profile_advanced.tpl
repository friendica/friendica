<div id="profile-page" class="generic-page-wrapper">
	<h3 class="">{{$title}}</h3>

	{{* The link to edit the profile*}}
	{{if $profile.edit}}
	<ul class="nav nav-pills preferences">
		<li class="pull-right">
			<a class="btn btn-link btn-sm" type="button" id="profile-edit-link" href="{{$profile.edit.0}}" title="{{$profile.edit.3}}">
				<i class="fa fa-pencil-square-o" aria-hidden="true"></i>&nbsp;{{$profile.edit.1}}
			</a>
		</li>
	</ul>
	<div class="clear"></div>
	{{/if}}

	{{* Frio does split the profile information in "standard" and "advanced". This is the tab menu for swithching between this modes *}}
	<ul id="profile-menu" class="nav nav-tabs" role="tablist">
		<li role="presentation" class="active">
			<a href="#profile-content-standard" aria-controls="profile-content-standard" role="tab" data-toggle="tab">{{$basic}}</a>
		</li>
		<li role="presentation">
			<a href="#profile-content-advanced" aria-controls="profile-content-advanced" role="tab" data-toggle="tab">{{$advanced}}</a>
		</li>
	</ul>

	<div class="tab-content">
		<div role="tabpanel" class="tab-pane active" id="profile-content-standard">
			<div id="aprofile-fullname" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 aprofile">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$profile.fullname.0}}</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$profile.fullname.1}}</div>
			</div>

			{{if $profile.gender}}
			<div id="aprofile-gender" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 aprofile">
				<hr class="profile-separator">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$profile.gender.0}}</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$profile.gender.1}}</div>
			</div>
			{{/if}}

			{{if $profile.birthday}}
			<div id="aprofile-birthday" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 aprofile">
				<hr class="profile-separator">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$profile.birthday.0}}</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$profile.birthday.1}}</div>
			</div>
			{{/if}}

			{{if $profile.age}}
			<div id="aprofile-age" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 aprofile">
				<hr class="profile-separator">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$profile.age.0}}</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$profile.age.1}}</div>
			</div>
			{{/if}}

			{{if $profile.hometown}}
			<div id="aprofile-hometown" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 aprofile">
				<hr class="profile-separator">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$profile.hometown.0}}</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$profile.hometown.1}}</div>
			</div>
			{{/if}}

			{{if $profile.marital}}
			<div id="aprofile-marital" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 aprofile">
				<hr class="profile-separator">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted"><span class="heart">&hearts;</span>  {{$profile.marital.0}}</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$profile.marital.1}}{{if $profile.marital.with}} ({{$profile.marital.with}}){{/if}}{{if $profile.howlong}} {{$profile.howlong}}{{/if}}</div>
			</div>
			{{/if}}

			{{if $profile.homepage}}
			<div id="aprofile-homepage" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 aprofile">
				<hr class="profile-separator">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$profile.homepage.0}}</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$profile.homepage.1}}</div>
			</div>
			{{/if}}

			{{if $profile.about}}
			<div id="aprofile-about" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 aprofile">
				<hr class="profile-separator">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$profile.about.0}}</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$profile.about.1}}</div>
			</div>
			{{/if}}

			{{if $profile.pub_keywords}}
			<div id="aprofile-tags" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 aprofile">
				<hr class="profile-separator">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$profile.pub_keywords.0}}</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$profile.pub_keywords.1}}</div>
			</div>
			{{/if}}
		</div>

		<div role="tabpanel" class="tab-pane advanced" id="profile-content-advanced">
			{{if $profile.sexual}}
			<div id="aprofile-sexual" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 aprofile">
				<hr class="profile-separator">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$profile.sexual.0}}</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$profile.sexual.1}}</div>
			</div>
			{{/if}}

			{{if $profile.politic}}
			<div id="aprofile-politic" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 aprofile">
				<hr class="profile-separator">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$profile.politic.0}}</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$profile.politic.1}}</div>
			</div>
			{{/if}}

			{{if $profile.religion}}
			<div id="aprofile-religion" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 aprofile">
				<hr class="profile-separator">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$profile.religion.0}}</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$profile.religion.1}}</div>
			</div>
			{{/if}}


			{{if $profile.interest}}
			<div id="aprofile-interest" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 aprofile">
				<hr class="profile-separator">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$profile.interest.0}}</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$profile.interest.1}}</div>
			</div>
			{{/if}}

			{{if $profile.likes}}
			<div id="aprofile-likes" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 aprofile">
				<hr class="profile-separator">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$profile.likes.0}}</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$profile.likes.1}}</div>
			</div>
			{{/if}}

			{{if $profile.dislikes}}
			<div id="aprofile-dislikes" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 aprofile">
				<hr class="profile-separator">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$profile.dislikes.0}}</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$profile.dislikes.1}}</div>
			</div>
			{{/if}}

			{{if $profile.contact}}
			<div id="aprofile-contact" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 aprofile">
				<hr class="profile-separator">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$profile.contact.0}}</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$profile.contact.1}}</div>
			</div>
			{{/if}}


			{{if $profile.music}}
			<div id="aprofile-music" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 aprofile">
				<hr class="profile-separator">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$profile.music.0}}</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$profile.music.1}}</div>
			</div>
			{{/if}}


			{{if $profile.book}}
			<div id="aprofile-book" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 aprofile">
				<hr class="profile-separator">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$profile.book.0}}</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$profile.book.1}}</div>
			</div>
			{{/if}}


			{{if $profile.tv}}
			<div id="aprofile-tv" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 aprofile">
				<hr class="profile-separator">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$profile.tv.0}}</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$profile.tv.1}}</div>
			</div>
			{{/if}}


			{{if $profile.film}}
			<div id="aprofile-film" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 aprofile">
				<hr class="profile-separator">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$profile.film.0}}</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$profile.film.1}}</div>
			</div>
			{{/if}}


			{{if $profile.romance}}
			<div id="aprofile-romance" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 aprofile">
				<hr class="profile-separator">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$profile.romance.0}}</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$profile.romance.1}}</div>
			</div>
			{{/if}}


			{{if $profile.work}}
			<div id="aprofile-work" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 aprofile">
			<hr class="profile-separator">
			<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$profile.work.0}}</div>
			<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$profile.work.1}}</div>
			</div>
			{{/if}}

			{{if $profile.education}}
			<div id="aprofile-education" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 aprofile">
				<hr class="profile-separator">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$profile.education.0}}</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$profile.education.1}}</div>
			</div>
			{{/if}}

			{{if $profile.forumlist}}
			<div id="aprofile-forumlist" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 aprofile">
				<hr class="profile-separator">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$profile.forumlist.0}}</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$profile.forumlist.1}}</div>
			</div>
			{{/if}}
		</div>
	</div>
</div>
