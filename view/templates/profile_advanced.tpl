
<h2>{{$title}}</h2>

<dl id="aprofile-fullname" class="aprofile">
 <dt>{{$profile.fullname.0}}</dt>
 <dd>{{$profile.fullname.1}}</dd>
</dl>

{{if $profile.gender}}
<dl id="aprofile-gender" class="aprofile">
 <dt>{{$profile.gender.0}}</dt>
 <dd>{{$profile.gender.1}}</dd>
</dl>
{{/if}}

{{if $profile.birthday}}
<dl id="aprofile-birthday" class="aprofile">
 <dt>{{$profile.birthday.0}}</dt>
 <dd>{{$profile.birthday.1}}</dd>
</dl>
{{/if}}

{{if $profile.age}}
<dl id="aprofile-age" class="aprofile">
 <dt>{{$profile.age.0}}</dt>
 <dd>{{$profile.age.1}}</dd>
</dl>
{{/if}}

{{if $profile.marital}}
<dl id="aprofile-marital" class="aprofile">
 <dt><span class="heart">&hearts;</span>  {{$profile.marital.0}}</dt>
 <dd>{{$profile.marital.1}}{{if $profile.marital.with}} ({{$profile.marital.with}}){{/if}}{{if $profile.howlong}} {{$profile.howlong}}{{/if}}</dd>
</dl>
{{/if}}

{{if $profile.sexual}}
<dl id="aprofile-sexual" class="aprofile">
 <dt>{{$profile.sexual.0}}</dt>
 <dd>{{$profile.sexual.1}}</dd>
</dl>
{{/if}}

{{if $profile.pub_keywords}}
<dl id="aprofile-tags" class="aprofile">
 <dt>{{$profile.pub_keywords.0}}</dt>
 <dd>{{$profile.pub_keywords.1}}</dd>
</dl>
{{/if}}

{{if $profile.homepage}}
<dl id="aprofile-homepage" class="aprofile">
 <dt>{{$profile.homepage.0}}</dt>
 <dd>{{$profile.homepage.1}}</dd>
</dl>
{{/if}}

{{if $profile.hometown}}
<dl id="aprofile-hometown" class="aprofile">
 <dt>{{$profile.hometown.0}}</dt>
 <dd>{{$profile.hometown.1}}</dd>
</dl>
{{/if}}

{{if $profile.politic}}
<dl id="aprofile-politic" class="aprofile">
 <dt>{{$profile.politic.0}}</dt>
 <dd>{{$profile.politic.1}}</dd>
</dl>
{{/if}}

{{if $profile.religion}}
<dl id="aprofile-religion" class="aprofile">
 <dt>{{$profile.religion.0}}</dt>
 <dd>{{$profile.religion.1}}</dd>
</dl>
{{/if}}

{{if $profile.about}}
<dl id="aprofile-about" class="aprofile">
 <dt>{{$profile.about.0}}</dt>
 <dd>{{$profile.about.1}}</dd>
</dl>
{{/if}}

{{if $profile.interest}}
<dl id="aprofile-interest" class="aprofile">
 <dt>{{$profile.interest.0}}</dt>
 <dd>{{$profile.interest.1}}</dd>
</dl>
{{/if}}

{{if $profile.likes}}
<dl id="aprofile-likes" class="aprofile">
 <dt>{{$profile.likes.0}}</dt>
 <dd>{{$profile.likes.1}}</dd>
</dl>
{{/if}}

{{if $profile.dislikes}}
<dl id="aprofile-dislikes" class="aprofile">
 <dt>{{$profile.dislikes.0}}</dt>
 <dd>{{$profile.dislikes.1}}</dd>
</dl>
{{/if}}

{{if $profile.contact}}
<dl id="aprofile-contact" class="aprofile">
 <dt>{{$profile.contact.0}}</dt>
 <dd>{{$profile.contact.1}}</dd>
</dl>
{{/if}}


{{if $profile.music}}
<dl id="aprofile-music" class="aprofile">
 <dt>{{$profile.music.0}}</dt>
 <dd>{{$profile.music.1}}</dd>
</dl>
{{/if}}


{{if $profile.book}}
<dl id="aprofile-book" class="aprofile">
 <dt>{{$profile.book.0}}</dt>
 <dd>{{$profile.book.1}}</dd>
</dl>
{{/if}}


{{if $profile.tv}}
<dl id="aprofile-tv" class="aprofile">
 <dt>{{$profile.tv.0}}</dt>
 <dd>{{$profile.tv.1}}</dd>
</dl>
{{/if}}


{{if $profile.film}}
<dl id="aprofile-film" class="aprofile">
 <dt>{{$profile.film.0}}</dt>
 <dd>{{$profile.film.1}}</dd>
</dl>
{{/if}}


{{if $profile.romance}}
<dl id="aprofile-romance" class="aprofile">
 <dt>{{$profile.romance.0}}</dt>
 <dd>{{$profile.romance.1}}</dd>
</dl>
{{/if}}


{{if $profile.work}}
<dl id="aprofile-work" class="aprofile">
 <dt>{{$profile.work.0}}</dt>
 <dd>{{$profile.work.1}}</dd>
</dl>
{{/if}}

{{if $profile.education}}
<dl id="aprofile-education" class="aprofile">
 <dt>{{$profile.education.0}}</dt>
 <dd>{{$profile.education.1}}</dd>
</dl>
{{/if}}


{{if $profile.forumlist}}
<dl id="aprofile-forumlist" class="aprofile">
 <dt>{{$profile.forumlist.0}}</dt>
 <dd>{{$profile.forumlist.1}}</dd>
</dl>
{{/if}}


