<div id="{{$id}}" class="input-group">
        <form action="{{$action_url}}" method="get" >
                {{strip}}
                <input type="text" name="search" id="search-text" placeholder="{{$search_label}}" value="{{$s}}" autofocus />
                {{if $searchoption}}
		<select name="search-option" id="search-options">
			<option value="fulltext">{{$searchoption.0}}</option>
			<option value="tags">{{$searchoption.1}}</option>
			<option value="contacts">{{$searchoption.2}}</option>
			{{if $searchoption.3}}<option value="forums">{{$searchoption.3}}</option>{{/if}}
		</select>
		{{/if}}

                <input type="submit" name="submit" id="search-submit" value="{{$search_label}}" />
                {{if $savedsearch}}
                <input type="submit" name="save" id="search-save" value="{{$save_label}}" />
                {{/if}}
                {{/strip}}
        </form>
</div>
