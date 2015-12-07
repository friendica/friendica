<script>
        $(document).ready(function() {
                
                $('.toggle-section-content').hide();
                $('.js-section-toggler').click(function(){
                        $('.toggle-section-content').hide();
                        $(this).next('.toggle-section-content').toggle();
                });

        });
</script>

<h1>{{$banner}}</h1>

<div id="profile-edit-links">
<ul>
  <li><a class="btn" href="profile/{{$profile_id}}/view?tab=profile" id="profile-edit-view-link" title="{{$viewprof}}">{{$viewprof}}</a></li>
  {{if $multi_profiles}}
    <li><a class="btn" href="{{$profile_clone_link}}" id="profile-edit-clone-link" title="{{$cr_prof}}">{{$cl_prof}}</a></li>
    <li><a class="btn" href="{{$profile_drop_link}}" id="profile-edit-drop-link" title="{{$del_prof}}" {{$disabled}} >{{$del_prof}}</a></li>
  {{/if}}
</ul>
</div>
<div id="profile-edit-links-end"></div>

{{$default}}

<div id="profile-edit-wrapper" >

<form enctype="multipart/form-data" action="profile_photo" method="post">
  <input type='hidden' name='form_security_token' value='{{$form_security_token_photo}}'>
  <input type="hidden" name="profile" value="{{$profile_name}}" />
  
  <!-- Profile picture -->
{{if $detailled_profile}}
  <div class="toggle-section js-toggle-section">
    <a class="section-caption js-section-toggler" href="javascript:;">{{$lbl_picture_section}} &raquo;</a>
    <div class="js-section toggle-section-content hidden">
{{/if}}
      
      <div id="profile-photo-upload-wrapper">
        <label id="profile-photo-upload-label" for="profile-photo-upload">{{$lbl_profile_photo}}:</label>
        <input name="userfile" type="file" id="profile-photo-upload" size="48" />
      </div>

      <div class="profile-edit-submit-wrapper" >
        <input type="submit" name="submit" class="profile-edit-submit-button" value="{{$submit}}" />
      </div>
      <div class="profile-edit-submit-end"></div>
    
{{if $detailled_profile}}
    </div>
  </div>
{{/if}}
</form>

<form id="profile-edit-form" name="form1" action="profiles/{{$profile_id}}" method="post" >
  <input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

