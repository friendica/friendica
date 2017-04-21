
{{$default}}

<h1>{{$banner}}</h1>

<div id="profile-edit-links">
<ul>
<li><a href="profile_photo" id="profile-photo_upload-link" title="{{$profpic|escape:'html'}}">{{$profpic}}</a></li>
<li><a href="profile/{{$profile_id}}/view?tab=profile" id="profile-edit-view-link" title="{{$viewprof|escape:'html'}}">{{$viewprof}}</a></li>
<li><a href="{{$profile_clone_link}}" id="profile-edit-clone-link" title="{{$cr_prof|escape:'html'}}">{{$cl_prof}}</a></li>
<li></li>
<li><a href="{{$profile_drop_link}}" id="profile-edit-drop-link" title="{{$del_prof|escape:'html'}}" {{$disabled}} >{{$del_prof}}</a></li>

</ul>
</div>

<div id="profile-edit-links-end"></div>


<div id="profile-edit-wrapper" >
<form id="profile-edit-form" name="form1" action="profiles/{{$profile_id}}" method="post" >
<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

{{if $detailled_profile}}
{{include file="field_yesno.tpl" field=$details}}
<div id="profile-edit-profile-name-wrapper" >
<label id="profile-edit-profile-name-label" for="profile-edit-profile-name" >{{$profile_name.1}} </label>
<input type="text" size="32" name="profile_name" id="profile-edit-profile-name" value="{{$profile_name.2|escape:'html'}}" /><div class="required">*</div>
</div>
<div id="profile-edit-profile-name-end"></div>

<div id="profile-edit-name-wrapper" >
<label id="profile-edit-name-label" for="profile-edit-name" >{{$name.1}} </label>
<input type="text" size="32" name="name" id="profile-edit-name" value="{{$name.2|escape:'html'}}" />
</div>
<div id="profile-edit-name-end"></div>

<div id="profile-edit-pdesc-wrapper" >
<label id="profile-edit-pdesc-label" for="profile-edit-pdesc" >{{$pdesc.1}} </label>
<input type="text" size="32" name="pdesc" id="profile-edit-pdesc" value="{{$pdesc.1|escape:'html'}}" />
</div>
<div id="profile-edit-pdesc-end"></div>


<div id="profile-edit-gender-wrapper" >
<label id="profile-edit-gender-label" for="gender-select" >{{$lbl_gender}} </label>
{{$gender}}
</div>
<div id="profile-edit-gender-end"></div>

<div id="profile-edit-dob-wrapper" >
{{$dob}}
</div>
<div id="profile-edit-dob-end"></div>

{{$hide_friends}}

<div class="profile-edit-submit-wrapper" >
<input type="submit" name="submit" class="profile-edit-submit-button" value="{{$submit}}" />
</div>
<div class="profile-edit-submit-end"></div>


<div id="profile-edit-address-wrapper" >
<label id="profile-edit-address-label" for="profile-edit-address" >{{$address.1}} </label>
<input type="text" size="32" name="address" id="profile-edit-address" value="{{$address.2|escape:'html'}}" />
</div>
<div id="profile-edit-address-end"></div>

<div id="profile-edit-locality-wrapper" >
<label id="profile-edit-locality-label" for="profile-edit-locality" >{{$locality.1}} </label>
<input type="text" size="32" name="locality" id="profile-edit-locality" value="{{$locality.2|escape:'html'}}" />
</div>
<div id="profile-edit-locality-end"></div>


<div id="profile-edit-postal-code-wrapper" >
<label id="profile-edit-postal-code-label" for="profile-edit-postal-code" >{{$postal_code.1}} </label>
<input type="text" size="32" name="postal_code" id="profile-edit-postal-code" value="{{$postal_code.2|escape:'html'}}" />
</div>
<div id="profile-edit-postal-code-end"></div>

