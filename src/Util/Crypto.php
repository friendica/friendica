<?php
/**
 * @file src/Util/Crypto.php
 */
namespace Friendica\Util;

use Friendica\Core\Config;
use ASN_BASE;
use ASNValue;

/**
 * @brief Crypto class
 */
class Crypto
{
	const RSA_PAD_DEFAULT = 16777216;

	/**
	 * PKCS1 padding-agnostic OpenSSL private decrypt.
	 *
	 * If the padding parameter is omitted, it will perform two decrypt operations with both padding modes.
	 *
	 * @param string $ciphertext
	 * @param string $privateKey
	 * @param int    $padding_mode
	 * @return string
	 * @throws Exception
	 */
	public static function opensslPrivateDecrypt($ciphertext, $privateKey, $padding_mode = self::RSA_PAD_DEFAULT)
	{
		switch ($padding_mode) {
			case self::RSA_PAD_DEFAULT:
				// Decrypt twice
				$oaep = '';
				$pkcs1 = '';
				openssl_private_decrypt($ciphertext, $oaep, $privateKey, OPENSSL_PKCS1_OAEP_PADDING);
				openssl_private_decrypt($ciphertext, $pkcs1, $privateKey, OPENSSL_PKCS1_PADDING);
				/* Because the incorrect one will be false, we can coerce to string and concatenate
				  since FALSE converts to "" (empty string) and not bother implementing a constant-time
				  conditional swap function. */
				$plaintext = (string) $oaep . (string) $pkcs1;
				if (empty($plaintext)) {
					throw new Exception('Invalid padding');
				}

				break;
			case OPENSSL_PKCS1_PADDING:
				trigger_error('PKCS1v1.5 padding is deprecated due to it being a security disaster.', E_USER_DEPRECATED);
			// break statement intentionally omitted
			case OPENSSL_PKCS1_OAEP_PADDING:
				$plaintext = '';
				openssl_private_decrypt($ciphertext, $plaintext, $privateKey, $padding_mode);
				if (empty($plaintext)) {
					throw new Exception('Invalid padding');
				}

				break;
			default:
				throw new Exception('Invalid padding mode');
		}
		return $plaintext;
	}

	// supported algorithms are 'sha256', 'sha1'
	/**
	 * @param string $data data
	 * @param string $key  key
	 * @param string $alg  algorithm
	 * @return string
	 */
	public static function rsaSign($data, $key, $alg = 'sha256')
	{
		openssl_sign($data, $sig, $key, (($alg == 'sha1') ? OPENSSL_ALGO_SHA1 : $alg));
		return $sig;
	}

	/**
	 * @param string $data data
	 * @param string $sig  signature
	 * @param string $key  key
	 * @param string $alg  algorithm
	 * @return boolean
	 */
	public static function rsaVerify($data, $sig, $key, $alg = 'sha256')
	{
		return openssl_verify($data, $sig, $key, (($alg == 'sha1') ? OPENSSL_ALGO_SHA1 : $alg));
	}

	/**
	 * @param string $Der     der formatted string
	 * @param string $Private key type optional, default false
	 * @return string
	 */
	private static function DerToPem($Der, $Private = false)
	{
		//Encode:
		$Der = base64_encode($Der);
		//Split lines:
		$lines = str_split($Der, 65);
		$body = implode("\n", $lines);
		//Get title:
		$title = $Private ? 'RSA PRIVATE KEY' : 'PUBLIC KEY';
		//Add wrapping:
		$result = "-----BEGIN {$title}-----\n";
		$result .= $body . "\n";
		$result .= "-----END {$title}-----\n";

		return $result;
	}

	/**
	 * @param string $Der der formatted string
	 * @return string
	 */
	private static function DerToRsa($Der)
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

