<div id="credits" class="generic-page-wrapper">
        {{include file="section_title.tpl"}}
        <p>{{$thanks}}</p>

        <ul class="credits">
                {{foreach $names as $name}}
                 <li>{{$name}}</li>
                {{/foreach}}
        </ul>
        <div class="clear"></div>
</div>
