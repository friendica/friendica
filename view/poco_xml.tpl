<?xml version="1.0" encoding="utf-8"?>
<response>
{{ if $response.sorted }}<sorted>$response.sorted</sorted>{{ endif }}
{{ if $response.filtered }}<filtered>$response.filtered</filtered>{{ endif }}
{{ if $response.updatedSince }}<updatedSince>$response.updatedSince</updatedSince>{{ endif }}
<startIndex>$response.startIndex</startIndex>
<itemsPerPage>$response.itemsPerPage</itemsPerPage>
<totalResults>$response.totalResults</totalResults>


{{ if $response.totalResults }}
{{ for $response.entry as $entry }}
{{ inc poco_entry_xml.tpl }}{{ endinc }}
{{ endfor }}
{{ else }}
<entry></entry>
{{ endif }}
</response>
