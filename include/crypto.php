<?php

require_once('library/ASNValue.class.php');
require_once('library/asn1.php');

// supported algorithms are 'sha256', 'sha1'

function rsa_sign($data,$key,$alg = 'sha256') {

	$sig = '';
	if (version_compare(PHP_VERSION, '5.3.0', '>=') || $alg === 'sha1') {
		openssl_sign($data,$sig,$key,(($alg == 'sha1') ? OPENSSL_ALGO_SHA1 : $alg));
    }
    else {
		if(strlen($key) < 1024 || extension_loaded('gmp')) {
			require_once('library/phpsec/Crypt/RSA.php');
			$rsa = new CRYPT_RSA();
			$rsa->signatureMode = CRYPT_RSA_SIGNATURE_PKCS1;
			$rsa->setHash($alg);
			$rsa->loadKey($key);
			$sig = $rsa->sign($data);
		}
		else {
			logger('rsa_sign: insecure algorithm used. Please upgrade PHP to 5.3');
			openssl_private_encrypt(hex2bin('3031300d060960864801650304020105000420') . hash('sha256',$data,true), $sig, $key);
		}
	}
	return $sig;
}

function rsa_verify($data,$sig,$key,$alg = 'sha256') {

	if (version_compare(PHP_VERSION, '5.3.0', '>=') || $alg === 'sha1') {
		$verify = openssl_verify($data,$sig,$key,(($alg == 'sha1') ? OPENSSL_ALGO_SHA1 : $alg));
    }
    else {
		if(strlen($key) <= 300 || extension_loaded('gmp')) {
			require_once('library/phpsec/Crypt/RSA.php');
			$rsa = new CRYPT_RSA();
			$rsa->signatureMode = CRYPT_RSA_SIGNATURE_PKCS1;
			$rsa->setHash($alg);
			$rsa->loadKey($key);
			$verify = $rsa->verify($data,$sig);
		}
		else {
			// fallback sha256 verify for PHP < 5.3 and large key lengths
			$rawsig = '';
        	openssl_public_decrypt($sig,$rawsig,$key);
	        $verify = (($rawsig && substr($rawsig,-32) === hash('sha256',$data,true)) ? true : false);
    	}
	}
	return $verify;
}


function DerToPem($Der, $Private=false)
{
    //Encode:
    $Der = base64_encode($Der);
    //Split lines:
    $lines = str_split($Der, 65);
    $body = implode("\n", $lines);
    //Get title:
    $title = $Private? 'RSA PRIVATE KEY' : 'PUBLIC KEY';
    //Add wrapping:
    $result = "-----BEGIN {$title}-----\n";
    $result .= $body . "\n";
    $result .= "-----END {$title}-----\n";
 
    return $result;
}

function DerToRsa($Der)
{
    //Encode:
    $Der = base64_encode($Der);
    //Split lines:
    $lines = str_split($Der, 64);
    $body = implode("\n", $lines);
    //Get title:
    $title = 'RSA PUBLIC KEY';
    //Add wrapping:
    $result = "-----BEGIN {$title}-----\n";
    $result .= $body . "\n";
    $result .= "-----END {$title}-----\n";
 
    return $result;
}


function pkcs8_encode($Modulus,$PublicExponent) {
	//Encode key sequence
	$modulus = new ASNValue(ASNValue::TAG_INTEGER);
	$modulus->SetIntBuffer($Modulus);
	$publicExponent = new ASNValue(ASNValue::TAG_INTEGER);
	$publicExponent->SetIntBuffer($PublicExponent);
	$keySequenceItems = array($modulus, $publicExponent);
	$keySequence = new ASNValue(ASNValue::TAG_SEQUENCE);
	$keySequence->SetSequence($keySequenceItems);
	//Encode bit string
	$bitStringValue = $keySequence->Encode();
	$bitStringValue = chr(0x00) . $bitStringValue; //Add unused bits byte
	$bitString = new ASNValue(ASNValue::TAG_BITSTRING);
	$bitString->Value = $bitStringValue;
	//Encode body
	$bodyValue = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00" . $bitString->Encode();
	$body = new ASNValue(ASNValue::TAG_SEQUENCE);
	$body->Value = $bodyValue;
	//Get DER encoded public key:
	$PublicDER = $body->Encode();
	return $PublicDER;
}


function pkcs1_encode($Modulus,$PublicExponent) {
	//Encode key sequence
	$modulus = new ASNValue(ASNValue::TAG_INTEGER);
	$modulus->SetIntBuffer($Modulus);
	$publicExponent = new ASNValue(ASNValue::TAG_INTEGER);
	$publicExponent->SetIntBuffer($PublicExponent);
	$keySequenceItems = array($modulus, $publicExponent);
	$keySequence = new ASNValue(ASNValue::TAG_SEQUENCE);
	$keySequence->SetSequence($keySequenceItems);
	//Encode bit string
	$bitStringValue = $keySequence->Encode();
	return $bitStringValue;
}


function metopem($m,$e) {
	$der = pkcs8_encode($m,$e);
	$key = DerToPem($der,false);
	return $key;
}	


function pubrsatome($key,&$m,&$e) {
	require_once('library/asn1.php');
	require_once('include/salmon.php');

	$lines = explode("\n",$key);
	unset($lines[0]);
	unset($lines[count($lines)]);
	$x = base64_decode(implode('',$lines));

	$r = ASN_BASE::parseASNString($x);

	$m = base64url_decode($r[0]->asnData[0]->asnData);
	$e = base64url_decode($r[0]->asnData[1]->asnData);
}