<div id="profile-edit-country-name-wrapper" >
<label id="profile-edit-country-name-label" for="profile-edit-country-name" >{{$country_name.1}} </label>
<select name="country_name" id="profile-edit-country-name" onChange="Fill_States('{{$region.2}}');">
<option selected="selected" >{{$country_name.2}}</option>
<option>temp</option>
</select>
</div>
<div id="profile-edit-country-name-end"></div>

<div id="profile-edit-region-wrapper" >
<label id="profile-edit-region-label" for="profile-edit-region" >{{$region.1}} </label>
<select name="region" id="profile-edit-region" onChange="Update_Globals();" >
<option selected="selected" >{{$region.2}}</option>
<option>temp</option>
</select>
</div>
<div id="profile-edit-region-end"></div>

<div id="profile-edit-hometown-wrapper" >
<label id="profile-edit-hometown-label" for="profile-edit-hometown" >{{$hometown.1}} </label>
<input type="text" size="32" name="hometown" id="profile-edit-hometown" value="{{$hometown.2|escape:'html'}}" />
</div>
<div id="profile-edit-hometown-end"></div>

<div class="profile-edit-submit-wrapper" >
<input type="submit" name="submit" class="profile-edit-submit-button" value="{{$submit}}" />
</div>
<div class="profile-edit-submit-end"></div>

<div id="profile-edit-marital-wrapper" >
<label id="profile-edit-marital-label" for="profile-edit-marital" >{{$lbl_marital}} </label>
{{$marital}}
</div>
<label id="profile-edit-with-label" for="profile-edit-with" > {{$with.1}} </label>
<input type="text" size="32" name="with" id="profile-edit-with" title="{{$with.3}}" value="{{$with.2}}" />
<label id="profile-edit-howlong-label" for="profile-edit-howlong" > {{$howlong.1}} </label>
<input type="text" size="32" name="howlong" id="profile-edit-howlong" title="{{$howlong.1}}" value="{{$howlong.1|escape:'html'}}" />

<div id="profile-edit-marital-end"></div>

<div id="profile-edit-sexual-wrapper" >
<label id="profile-edit-sexual-label" for="sexual-select" >{{$lbl_sexual}} </label>
{{$sexual}}
</div>
<div id="profile-edit-sexual-end"></div>



<div id="profile-edit-homepage-wrapper" >
<label id="profile-edit-homepage-label" for="profile-edit-homepage" >{{$homepage.1}} </label>
<input type="url" size="32" name="homepage" id="profile-edit-homepage" value="{{$homepage.2|escape:'html'}}" />
</div>
<div id="profile-edit-homepage-end"></div>

<div id="profile-edit-xmpp-wrapper" >
<label id="profile-edit-xmpp-label" for="profile-edit-xmpp" >{{$xmpp.1}} </label>
<input type="text" size="32" name="xmpp" id="profile-edit-xmpp" title="{{$lbl_ex2}}" value="{{$xmpp.2|escape:'html'}}" />
</div><div id="profile-edit-xmpp-desc">{{$xmpp.3}}</div>
<div id="profile-edit-xmpp-end"></div>


<div id="profile-edit-politic-wrapper" >
<label id="profile-edit-politic-label" for="profile-edit-politic" >{{$politic.1}} </label>
<input type="text" size="32" name="politic" id="profile-edit-politic" value="{{$politic.2|escape:'html'}}" />
</div>
<div id="profile-edit-politic-end"></div>

<div id="profile-edit-religion-wrapper" >
<label id="profile-edit-religion-label" for="profile-edit-religion" >{{$religion.1}} </label>
<input type="text" size="32" name="religion" id="profile-edit-religion" value="{{$religion.2|escape:'html'}}" />
</div>
<div id="profile-edit-religion-end"></div>

<div id="profile-edit-pubkeywords-wrapper" >
<label id="profile-edit-pubkeywords-label" for="profile-edit-pubkeywords" >{{$pub_keywords.1}} </label>
<input type="text" size="32" name="pub_keywords" id="profile-edit-pubkeywords" title="{{$lbl_ex2}}" value="{{$pub_keywords.2|escape:'html'}}" />
</div><div id="profile-edit-pubkeywords-desc">{{$pub_keywords.3}}</div>
<div id="profile-edit-pubkeywords-end"></div>