{{if $detailled_profile}}
  <!-- Basic information -->
  <div class="toggle-section js-toggle-section">
    <a class="section-caption js-section-toggler" href="javascript:;">{{$lbl_basic_section}} &raquo;</a>
    <div class="js-section toggle-section-content hidden">
      
      {{include file="field_yesno.tpl" field=$details}}

      {{if $multi_profiles}}
        <div id="profile-edit-profile-name-wrapper" >
          <label id="profile-edit-profile-name-label" for="profile-edit-profile-name" >{{$lbl_profname}} </label>
          <input type="text" size="32" name="profile_name" id="profile-edit-profile-name" value="{{$profile_name}}" /><div class="required">*</div>
        </div>
        <div id="profile-edit-profile-name-end"></div>
      {{else}}
        <input type="hidden" name="profile_name" id="profile-edit-profile-name" value="{{$profile_name}}" />
      {{/if}}
      
      <div id="profile-edit-name-wrapper" >
        <label id="profile-edit-name-label" for="profile-edit-name" >{{$lbl_fullname}} </label>
        <input type="text" size="32" name="name" id="profile-edit-name" value="{{$name}}" />
      </div>
      <div id="profile-edit-name-end"></div>
      
      <div id="profile-edit-gender-wrapper" >
        <label id="profile-edit-gender-label" for="gender-select" >{{$lbl_gender}} </label>
        {{$gender}}
      </div>
      <div id="profile-edit-gender-end"></div>
      
      <div id="profile-edit-pdesc-wrapper" >
        <label id="profile-edit-pdesc-label" for="profile-edit-pdesc" >{{$lbl_title}} </label>
        <input type="text" size="32" name="pdesc" id="profile-edit-pdesc" value="{{$pdesc}}" />
      </div>
      <div id="profile-edit-pdesc-end"></div>
      
      <div id="profile-edit-homepage-wrapper" >
        <label id="profile-edit-homepage-label" for="profile-edit-homepage" >{{$lbl_homepage}} </label>
        <input type="text" size="32" name="homepage" id="profile-edit-homepage" value="{{$homepage}}" />
      </div>
      <div id="profile-edit-homepage-end"></div>
      
      <div id="profile-edit-dob-wrapper" >
        <label id="profile-edit-dob-label" for="dob-select" >{{$lbl_bd}}</label>
        <div id="profile-edit-dob" >
          {{$dob}} {{$age}}
        </div>
      </div>
      <div id="profile-edit-dob-end"></div>
      
      {{$hide_friends}}
      
      <div id="about-jot-wrapper">
        <div id="about-jot-desc">{{$lbl_about}}</div>
        <textarea rows="10" cols="72" id="profile-about-text" name="about" style="width:599px;">{{$about}}</textarea>
      </div>
      <div id="about-jot-end"></div>
      
      <div id="contact-jot-wrapper" >
        <div id="contact-jot-desc">{{$lbl_social}}</div>
        <textarea rows="10" cols="72" id="contact-jot-text" name="contact" style="width:599px;">{{$contact}}</textarea>
      </div>
      <div id="contact-jot-end"></div>
      
      <div id="profile-edit-pubkeywords-wrapper" >
        <label id="profile-edit-pubkeywords-label" for="profile-edit-pubkeywords" >{{$lbl_pubkey}} </label>
        <input type="text" size="32" name="pub_keywords" id="profile-edit-pubkeywords" title="{{$lbl_ex2}}" value="{{$pub_keywords}}" />
      </div>
      <div id="profile-edit-pubkeywords-desc">{{$lbl_pubdsc}}</div>
      <div id="profile-edit-pubkeywords-end"></div>
      
      <div id="profile-edit-prvkeywords-wrapper" >
        <label id="profile-edit-prvkeywords-label" for="profile-edit-prvkeywords" >{{$lbl_prvkey}} </label>
        <input type="text" size="32" name="prv_keywords" id="profile-edit-prvkeywords" title="{{$lbl_ex2}}" value="{{$prv_keywords}}" />
      </div>
      <div id="profile-edit-prvkeywords-desc">{{$lbl_prvdsc}}</div>
      <div id="profile-edit-prvkeywords-end"></div>
      
      <div class="profile-edit-submit-wrapper" >
        <input type="submit" name="submit" class="profile-edit-submit-button" value="{{$submit}}" />
      </div>
      <div class="profile-edit-submit-end"></div>
    
    </div>
  </div>
  
  <!-- About you -->
  <div class="toggle-section js-toggle-section">
    <a class="section-caption js-section-toggler" href="javascript:;">{{$lbl_about_section}} &raquo;</a>
    <div class="js-section toggle-section-content hidden">
      
      <div><b>{{$lbl_location_section}}</b></div>
      
      <div id="profile-edit-address-wrapper" >
        <label id="profile-edit-address-label" for="profile-edit-address" >{{$lbl_address}} </label>
        <input type="text" size="32" name="address" id="profile-edit-address" value="{{$address}}" />
      </div>
      <div id="profile-edit-address-end"></div>
      
      <div id="profile-edit-locality-wrapper" >
        <label id="profile-edit-locality-label" for="profile-edit-locality" >{{$lbl_city}} </label>
        <input type="text" size="32" name="locality" id="profile-edit-locality" value="{{$locality}}" />
      </div>
      <div id="profile-edit-locality-end"></div>
      
      <div id="profile-edit-postal-code-wrapper" >
        <label id="profile-edit-postal-code-label" for="profile-edit-postal-code" >{{$lbl_zip}} </label>
        <input type="text" size="32" name="postal_code" id="profile-edit-postal-code" value="{{$postal_code}}" />
      </div>
      <div id="profile-edit-postal-code-end"></div>
      
      <div id="profile-edit-country-name-wrapper" >
        <label id="profile-edit-country-name-label" for="profile-edit-country-name" >{{$lbl_country}} </label>
        <select name="country_name" id="profile-edit-country-name" onChange="Fill_States('{{$region}}');">
          <option selected="selected" >{{$country_name}}</option>
        </select>
      </div>
      <div id="profile-edit-country-name-end"></div>
      
      <div id="profile-edit-region-wrapper" >
        <label id="profile-edit-region-label" for="profile-edit-region" >{{$lbl_region}} </label>
        <select name="region" id="profile-edit-region" onChange="Update_Globals();" >
          <option selected="selected" >{{$region}}</option>
        </select>
      </div>
      <div id="profile-edit-region-end"></div>
      
      <div id="profile-edit-hometown-wrapper" >
        <label id="profile-edit-hometown-label" for="profile-edit-hometown" >{{$lbl_hometown}} </label>
        <input type="text" size="32" name="hometown" id="profile-edit-hometown" value="{{$hometown}}" />
      </div>
      <div id="profile-edit-hometown-end"></div>
      
      <br>
      
      <div><b>{{$lbl_preferences_section}}</b></div>
      
      <div id="profile-edit-sexual-wrapper" >
        <label id="profile-edit-sexual-label" for="sexual-select" >{{$lbl_sexual}} </label>
        {{$sexual}}
      </div>
      <div id="profile-edit-sexual-end"></div>
      
      <div id="profile-edit-politic-wrapper" >
        <label id="profile-edit-politic-label" for="profile-edit-politic" >{{$lbl_politic}} </label>
        <input type="text" size="32" name="politic" id="profile-edit-politic" value="{{$politic}}" />
      </div>
      <div id="profile-edit-politic-end"></div>
      
      <div id="profile-edit-religion-wrapper" >
        <label id="profile-edit-religion-label" for="profile-edit-religion" >{{$lbl_religion}} </label>
        <input type="text" size="32" name="religion" id="profile-edit-religion" value="{{$religion}}" />
      </div>
      <div id="profile-edit-religion-end"></div>
      
      <div id="likes-jot-wrapper">
        <div id="likes-jot-desc">{{$lbl_likes}}</div>
        <textarea rows="10" cols="72" id="likes-jot-text" name="likes" style="width:599px;">{{$likes}}</textarea>
      </div>
      <div id="likes-jot-end"></div>
      
      <div id="dislikes-jot-wrapper">
        <div id="dislikes-jot-desc">{{$lbl_dislikes}}</div>
        <textarea rows="10" cols="72" id="dislikes-jot-text" name="dislikes" style="width:599px;">{{$dislikes}}</textarea>
      </div>
      <div id="dislikes-jot-end"></div>
      
      <div class="profile-edit-submit-wrapper" >
        <input type="submit" name="submit" class="profile-edit-submit-button" value="{{$submit}}" />
      </div>
      <div class="profile-edit-submit-end"></div>
      
    </div>
  </div>
  
  <!-- Status -->
  <div class="toggle-section js-toggle-section">
    <a class="section-caption js-section-toggler" href="javascript:;">{{$lbl_status_section}} &raquo;</a>
    <div class="js-section toggle-section-content hidden">
      
      <div id="profile-edit-marital-wrapper" >
        <label id="profile-edit-marital-label" for="profile-edit-marital" >{{$lbl_marital}} </label>
        {{$marital}}
      </div>
      <label id="profile-edit-with-label" for="profile-edit-with" > {{$lbl_with}} </label>
      <input type="text" size="32" name="with" id="profile-edit-with" title="{{$lbl_ex1}}" value="{{$with}}" />
      <label id="profile-edit-howlong-label" for="profile-edit-howlong" > {{$lbl_howlong}} </label>
      <input type="text" size="32" name="howlong" id="profile-edit-howlong" title="{{$lbl_howlong}}" value="{{$howlong}}" />
      <div id="profile-edit-marital-end"></div>
      
      <div id="romance-jot-wrapper" >
        <div id="romance-jot-desc">{{$lbl_love}}</div>
        <textarea rows="10" cols="72" id="romance-jot-text" name="romance" style="width:599px;">{{$romance}}</textarea>
      </div>
      <div id="romance-jot-end"></div>
      
      <div id="work-jot-wrapper">
        <div id="work-jot-desc">{{$lbl_work}}</div>
        <textarea rows="10" cols="72" id="work-jot-text" name="work" style="width:599px;">{{$work}}</textarea>
      </div>
      <div id="work-jot-end"></div>
      
      <div id="education-jot-wrapper" >
        <div id="education-jot-desc">{{$lbl_school}}</div>
        <textarea rows="10" cols="72" id="education-jot-text" name="education" style="width:599px;">{{$education}}</textarea>
      </div>
      <div id="education-jot-end"></div>
      
      <div class="profile-edit-submit-wrapper" >
        <input type="submit" name="submit" class="profile-edit-submit-button" value="{{$submit}}" />
      </div>
      <div class="profile-edit-submit-end"></div>
      
    </div>
  </div>
  
  <!-- Interests -->
  <div class="toggle-section js-toggle-section">
    <a class="section-caption js-section-toggler" href="javascript:;">{{$lbl_interests_section}} &raquo;</a>
    <div class="js-section toggle-section-content hidden">
      
      <div id="interest-jot-wrapper">
        <div id="interest-jot-desc">{{$lbl_hobbies}}</div>
        <textarea rows="10" cols="72" id="interest-jot-text" name="interest" style="width:599px;">{{$interest}}</textarea>
      </div>
      <div id="interest-jot-end"></div>
      
      <div id="music-jot-wrapper">
        <div id="music-jot-desc">{{$lbl_music}}</div>
        <textarea rows="10" cols="72" id="music-jot-text" name="music" style="width:599px;">{{$music}}</textarea>
      </div>
      <div id="music-jot-end"></div>

      <div id="book-jot-wrapper">
        <div id="book-jot-desc">{{$lbl_book}}</div>
        <textarea rows="10" cols="72" id="book-jot-text" name="book" style="width:599px;">{{$book}}</textarea>
      </div>
      <div id="book-jot-end"></div>
      
      <div id="tv-jot-wrapper">
        <div id="tv-jot-desc">{{$lbl_tv}}</div>
        <textarea rows="10" cols="72" id="tv-jot-text" name="tv" style="width:599px;">{{$tv}}</textarea>
      </div>
      <div id="tv-jot-end"></div>
      
      <div id="film-jot-wrapper">
        <div id="film-jot-desc">{{$lbl_film}}</div>
        <textarea rows="10" cols="72" id="film-jot-text" name="film" style="width:599px;">{{$film}}</textarea>
      </div>
      <div id="film-jot-end"></div>
      
      <div class="profile-edit-submit-wrapper" >
        <input type="submit" name="submit" class="profile-edit-submit-button" value="{{$submit}}" />
      </div>
      <div class="profile-edit-submit-end"></div>
      
    </div>
  </div>
{{else}}