function rsatopem($key) {
	pubrsatome($key,$m,$e);
	return(metopem($m,$e));
}

function pemtorsa($key) {
	pemtome($key,$m,$e);
	return(metorsa($m,$e));
}

function pemtome($key,&$m,&$e) {
	require_once('include/salmon.php');
	$lines = explode("\n",$key);
	unset($lines[0]);
	unset($lines[count($lines)]);
	$x = base64_decode(implode('',$lines));

	$r = ASN_BASE::parseASNString($x);

	$m = base64url_decode($r[0]->asnData[1]->asnData[0]->asnData[0]->asnData);
	$e = base64url_decode($r[0]->asnData[1]->asnData[0]->asnData[1]->asnData);
}

function metorsa($m,$e) {
	$der = pkcs1_encode($m,$e);
	$key = DerToRsa($der);
	return $key;
}	

function salmon_key($pubkey) {
	pemtome($pubkey,$m,$e);
	return 'RSA' . '.' . base64url_encode($m,true) . '.' . base64url_encode($e,true) ;
}



if(! function_exists('aes_decrypt')) {
function aes_decrypt($val,$ky)
{
    $key="\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
    for($a=0;$a<strlen($ky);$a++)
      $key[$a%16]=chr(ord($key[$a%16]) ^ ord($ky[$a]));
    $mode = MCRYPT_MODE_ECB;
    $enc = MCRYPT_RIJNDAEL_128;
    $dec = @mcrypt_decrypt($enc, $key, $val, $mode, @mcrypt_create_iv( @mcrypt_get_iv_size($enc, $mode), MCRYPT_DEV_URANDOM ) );
    return rtrim($dec,(( ord(substr($dec,strlen($dec)-1,1))>=0 and ord(substr($dec, strlen($dec)-1,1))<=16)? chr(ord( substr($dec,strlen($dec)-1,1))):null));
}}


if(! function_exists('aes_encrypt')) {
function aes_encrypt($val,$ky)
{
    $key="\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
    for($a=0;$a<strlen($ky);$a++)
      $key[$a%16]=chr(ord($key[$a%16]) ^ ord($ky[$a]));
    $mode=MCRYPT_MODE_ECB;
    $enc=MCRYPT_RIJNDAEL_128;
    $val=str_pad($val, (16*(floor(strlen($val) / 16)+(strlen($val) % 16==0?2:1))), chr(16-(strlen($val) % 16)));
    return mcrypt_encrypt($enc, $key, $val, $mode, mcrypt_create_iv( mcrypt_get_iv_size($enc, $mode), MCRYPT_DEV_URANDOM));
}} 


function pkcs5_pad ($text, $blocksize)
{
    $pad = $blocksize - (strlen($text) % $blocksize);
    return $text . str_repeat(chr($pad), $pad);
}

function pkcs5_unpad($text)
{
    $pad = ord($text{strlen($text)-1});
    if ($pad > strlen($text)) return false;
    if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false;
    return substr($text, 0, -1 * $pad);
} 

function AES256CBC_encrypt($data,$key,$iv) {
	return mcrypt_encrypt(
		MCRYPT_RIJNDAEL_128, 
		str_pad($key,32,"\0"), 
		pkcs5_pad($data,16), 
		MCRYPT_MODE_CBC, 
		str_pad($iv,16,"\0"));
}

function AES256CBC_decrypt($data,$key,$iv) {
	return pkcs5_unpad(mcrypt_decrypt(
		MCRYPT_RIJNDAEL_128, 
		str_pad($key,32,"\0"), 
		$data, 
		MCRYPT_MODE_CBC, 
		str_pad($iv,16,"\0")));
}

function aes_encapsulate($data,$pubkey) {
	$key = random_string(32,RANDOM_STRING_TEXT);
	$iv  = random_string(16,RANDOM_STRING_TEXT);
	$result['data'] = base64url_encode(AES256CBC_encrypt($data,$key,$iv),true);
	openssl_public_encrypt($key,$k,$pubkey);
	$result['key'] = base64url_encode($k,true);
	openssl_public_encrypt($iv,$i,$pubkey);
	$result['iv'] = base64url_encode($i,true);
	return $result;
}

function aes_unencapsulate($data,$prvkey) {
	openssl_private_decrypt(base64url_decode($data['key']),$k,$prvkey);
	openssl_private_decrypt(base64url_decode($data['iv']),$i,$prvkey);
	return AES256CBC_decrypt(base64url_decode($data['data']),$k,$i);
}


// This has been superceded.

function zot_encapsulate($data,$envelope,$pubkey) {
$res = aes_encapsulate($data,$pubkey);

return <<< EOT
<?xml version='1.0' encoding='UTF-8'?>
<zot:msg xmlns:zot='http://purl.org/zot/1.0'>
 <zot:key>{$res['key']}</zot:key>
 <zot:iv>{$res['iv']}</zot:iv>
 <zot:env>$s1</zot:env>
 <zot:sig key_id="$keyid">$sig</zot:sig>
 <zot:alg>AES-256-CBC</zot:alg>
 <zot:data type='application/magic-envelope+xml'>{$res['data']}</zot:data>
</zot:msg>
EOT;

}

// so has this

function zot_unencapsulate($data,$prvkey) {
	$ret = array();
	$c = array();
	$x = parse_xml_string($data);
	$c = array('key' => $x->key,'iv' => $x->iv,'data' => $x->data);
	openssl_private_decrypt(base64url_decode($x->sender),$s,$prvkey);
	$ret['sender'] = $s;
	$ret['data'] = aes_unencapsulate($x,$prvkey);
	return $ret;
}