<div id="profile-edit-prvkeywords-wrapper" >
<label id="profile-edit-prvkeywords-label" for="profile-edit-prvkeywords" >{{$prv_keywords.1}} </label>
<input type="text" size="32" name="prv_keywords" id="profile-edit-prvkeywords" title="{{$lbl_ex2}}" value="{{$prv_keywords.2|escape:'html'}}" />
</div><div id="profile-edit-prvkeywords-desc">{{$prv_keywords.3}}</div>
<div id="profile-edit-prvkeywords-end"></div>


<div class="profile-edit-submit-wrapper" >
<input type="submit" name="submit" class="profile-edit-submit-button" value="{{$submit}}" />
</div>
<div class="profile-edit-submit-end"></div>

<div id="about-jot-wrapper" >
<p id="about-jot-desc" >
{{$about.1}}
</p>

<textarea rows="10" cols="72" id="profile-about-text" name="about" >{{$about.2}}</textarea>

</div>
<div id="about-jot-end"></div>


<div id="interest-jot-wrapper" >
<p id="interest-jot-desc" >
{{$interest.1}}
</p>

<textarea rows="10" cols="72" id="interest-jot-text" name="interest" >{{$interest.2}}</textarea>

</div>
<div id="interest-jot-end"></div>


<div id="likes-jot-wrapper" >
<p id="likes-jot-desc" >
{{$likes.1}}
</p>

<textarea rows="10" cols="72" id="likes-jot-text" name="likes" >{{$likes.2}}</textarea>

</div>
<div id="likes-jot-end"></div>


<div id="dislikes-jot-wrapper" >
<p id="dislikes-jot-desc" >
{{$dislikes.1}}
</p>

<textarea rows="10" cols="72" id="dislikes-jot-text" name="dislikes" >{{$dislikes.2}}</textarea>

</div>
<div id="dislikes-jot-end"></div>


<div id="contact-jot-wrapper" >
<p id="contact-jot-desc" >
{{$contact.1}}
</p>

<textarea rows="10" cols="72" id="contact-jot-text" name="contact" >{{$contact.2}}</textarea>

</div>
<div id="contact-jot-end"></div>


<div class="profile-edit-submit-wrapper" >
<input type="submit" name="submit" class="profile-edit-submit-button" value="{{$submit}}" />
</div>
<div class="profile-edit-submit-end"></div>


<div id="music-jot-wrapper" >
<p id="music-jot-desc" >
{{$music.1}}
</p>

<textarea rows="10" cols="72" id="music-jot-text" name="music" >{{$music.2}}</textarea>

</div>
<div id="music-jot-end"></div>

<div id="book-jot-wrapper" >
<p id="book-jot-desc" >
{{$book.1}}
</p>

<textarea rows="10" cols="72" id="book-jot-text" name="book" >{{$book.2}}</textarea>

</div>
<div id="book-jot-end"></div>



<div id="tv-jot-wrapper" >
<p id="tv-jot-desc" >
{{$tv.1}} 
</p>

<textarea rows="10" cols="72" id="tv-jot-text" name="tv" >{{$tv.2}}</textarea>

</div>
<div id="tv-jot-end"></div>



<div id="film-jot-wrapper" >
<p id="film-jot-desc" >
{{$film.1}}
</p>

<textarea rows="10" cols="72" id="film-jot-text" name="film" >{{$film.2}}</textarea>

</div>
<div id="film-jot-end"></div>


<div class="profile-edit-submit-wrapper" >
<input type="submit" name="submit" class="profile-edit-submit-button" value="{{$submit}}" />
</div>
<div class="profile-edit-submit-end"></div>


<div id="romance-jot-wrapper" >
<p id="romance-jot-desc" >
{{$romance.1}}
</p>

<textarea rows="10" cols="72" id="romance-jot-text" name="romance" >{{$romance.2}}</textarea>

