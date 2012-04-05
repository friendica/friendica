<?xml version="1.0" encoding="UTF-8"?>
<XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0">
 
    <Subject>$accturi</Subject>
	<Alias>$accturi</Alias>
    <Alias>$profile_url</Alias>
 
    <Link rel="http://purl.org/zot/1.0/post/$nick" 
          href="$zot_post" />

    <Property xmlns:zot="http://purl.og/zot/1.0"
          type="http://purl.org/zot/1.0/version"
          zot:version="1" />

    <Property xmlns:zot="http://purl.og/zot/1.0"
        type="http://purl.org/zot/1.0/accept"
        zot:accept="application/atom+xml" />

    <Link rel="http://purl.org/macgirvin/dfrn/1.0"
          href="$profile_url" />
    <Link rel="http://schemas.google.com/g/2010#updates-from" 
          type="application/atom+xml" 
          href="$atom" />
    <Link rel="http://webfinger.net/rel/profile-page"
          type="text/html"
          href="$profile_url" />
    <Link rel="http://microformats.org/profile/hcard"
          type="text/html"
          href="$hcard_url" />
    <Link rel="http://portablecontacts.net/spec/1.0"
          href="$poco_url" />
    <Link rel="http://webfinger.net/rel/avatar"
          type="image/jpeg"
          href="$photo" />
	$dspr
    <Link rel="salmon" 
          href="$salmon" />
    <Link rel="http://salmon-protocol.org/ns/salmon-replies" 
          href="$salmon" />
    <Link rel="http://salmon-protocol.org/ns/salmon-mention" 
          href="$salmen" />
    <Link rel="magic-public-key" 
          href="$modexp" />
 
	<Property xmlns:mk="http://salmon-protocol.org/ns/magic-key"
          type="http://salmon-protocol.org/ns/magic-key"
          mk:key_id="1">$bigkey</Property>

</XRD>
