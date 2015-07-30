<div id="{{$id}}" class="input-group">
        <form action="{{$action_url}}" method="get" >
                {{strip}}
                <input type="text" name="search" id="search-text" placeholder="{{$search_label}}" value="{{$s}}" />
                <input type="submit" name="submit" id="search-submit" value="{{$search_label}}" />
                {{if $savedsearch}}
                <input type="submit" name="save" id="search-save" value="{{$save_label}}" />
                {{/if}}
                {{/strip}}
        </form>
</div>