</div>
<div id="romance-jot-end"></div>



<div id="work-jot-wrapper" >
<p id="work-jot-desc" >
{{$work.1}}
</p>

<textarea rows="10" cols="72" id="work-jot-text" name="work" >{{$work.2}}</textarea>

</div>
<div id="work-jot-end"></div>



<div id="education-jot-wrapper" >
<p id="education-jot-desc" >
{{$education.1}} 
</p>

<textarea rows="10" cols="72" id="education-jot-text" name="education" >{{$education.2}}</textarea>

</div>
<div id="education-jot-end"></div>



<div class="profile-edit-submit-wrapper" >
<input type="submit" name="submit" class="profile-edit-submit-button" value="{{$submit}}" />
</div>
<div class="profile-edit-submit-end"></div>

{{else}}
{{if $personal_account}}
{{include file="field_yesno.tpl" field=$details}}
{{/if}}
<div id="profile-edit-profile-name-wrapper" >
<label id="profile-edit-profile-name-label" for="profile-edit-profile-name" >{{$profile_name.1}} </label>
<input type="text" size="32" name="profile_name" id="profile-edit-profile-name" value="{{$profile_name.2|escape:'html'}}" /><div class="required">*</div>
</div>
<div id="profile-edit-profile-name-end"></div>

<div id="profile-edit-name-wrapper" >
<label id="profile-edit-name-label" for="profile-edit-name" >{{$name.1}} </label>
<input type="text" size="32" name="name" id="profile-edit-name" value="{{$name.2|escape:'html'}}" />
</div>
<div id="profile-edit-name-end"></div>

{{if $personal_account}}
<div id="profile-edit-gender-wrapper" >
<label id="profile-edit-gender-label" for="gender-select" >{{$lbl_gender}} </label>
{{$gender}}
</div>
<div id="profile-edit-gender-end"></div>

<div id="profile-edit-dob-wrapper" >
{{$dob}}
</div>
<div id="profile-edit-dob-end"></div>
{{/if}}

<div id="profile-edit-homepage-wrapper" >
<label id="profile-edit-homepage-label" for="profile-edit-homepage" >{{$homepage.1}} </label>
<input type="url" size="32" name="homepage" id="profile-edit-homepage" value="{{$homepage.2|escape:'html'}}" />
</div>
<div id="profile-edit-homepage-end"></div>

<div id="profile-edit-xmpp-wrapper" >
<label id="profile-edit-xmpp-label" for="profile-edit-xmpp" >{{$xmpp.1}} </label>
<input type="text" size="32" name="xmpp" id="profile-edit-xmpp" title="{{$lbl_ex2}}" value="{{$xmpp.2|escape:'html'}}" />
</div><div id="profile-edit-xmpp-desc">{{$xmpp.3}}</div>
<div id="profile-edit-xmpp-end"></div>


{{$hide_friends}}

<div id="profile-edit-address-wrapper" >
<label id="profile-edit-address-label" for="profile-edit-address" >{{$address.1}} </label>
<input type="text" size="32" name="address" id="profile-edit-address" value="{{$address.2|escape:'html'}}" />
</div>
<div id="profile-edit-address-end"></div>

<div id="profile-edit-locality-wrapper" >
<label id="profile-edit-locality-label" for="profile-edit-locality" >{{$locality.1}} </label>
<input type="text" size="32" name="locality" id="profile-edit-locality" value="{{$locality.2|escape:'html'}}" />
</div>
<div id="profile-edit-locality-end"></div>


<div id="profile-edit-postal-code-wrapper" >
<label id="profile-edit-postal-code-label" for="profile-edit-postal-code" >{{$postal_code.1}} </label>
<input type="text" size="32" name="postal_code" id="profile-edit-postal-code" value="{{$postal_code.2|escape:'html'}}" />
</div>
<div id="profile-edit-postal-code-end"></div>

