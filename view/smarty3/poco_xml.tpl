<?xml version="1.0" encoding="utf-8"?>
<response>
{{if $response.sorted}}<sorted>{{$response.sorted}}</sorted>{{/if}}
{{if $response.filtered}}<filtered>{{$response.filtered}}</filtered>{{/if}}
{{if $response.updatedSince}}<updatedSince>{{$response.updatedSince}}</updatedSince>{{/if}}
<startIndex>{{$response.startIndex}}</startIndex>
<itemsPerPage>{{$response.itemsPerPage}}</itemsPerPage>
<totalResults>{{$response.totalResults}}</totalResults>


{{if $response.totalResults}}
{{foreach $response.entry as $ent}}
{{include file="poco_entry_xml.tpl" entry=$ent}}
{{/foreach}}
{{else}}
<entry></entry>
{{/if}}
</response>
