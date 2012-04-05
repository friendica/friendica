<?xml version='1.0' encoding='UTF-8'?>
<XRD xmlns='http://docs.oasis-open.org/ns/xri/xrd-1.0'
     xmlns:hm='http://host-meta.net/xrd/1.0'>
 
    <hm:Host>$zhost</hm:Host>
 
    <Link rel='lrdd' template='$domain/xrd/?uri={uri}' />
    <Link rel='acct-mgmt' href='$domain/amcd' />
    <Link rel='http://services.mozilla.com/amcd/0.1' href='$domain/amcd' />
	<Link rel="http://oexchange.org/spec/0.8/rel/resident-target" type="application/xrd+xml" 
        href="$domain/oexchange/xrd" />

    <Link rel="http://purl.org/zot/1.0/post" 
        href="$zot_post" />

    <Property xmlns:zot="http://purl.og/zot/1.0"
        type="http://purl.org/zot/1.0/version"
        zot:version="1" />

    <Property xmlns:zot="http://purl.og/zot/1.0"
        type="http://purl.org/zot/1.0/accept"
        zot:accept="application/atom+xml" />

    <Property xmlns:mk="http://salmon-protocol.org/ns/magic-key"
        type="http://salmon-protocol.org/ns/magic-key"
        mk:key_id="1">$bigkey</Property>


</XRD>