	/**
	 * @param string $Modulus        modulo
	 * @param string $PublicExponent exponent
	 * @return string
	 */
	private static function pkcs8Encode($Modulus, $PublicExponent)
	{
		//Encode key sequence
		$modulus = new ASNValue(ASNValue::TAG_INTEGER);
		$modulus->SetIntBuffer($Modulus);
		$publicExponent = new ASNValue(ASNValue::TAG_INTEGER);
		$publicExponent->SetIntBuffer($PublicExponent);
		$keySequenceItems = [$modulus, $publicExponent];
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

	/**
	 * @param string $Modulus        modulo
	 * @param string $PublicExponent exponent
	 * @return string
	 */
	private static function pkcs1Encode($Modulus, $PublicExponent)
	{
		//Encode key sequence
		$modulus = new ASNValue(ASNValue::TAG_INTEGER);
		$modulus->SetIntBuffer($Modulus);
		$publicExponent = new ASNValue(ASNValue::TAG_INTEGER);
		$publicExponent->SetIntBuffer($PublicExponent);
		$keySequenceItems = [$modulus, $publicExponent];
		$keySequence = new ASNValue(ASNValue::TAG_SEQUENCE);
		$keySequence->SetSequence($keySequenceItems);
		//Encode bit string
		$bitStringValue = $keySequence->Encode();
		return $bitStringValue;
	}

	/**
	 * @param string $m modulo
	 * @param string $e exponent
	 * @return string
	 */
	public static function meToPem($m, $e)
	{
		$der = self::pkcs8Encode($m, $e);
		$key = self::DerToPem($der, false);
		return $key;
	}

	/**
	 * @param string $key key
	 * @param string $m   modulo reference
	 * @param object $e   exponent reference
	 * @return void
	 */
	private static function pubRsaToMe($key, &$m, &$e)
	{
		$lines = explode("\n", $key);
		unset($lines[0]);
		unset($lines[count($lines)]);
		$x = base64_decode(implode('', $lines));

		$r = ASN_BASE::parseASNString($x);

		$m = base64url_decode($r[0]->asnData[0]->asnData);
		$e = base64url_decode($r[0]->asnData[1]->asnData);
	}

	/**
	 * @param string $key key
	 * @return string
	 */
	public static function rsaToPem($key)
	{
		self::pubRsaToMe($key, $m, $e);
		return self::meToPem($m, $e);
	}

	/**
	 * @param string $key key
	 * @return string
	 */
	private static function pemToRsa($key)
	{
		self::pemToMe($key, $m, $e);
		return self::meToRsa($m, $e);
	}

	/**
	 * @param string $key key
	 * @param string $m   modulo reference
	 * @param string $e   exponent reference
	 * @return void
	 */
	public static function pemToMe($key, &$m, &$e)
	{
		$lines = explode("\n", $key);
		unset($lines[0]);
		unset($lines[count($lines)]);
		$x = base64_decode(implode('', $lines));

		$r = ASN_BASE::parseASNString($x);

		$m = base64url_decode($r[0]->asnData[1]->asnData[0]->asnData[0]->asnData);
		$e = base64url_decode($r[0]->asnData[1]->asnData[0]->asnData[1]->asnData);
	}

	/**
	 * @param string $m modulo
	 * @param string $e exponent
	 * @return string
	 */
	private static function meToRsa($m, $e)
	{
		$der = self::pkcs1Encode($m, $e);
		$key = self::DerToRsa($der);
		return $key;
	}

	/**
	 * @param integer $bits number of bits
	 * @return mixed
	 */
	public static function newKeypair($bits)
	{
		$openssl_options = [
			'digest_alg'       => 'sha1',
			'private_key_bits' => $bits,
			'encrypt_key'      => false
		];

		$conf = Config::get('system', 'openssl_conf_file');
		if ($conf) {
			$openssl_options['config'] = $conf;
		}
		$result = openssl_pkey_new($openssl_options);

		if (empty($result)) {
			logger('new_keypair: failed');
			return false;
		}

		// Get private key
		$response = ['prvkey' => '', 'pubkey' => ''];

		openssl_pkey_export($result, $response['prvkey']);

		// Get public key
		$pkey = openssl_pkey_get_details($result);
		$response['pubkey'] = $pkey["key"];

		return $response;
	}
}