{{if $personal_account}}
{{include file="field_yesno.tpl" field=$details}}
{{/if}}
<div id="profile-edit-profile-name-wrapper" >
<label id="profile-edit-profile-name-label" for="profile-edit-profile-name" >{{$lbl_profname}} </label>
<input type="text" size="32" name="profile_name" id="profile-edit-profile-name" value="{{$profile_name|escape:'html'}}" /><div class="required">*</div>
</div>
<div id="profile-edit-profile-name-end"></div>

<div id="profile-edit-name-wrapper" >
<label id="profile-edit-name-label" for="profile-edit-name" >{{$lbl_fullname}} </label>
<input type="text" size="32" name="name" id="profile-edit-name" value="{{$name|escape:'html'}}" />
</div>
<div id="profile-edit-name-end"></div>

{{if $personal_account}}
<div id="profile-edit-gender-wrapper" >
<label id="profile-edit-gender-label" for="gender-select" >{{$lbl_gender}} </label>
{{$gender}}
</div>
<div id="profile-edit-gender-end"></div>

<div id="profile-edit-dob-wrapper" >
<label id="profile-edit-dob-label" for="dob-select" >{{$lbl_bd}} </label>
<div id="profile-edit-dob" >
{{$dob}} {{$age}}
</div>
</div>
<div id="profile-edit-dob-end"></div>
{{/if}}

      <div id="profile-edit-homepage-wrapper" >
        <label id="profile-edit-homepage-label" for="profile-edit-homepage" >{{$lbl_homepage}} </label>
        <input type="text" size="32" name="homepage" id="profile-edit-homepage" value="{{$homepage}}" />
      </div>
      <div id="profile-edit-homepage-end"></div>