<div id="profile-edit-country-name-wrapper" >
<label id="profile-edit-country-name-label" for="profile-edit-country-name" >{{$country_name.1}} </label>
<select name="country_name" id="profile-edit-country-name" onChange="Fill_States('{{$region.2}}');">
<option selected="selected" >{{$country_name.1}}</option>
<option>temp</option>
</select>
</div>
<div id="profile-edit-country-name-end"></div>

<div id="profile-edit-region-wrapper" >
<label id="profile-edit-region-label" for="profile-edit-region" >{{$region.1}} </label>
<select name="region" id="profile-edit-region" onChange="Update_Globals();" >
<option selected="selected" >{{$region.2}}</option>
<option>temp</option>
</select>
</div>
<div id="profile-edit-region-end"></div>

<div id="profile-edit-pubkeywords-wrapper" >
<label id="profile-edit-pubkeywords-label" for="profile-edit-pubkeywords" >{{$pub_keywords.1}} </label>
<input type="text" size="32" name="pub_keywords" id="profile-edit-pubkeywords" title="{{$lbl_ex2}}" value="{{$pub_keywords.2|escape:'html'}}" />
</div><div id="profile-edit-pubkeywords-desc">{{$pub_keywords.3}}</div>
<div id="profile-edit-pubkeywords-end"></div>

<div id="profile-edit-prvkeywords-wrapper" >
<label id="profile-edit-prvkeywords-label" for="profile-edit-prvkeywords" >{{$prv_keywords.1}} </label>
<input type="text" size="32" name="prv_keywords" id="profile-edit-prvkeywords" title="{{$lbl_ex2}}" value="{{$prv_keywords.2|escape:'html'}}" />
</div><div id="profile-edit-prvkeywords-desc">{{$prv_keywords.3}}</div>
<div id="profile-edit-prvkeywords-end"></div>

<div id="about-jot-wrapper" >
<p id="about-jot-desc" >
{{$about.1}}
</p>

<textarea rows="10" cols="72" id="profile-about-text" name="about" >{{$about.2}}</textarea>

</div>
<div id="about-jot-end"></div>

<div class="profile-edit-submit-wrapper" >
<input type="submit" name="submit" class="profile-edit-submit-button" value="{{$submit}}" />
</div>
<div class="profile-edit-submit-end"></div>

        <input type="hidden" name="pdesc" id="profile-edit-pdesc" value="{{$pdesc.2}}" />
        <input type="hidden" id="contact-jot-text" name="contact" value="{{$contact.2}}" />
        <input type="hidden" name="hometown" id="profile-edit-hometown" value="{{$hometown.2}}" />
        <input type="hidden" name="politic" id="profile-edit-politic" value="{{$politic.2}}" />
        <input type="hidden" name="religion" id="profile-edit-religion" value="{{$religion.2}}" />
        <input type="hidden" id="likes-jot-text" name="likes" value="{{$likes.2}}" />
        <input type="hidden" id="dislikes-jot-text" name="dislikes" value="{{$dislikes.2}}" />
        <input type="hidden" name="with" id="profile-edit-with" value="{{$with.2}}" />
        <input type="hidden" name="howlong" id="profile-edit-howlong" value="{{$howlong.2}}" />
        <input type="hidden" id="romance-jot-text" name="romance" value="{{$romance.2}}" />
        <input type="hidden" id="work-jot-text" name="work" value="{{$work.2}}" />
        <input type="hidden" id="education-jot-text" name="education" value="{{$education.2}}" />
        <input type="hidden" id="interest-jot-text" name="interest" value="{{$interest.2}}" />
        <input type="hidden" id="music-jot-text" name="music" value="{{$music.2}}" />
        <input type="hidden" id="book-jot-text" name="book" value="{{$book.2}}" />
        <input type="hidden" id="tv-jot-text" name="tv" value="{{$tv.2}}" />
        <input type="hidden" id="film-jot-text" name="film" value="{{$film.2}}" />

{{/if}}
</form>
</div>
<script type="text/javascript">Fill_Country('{{$country_name.2}}');Fill_States('{{$region.2}}');</script>
