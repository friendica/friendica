#!/bin/bash

MINIFY_CMD=uglifyjs

JSFILES=(
	"js/acl.js"
	"js/ajaxupload.js"
	"js/country.js"
	"js/fk.autocomplete.js"
	"js/jquery.htmlstream.js"
	"js/main.js"
	"js/webtoolkit.base64.js"
	"view/theme/frost/js/acl.js"
	"view/theme/frost/js/fk.autocomplete.js"
	"view/theme/frost/js/jquery.divgrow-1.3.1.f1.js"
	"view/theme/frost/js/main.js"
	"view/theme/frost/js/theme.js"
	"view/theme/frost-mobile/js/acl.js"
	"view/theme/frost-mobile/js/fk.autocomplete.js"
	"view/theme/frost-mobile/js/jquery.divgrow-1.3.1.f1.js"
	"view/theme/frost-mobile/js/main.js"
	"view/theme/frost-mobile/js/theme.js"
	"view/theme/decaf-mobile/js/theme.js"
)

for i in ${JSFILES[@]}
do
	echo "Processing $i"
	MINFILE=${i%%.js}.min.js
	rm $MINFILE
	$MINIFY_CMD -o $MINFILE $i
done