{{$hide_friends}}

<div id="profile-edit-address-wrapper" >
<label id="profile-edit-address-label" for="profile-edit-address" >{{$lbl_address}} </label>
<input type="text" size="32" name="address" id="profile-edit-address" value="{{$address|escape:'html'}}" />
</div>
<div id="profile-edit-address-end"></div>

<div id="profile-edit-locality-wrapper" >
<label id="profile-edit-locality-label" for="profile-edit-locality" >{{$lbl_city}} </label>
<input type="text" size="32" name="locality" id="profile-edit-locality" value="{{$locality|escape:'html'}}" />
</div>
<div id="profile-edit-locality-end"></div>


<div id="profile-edit-postal-code-wrapper" >
<label id="profile-edit-postal-code-label" for="profile-edit-postal-code" >{{$lbl_zip}} </label>
<input type="text" size="32" name="postal_code" id="profile-edit-postal-code" value="{{$postal_code|escape:'html'}}" />
</div>
<div id="profile-edit-postal-code-end"></div>

<div id="profile-edit-country-name-wrapper" >
<label id="profile-edit-country-name-label" for="profile-edit-country-name" >{{$lbl_country}} </label>
<select name="country_name" id="profile-edit-country-name" onChange="Fill_States('{{$region}}');">
<option selected="selected" >{{$country_name}}</option>
<option>temp</option>
</select>
</div>
<div id="profile-edit-country-name-end"></div>
<div id="profile-edit-region-wrapper" >
<label id="profile-edit-region-label" for="profile-edit-region" >{{$lbl_region}} </label>
<select name="region" id="profile-edit-region" onChange="Update_Globals();" >
<option selected="selected" >{{$region}}</option>
<option>temp</option>
</select>
</div>
<div id="profile-edit-region-end"></div>

<div id="profile-edit-pubkeywords-wrapper" >
<label id="profile-edit-pubkeywords-label" for="profile-edit-pubkeywords" >{{$lbl_pubkey}} </label>
<input type="text" size="32" name="pub_keywords" id="profile-edit-pubkeywords" title="{{$lbl_ex2}}" value="{{$pub_keywords|escape:'html'}}" />
</div><div id="profile-edit-pubkeywords-desc">{{$lbl_pubdsc}}</div>
<div id="profile-edit-pubkeywords-end"></div>

<div id="profile-edit-prvkeywords-wrapper" >
<label id="profile-edit-prvkeywords-label" for="profile-edit-prvkeywords" >{{$lbl_prvkey}} </label>
<input type="text" size="32" name="prv_keywords" id="profile-edit-prvkeywords" title="{{$lbl_ex2}}" value="{{$prv_keywords|escape:'html'}}" />
</div><div id="profile-edit-prvkeywords-desc">{{$lbl_prvdsc}}</div>
<div id="profile-edit-prvkeywords-end"></div>

<div id="about-jot-wrapper" >
<p id="about-jot-desc" >
{{$lbl_about}}
</p>

<textarea rows="10" cols="72" id="profile-about-text" name="about" >{{$about}}</textarea>

</div>
<div id="about-jot-end"></div>

<div class="profile-edit-submit-wrapper" >
<input type="submit" name="submit" class="profile-edit-submit-button" value="{{$submit}}" />
</div>
<div class="profile-edit-submit-end"></div>

        <input type="hidden" name="pdesc" id="profile-edit-pdesc" value="{{$pdesc}}" />
        <input type="hidden" id="contact-jot-text" name="contact" value="{{$contact}}" />
        <input type="hidden" name="hometown" id="profile-edit-hometown" value="{{$hometown}}" />
        <input type="hidden" name="politic" id="profile-edit-politic" value="{{$politic}}" />
        <input type="hidden" name="religion" id="profile-edit-religion" value="{{$religion}}" />
        <input type="hidden" id="likes-jot-text" name="likes" value="{{$likes}}" />
        <input type="hidden" id="dislikes-jot-text" name="dislikes" value="{{$dislikes}}" />
        <input type="hidden" name="with" id="profile-edit-with" value="{{$with}}" />
        <input type="hidden" name="howlong" id="profile-edit-howlong" value="{{$howlong}}" />
        <input type="hidden" id="romance-jot-text" name="romance" value="{{$romance}}" />
        <input type="hidden" id="work-jot-text" name="work" value="{{$work}}" />
        <input type="hidden" id="education-jot-text" name="education" value="{{$education}}" />
        <input type="hidden" id="interest-jot-text" name="interest" value="{{$interest}}" />
        <input type="hidden" id="music-jot-text" name="music" value="{{$music}}" />
        <input type="hidden" id="book-jot-text" name="book" value="{{$book}}" />
        <input type="hidden" id="tv-jot-text" name="tv" value="{{$tv}}" />
        <input type="hidden" id="film-jot-text" name="film" value="{{$film}}" />

{{/if}}
  
</form>
</div>
<script type="text/javascript">Fill_Country('{{$country_name}}');Fill_States('{{$region}}');</script>
