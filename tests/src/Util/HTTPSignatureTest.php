<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Util;

use Friendica\Util\HTTPSignature;
use PHPUnit\Framework\TestCase;

/**
 * HTTP Signature utility test class
 */
class HTTPSignatureTest extends TestCase
{
	public static function dataParseSigned()
	{
		return [
			'signed1' => [
				'signature' => 'keyId="test-key-a", algorithm="hs2019",
       created=1402170695,
       headers="(request-target) (created) host date content-type digest
           content-length",
       signature="KXUj1H3ZOhv3Nk4xlRLTn4bOMlMOmFiud3VXrMa9MaLCxnVmrqOX5B
           ulRvB65YW/wQp0oT/nNQpXgOYeY8ovmHlpkRyz5buNDqoOpRsCpLGxsIJ9cX8
           XVsM9jy+Q1+RIlD9wfWoPHhqhoXt35ZkasuIDPF/AETuObs9QydlsqONwbK+T
           dQguDK/8Va1Pocl6wK1uLwqcXlxhPEb55EmdYB9pddDyHTADING7K4qMwof2m
           C3t8Pb0yoLZoZX5a4Or4FrCCKK/9BHAhq/RsVk0dTENMbTB4i7cHvKQu+o9xu
           YWuxyvBa0Z6NdOb0di70cdrSDEsL5Gz7LBY5J2N9KdGg=="',
				'assertion' => [
					'keyId'     => 'test-key-a',
					'algorithm' => 'hs2019',
					'created'   => '1402170695',
					'expires'   => null,
					'headers'   => ['(request-target)', '(created)', 'host', 'date', 'content-type', 'digest', 'content-length'],
					'signature' => base64_decode('KXUj1H3ZOhv3Nk4xlRLTn4bOMlMOmFiud3VXrMa9MaLCxnVmrqOX5BulRvB65YW/wQp0oT/nNQpXgOYeY8ovmHlpkRyz5buNDqoOpRsCpLGxsIJ9cX8XVsM9jy+Q1+RIlD9wfWoPHhqhoXt35ZkasuIDPF/AETuObs9QydlsqONwbK+TdQguDK/8Va1Pocl6wK1uLwqcXlxhPEb55EmdYB9pddDyHTADING7K4qMwof2mC3t8Pb0yoLZoZX5a4Or4FrCCKK/9BHAhq/RsVk0dTENMbTB4i7cHvKQu+o9xuYWuxyvBa0Z6NdOb0di70cdrSDEsL5Gz7LBY5J2N9KdGg=='),
				],
			],
			'signed2' => [
				'signature' => 'Signature keyId="acct:admin@friendica.local",algorithm="rsa-sha512",created=1402170695,headers="accept x-open-web-auth",signature="lZzgtUOtyko/ZUjTRq6kUye6VmLbF2Zrku9TMl+9LEEdt7rxXOd+jpRr8L0s0G0oYSrbMzArgnk5S2XAz1XLi6GhoEgpyKJNmpnYUc/GvD86k0Cmb12TQF7B4Zv8k6h5LLCHppMi5myNIqQ95mqzVeWewCTgUupZVaqJxUAve1Uyx12jlzfYo6HAprXHhZKbLSAVfg+qiS8ufVp4coCYguioSMtMd4QvCFtAO3rFGwZ5yizXmPiKjhLQqxoy15+1VifTeAy8KJ+nPy+XeakpiYTfbPuCvaDqAMqboFx+J5epKS7M2T581oIgpSmQpHpkfCU8AT/JJ99Kktyzfn+ctK3Q8bKSjZQOT9S66S1b04jvFMggDM7p8Zu+Nr/SaS7ro5Yr7Fc200CM7yoRvef7MKQ7o0HASP8sMQwtSB4k+NlNBLUu9Eo/6P16bzx27cg3yGyuh15qmU8dL9heHqBL+E/m96BTZKIB5D81TkO/m0MpvJIxR0NfS9qKFcCAoev8kfcGcnVxtcxuCys7M/iW4ykJoMhFJDnsfN7mygOledeTmug8ARm0WpxUhtoNqhQrDXjAGbuPnQ1B/fPNwKVrHdFa2i/va0kgwa5+yh2ACqyMfrKCSkv2Prni3iKTKs8W0o/bF6FFeSqPsCGxneSMoYx3FOaSy6ts2gyuAwEozSg="',
				'assertion' => [
					'keyId'     => 'acct:admin@friendica.local',
					'algorithm' => 'rsa-sha512',
					'created'   => '1402170695',
					'expires'   => null,
					'headers'   => ['accept', 'x-open-web-auth'],
					'signature' => base64_decode('lZzgtUOtyko/ZUjTRq6kUye6VmLbF2Zrku9TMl+9LEEdt7rxXOd+jpRr8L0s0G0oYSrbMzArgnk5S2XAz1XLi6GhoEgpyKJNmpnYUc/GvD86k0Cmb12TQF7B4Zv8k6h5LLCHppMi5myNIqQ95mqzVeWewCTgUupZVaqJxUAve1Uyx12jlzfYo6HAprXHhZKbLSAVfg+qiS8ufVp4coCYguioSMtMd4QvCFtAO3rFGwZ5yizXmPiKjhLQqxoy15+1VifTeAy8KJ+nPy+XeakpiYTfbPuCvaDqAMqboFx+J5epKS7M2T581oIgpSmQpHpkfCU8AT/JJ99Kktyzfn+ctK3Q8bKSjZQOT9S66S1b04jvFMggDM7p8Zu+Nr/SaS7ro5Yr7Fc200CM7yoRvef7MKQ7o0HASP8sMQwtSB4k+NlNBLUu9Eo/6P16bzx27cg3yGyuh15qmU8dL9heHqBL+E/m96BTZKIB5D81TkO/m0MpvJIxR0NfS9qKFcCAoev8kfcGcnVxtcxuCys7M/iW4ykJoMhFJDnsfN7mygOledeTmug8ARm0WpxUhtoNqhQrDXjAGbuPnQ1B/fPNwKVrHdFa2i/va0kgwa5+yh2ACqyMfrKCSkv2Prni3iKTKs8W0o/bF6FFeSqPsCGxneSMoYx3FOaSy6ts2gyuAwEozSg='),
				],
			],
		];
	}

	public static function dataHeader()
	{
		return [
			'signed' => [
				'privKey' => '-----BEGIN PRIVATE KEY-----
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
G1vVmRgkLDqhc4+r3wDz3qy6JpV7tg==
-----END PRIVATE KEY-----',
				'keyId'  => 'acct:admin@friendica.local',
				'header' => [
					'Accept'          => 'application/x-dfrn+json, application/x-zot+json',
					'X-Open-Web-Auth' => '1dde649b855fd1aae542a91c4edd8c3a7a4c59d8eaf3136cdee05dfc16a30bac',
				],
				'signature' => 'Signature keyId="acct:admin@friendica.local",algorithm="rsa-sha512",headers="accept x-open-web-auth",signature="cb09/wdmRdFhrQUczL0lR6LTkVh8qb/vYh70DFCW40QrzvuUYHzJ+GqqJW6tcCb2rXP4t+aobWKfZ4wFMBtVbejAiCgF01pzEBJfDevdyu7khlfKo+Gtw1CGk4/0so1QmqASeHdlG3ID3GnmuovqZn2v5f5D+1UAJ6Pu6mtAXrGRntoEM/oqYBAsRrMtDSDAE4tnOSDu2YfVJJLfyUX1ZWjUK0ejZXZ0YTDJwU8PqRcRX17NhBaDq82dEHlfhD7I/aOclwVbfKIi5Ud619XCxPq0sAAYso17MhUm40oBCJVze2x4pswJhv9IFYohtR5G/fKkz2eosu3xeRflvGicS4JdclhTRgCGWDy10DV76FiXiS5N2clLXreHItWEXlZKZx6m9zAZoEX92VRnc4BY4jDsRR89Pl88hELaxiNipviyHS7XYZTRnkLM+nvtOcxkHSCbEs7oGw+AX+pLHoU5otNOqy+ZbXQ1cUvFOBYZmYdX3DiWaLfBKraakPkslJuU3yJu95X1qYmQTpOZDR4Ma/yf5fmWJh5D9ywnXxxd6RaupoO6HTtIl6gmsfcsyZNi5hRbbgPI3BiQwGYVGF6qzJpEOMzEyHyAuFeanhicc8b+P+DCwXni5sjM7ntKwShbCBP80KHSdoumORin3/PYgHCmHZVv71N0HNlPZnyVzZw="',
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataParseSigned')]
	public function testParseSigheader(string $signature, array $assertion): void
	{
		$headers = HTTPSignature::parseSigheader($signature);
		self::assertEquals($assertion, $headers);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataHeader')]
	public function testSignHeader(string $privKey, string $keyId, array $header, string $signature): void
	{
		$signed = HTTPSignature::createSig($header, $privKey, $keyId);
		self::assertEquals($signature, $signed['Authorization'][0]);
	}

	/**
	 * The signature base for the "full coverage" example from RFC 9421, appendix B.2.3
	 */
	public function testRfc9421SignatureBase(): void
	{
		$context = [
			'method'    => 'POST',
			'scheme'    => 'https',
			'authority' => 'example.com',
			'target'    => '/foo?param=Value&Pet=dog',
		];

		$headers = [
			'date'           => 'Tue, 20 Apr 2021 02:07:55 GMT',
			'content-type'   => 'application/json',
			'content-length' => '18',
			'content-digest' => 'sha-512=:WZDPaVn/7XgHaAy8pmojAkGWoRx2UFChF41A2svX+TaPm+AbwAgBWnrIiYllu7BNNyealdVLvRwEmTHWXvJwew==:',
		];

		$components = ['date', '@method', '@path', '@query', '@authority', 'content-type', 'content-digest', 'content-length'];
		$params     = '("date" "@method" "@path" "@query" "@authority" "content-type" "content-digest" "content-length");created=1618884473;keyid="test-key-rsa-pss"';

		$expected = '"date": Tue, 20 Apr 2021 02:07:55 GMT' . "\n"
			. '"@method": POST' . "\n"
			. '"@path": /foo' . "\n"
			. '"@query": ?param=Value&Pet=dog' . "\n"
			. '"@authority": example.com' . "\n"
			. '"content-type": application/json' . "\n"
			. '"content-digest": sha-512=:WZDPaVn/7XgHaAy8pmojAkGWoRx2UFChF41A2svX+TaPm+AbwAgBWnrIiYllu7BNNyealdVLvRwEmTHWXvJwew==:' . "\n"
			. '"content-length": 18' . "\n"
			. '"@signature-params": ' . $params;

		self::assertEquals($expected, HTTPSignature::rfc9421SignatureBase($components, $params, $context, $headers));
	}

	public function testRfc9421SignatureBaseDerivedComponents(): void
	{
		$context = [
			'method'    => 'GET',
			'scheme'    => 'https',
			'authority' => 'example.com',
			'target'    => '/users/foo/outbox?page=2',
		];

		$components = ['@method', '@target-uri', '@authority', '@path', '@query'];
		$params     = '("@method" "@target-uri" "@authority" "@path" "@query");created=1618884473;keyid="test-key"';

		$expected = '"@method": GET' . "\n"
			. '"@target-uri": https://example.com/users/foo/outbox?page=2' . "\n"
			. '"@authority": example.com' . "\n"
			. '"@path": /users/foo/outbox' . "\n"
			. '"@query": ?page=2' . "\n"
			. '"@signature-params": ' . $params;

		self::assertEquals($expected, HTTPSignature::rfc9421SignatureBase($components, $params, $context, []));
	}

	public function testRfc9421SignatureBaseRejectsUnknownComponent(): void
	{
		$context = ['method' => 'GET', 'scheme' => 'https', 'authority' => 'example.com', 'target' => '/foo'];

		self::assertNull(HTTPSignature::rfc9421SignatureBase(['@status'], '("@status")', $context, []));
		self::assertNull(HTTPSignature::rfc9421SignatureBase(['signature'], '("signature")', $context, []));
	}

	/**
	 * Verifies the rsa-pss-sha512 signature from RFC 9421, appendix B.2.3 against its test key
	 */
	public function testRfc9421VerifyRsaPss(): void
	{
		$pubKey = "-----BEGIN PUBLIC KEY-----\n"
			. "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAr4tmm3r20Wd/PbqvP1s2\n"
			. "+QEtvpuRaV8Yq40gjUR8y2Rjxa6dpG2GXHbPfvMs8ct+Lh1GH45x28Rw3Ry53mm+\n"
			. "oAXjyQ86OnDkZ5N8lYbggD4O3w6M6pAvLkhk95AndTrifbIFPNU8PPMO7OyrFAHq\n"
			. "gDsznjPFmTOtCEcN2Z1FpWgchwuYLPL+Wokqltd11nqqzi+bJ9cvSKADYdUAAN5W\n"
			. "Utzdpiy6LbTgSxP7ociU4Tn0g5I6aDZJ7A8Lzo0KSyZYoA485mqcO0GVAdVw9lq4\n"
			. "aOT9v6d+nb4bnNkQVklLQ3fVAvJm+xdDOp9LCNCN48V2pnDOkFV6+U9nV5oyc6XI\n"
			. "2wIDAQAB\n"
			. "-----END PUBLIC KEY-----\n";

		$signature = base64_decode(
			'bbN8oArOxYoyylQQUU6QYwrTuaxLwjAC9fbY2F6SVWvh0yBiMIRGOnMYwZ/5MR6fb0Kh1rIRASVxFkeGt683+qRpRRU5p2v'
			. 'oTp768ZrCUb38K0fUxN0O0iC59DzYx8DFll5GmydPxSmme9v6ULbMFkl+V5B1TP/yPViV7KsLNmvKiLJH1pFkh/aYA2HXXZ'
			. 'zNBXmIkoQoLd7YfW91kE9o/CCoC1xMy7JA1ipwvKvfrs65ldmlu9bpG6A9BmzhuzF8Eim5f8ui9eH8LZH896+QIF61ka39V'
			. 'Brohr9iyMUJpvRX2Zbhl5ZJzSRxpJyoEZAFL2FUo5fTIztsDZKEgM4cUA==',
		);

		$context = ['method' => 'POST', 'scheme' => 'https', 'authority' => 'example.com', 'target' => '/foo?param=Value&Pet=dog'];
		$headers = [
			'date'           => 'Tue, 20 Apr 2021 02:07:55 GMT',
			'content-type'   => 'application/json',
			'content-length' => '18',
			'content-digest' => 'sha-512=:WZDPaVn/7XgHaAy8pmojAkGWoRx2UFChF41A2svX+TaPm+AbwAgBWnrIiYllu7BNNyealdVLvRwEmTHWXvJwew==:',
		];
		$components = ['date', '@method', '@path', '@query', '@authority', 'content-type', 'content-digest', 'content-length'];
		$params     = '("date" "@method" "@path" "@query" "@authority" "content-type" "content-digest" "content-length");created=1618884473;keyid="test-key-rsa-pss"';

		$base = HTTPSignature::rfc9421SignatureBase($components, $params, $context, $headers);

		$verify = new \ReflectionMethod(HTTPSignature::class, 'verifySignature');

		self::assertTrue($verify->invoke(null, $base, $signature, $pubKey, 'rsa-pss-sha512'));
		self::assertFalse($verify->invoke(null, $base . ' ', $signature, $pubKey, 'rsa-pss-sha512'));
	}

	/**
	 * Verifies the rsa-v1_5-sha256 "proxy_sig" from RFC 9421, section 3.2 against its test key
	 */
	public function testRfc9421VerifyRsaV15(): void
	{
		$pubKey = "-----BEGIN PUBLIC KEY-----\n"
			. "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhAKYdtoeoy8zcAcR874L\n"
			. "8cnZxKzAGwd7v36APp7Pv6Q2jdsPBRrwWEBnez6d0UDKDwGbc6nxfEXAy5mbhgaj\n"
			. "zrw3MOEt8uA5txSKobBpKDeBLOsdJKFqMGmXCQvEG7YemcxDTRPxAleIAgYYRjTS\n"
			. "d/QBwVW9OwNFhekro3RtlinV0a75jfZgkne/YiktSvLG34lw2zqXBDTC5NHROUqG\n"
			. "TlML4PlNZS5Ri2U4aCNx2rUPRcKIlE0PuKxI4T+HIaFpv8+rdV6eUgOrB2xeI1dS\n"
			. "FFn/nnv5OoZJEIB+VmuKn3DCUcCZSFlQPSXSfBDiUGhwOw76WuSSsf1D4b/vLoJ1\n"
			. "0wIDAQAB\n"
			. "-----END PUBLIC KEY-----\n";

		$signature = base64_decode(
			'S6ZzPXSdAMOPjN/6KXfXWNO/f7V6cHm7BXYUh3YD/fRad4BCaRZxP+JH+8XY1I6+8Cy+CM5g92iHgxtRPz+MjniOaYmdkDc'
			. 'nL9cCpXJleXsOckpURl49GwiyUpZ10KHgOEe11sx3G2gxI8S0jnxQB+Pu68U9vVcasqOWAEObtNKKZd8tSFu7LB5YAv0RAG'
			. 'hB8tmpv7sFnIm9y+7X5kXQfi8NMaZaA8i2ZHwpBdg7a6CMfwnnrtflzvZdXAsD3LH2TwevU+/PBPv0B6NMNk93wUs/vfJvy'
			. 'e+YuI87HU38lZHowtznbLVdp770I6VHR6WfgS9ddzirrswsE1w5o0LV/g==',
		);

		$context = ['method' => 'POST', 'scheme' => 'https', 'authority' => 'origin.host.internal.example', 'target' => '/foo?param=Value&Pet=dog'];
		$headers = [
			'content-digest' => 'sha-512=:WZDPaVn/7XgHaAy8pmojAkGWoRx2UFChF41A2svX+TaPm+AbwAgBWnrIiYllu7BNNyealdVLvRwEmTHWXvJwew==:',
			'content-type'   => 'application/json',
			'content-length' => '18',
			'forwarded'      => 'for=192.0.2.123;host=example.com;proto=https',
		];
		$components = ['@method', '@authority', '@path', 'content-digest', 'content-type', 'content-length', 'forwarded'];
		$params     = '("@method" "@authority" "@path" "content-digest" "content-type" "content-length" "forwarded");created=1618884480;keyid="test-key-rsa";alg="rsa-v1_5-sha256";expires=1618884540';

		$base = HTTPSignature::rfc9421SignatureBase($components, $params, $context, $headers);

		$verify = new \ReflectionMethod(HTTPSignature::class, 'verifySignature');

		self::assertTrue($verify->invoke(null, $base, $signature, $pubKey, 'sha256'));
	}

	/**
	 * An outgoing RFC 9421 POST signature verifies against the signing key
	 */
	public function testSignRequestRfc9421Post(): void
	{
		$keypair = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
		openssl_pkey_export($keypair, $privKey);
		$pubKey = openssl_pkey_get_details($keypair)['key'];

		$body    = '{"type":"Create"}';
		$url     = 'https://remote.example/inbox';
		$headers = HTTPSignature::signRequestRfc9421('POST', $url, $body, $privKey, 'https://local.example/actor#main-key');

		self::assertArrayHasKey('Content-Digest', $headers);
		self::assertSame('sha-256=:' . base64_encode(hash('sha256', $body, true)) . ':', $headers['Content-Digest']);
		self::assertStringStartsWith('sig1=("@method" "@target-uri" "content-digest");created=', $headers['Signature-Input']);

		$params = substr((string) $headers['Signature-Input'], strlen('sig1='));
		$base   = HTTPSignature::rfc9421SignatureBase(
			['@method', '@target-uri', 'content-digest'],
			$params,
			['method'         => 'POST', 'scheme' => 'https', 'authority' => 'remote.example', 'target' => '/inbox'],
			['content-digest' => $headers['Content-Digest']],
		);

		$signature = base64_decode(substr((string) $headers['Signature'], strlen('sig1=:'), -1));

		$verify = new \ReflectionMethod(HTTPSignature::class, 'verifySignature');
		self::assertTrue($verify->invoke(null, $base, $signature, $pubKey, 'sha256'));
	}

	/**
	 * An outgoing RFC 9421 GET signature covers method and target only, no digest
	 */
	public function testSignRequestRfc9421Get(): void
	{
		$keypair = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
		openssl_pkey_export($keypair, $privKey);
		$pubKey = openssl_pkey_get_details($keypair)['key'];

		$url     = 'https://remote.example/users/bob/outbox?page=2';
		$headers = HTTPSignature::signRequestRfc9421('GET', $url, null, $privKey, 'https://local.example/actor#main-key');

		self::assertArrayNotHasKey('Content-Digest', $headers);
		self::assertStringStartsWith('sig1=("@method" "@target-uri");created=', $headers['Signature-Input']);

		$params = substr((string) $headers['Signature-Input'], strlen('sig1='));
		$base   = HTTPSignature::rfc9421SignatureBase(
			['@method', '@target-uri'],
			$params,
			['method' => 'GET', 'scheme' => 'https', 'authority' => 'remote.example', 'target' => '/users/bob/outbox?page=2'],
			[],
		);

		$signature = base64_decode(substr((string) $headers['Signature'], strlen('sig1=:'), -1));

		$verify = new \ReflectionMethod(HTTPSignature::class, 'verifySignature');
		self::assertTrue($verify->invoke(null, $base, $signature, $pubKey, 'sha256'));
	}

	/**
	 * Verifies the ed25519 signature from RFC 9421, appendix B.2.6 against its Multikey
	 */
	public function testRfc9421VerifyEd25519(): void
	{
		// RFC 9421 appendix B.1.4 test-key-ed25519 as a multibase Multikey value
		$multikey  = 'z6Mkh4LmfP1ev9MNPGr7JbEbtD6BD4fsu1duEj83PMCs3xHG';
		$signature = base64_decode('wqcAqbmYJ2ji2glfAMaRy4gruYYnx2nEFN2HN6jrnDnQCK1u02Gb04v9EDgwUPiu4A0w6vuQv5lIp5WPpBKRCw==');

		$context = ['method' => 'POST', 'scheme' => 'https', 'authority' => 'example.com', 'target' => '/foo?param=Value&Pet=dog'];
		$headers = [
			'date'           => 'Tue, 20 Apr 2021 02:07:55 GMT',
			'content-type'   => 'application/json',
			'content-length' => '18',
		];
		$components = ['date', '@method', '@path', '@authority', 'content-type', 'content-length'];
		$params     = '("date" "@method" "@path" "@authority" "content-type" "content-length");created=1618884473;keyid="test-key-ed25519"';

		$base = HTTPSignature::rfc9421SignatureBase($components, $params, $context, $headers);

		$verify = new \ReflectionMethod(HTTPSignature::class, 'verifySignature');
		self::assertTrue($verify->invoke(null, $base, $signature, $multikey, 'ed25519'));
		self::assertFalse($verify->invoke(null, $base . ' ', $signature, $multikey, 'ed25519'));
		self::assertFalse($verify->invoke(null, $base, $signature, 'not-a-key', 'ed25519'));
	}

	/**
	 * Issue 14973: the query string is part of the draft-cavage "(request-target)"
	 */
	public function testRequestTargetIncludesQueryString(): void
	{
		$requestTarget = new \ReflectionMethod(HTTPSignature::class, 'requestTarget');

		self::assertSame('get /users/foo/outbox?page=2', $requestTarget->invoke(null, 'GET', 'https://social.example/users/foo/outbox?page=2'));
		self::assertSame('post /inbox', $requestTarget->invoke(null, 'POST', 'https://social.example/inbox'));

		// The legacy path-only form used for the backward-compatible retry
		self::assertSame('get /users/foo/outbox', $requestTarget->invoke(null, 'GET', 'https://social.example/users/foo/outbox?page=2', false));
	}

	/**
	 * Issue 14973: an outgoing signed GET signs the query string, and the retry drops it
	 */
	public function testCavageSignedGetCoversQueryString(): void
	{
		$keypair = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
		openssl_pkey_export($keypair, $privKey);
		$pubKey = openssl_pkey_get_details($keypair)['key'];

		$signGet         = new \ReflectionMethod(HTTPSignature::class, 'signGet');
		$buildSignedData = new \ReflectionMethod(HTTPSignature::class, 'buildSignedData');
		$owner           = ['uprvkey' => $privKey, 'url' => 'https://local.example/actor'];
		$url             = 'https://remote.example/users/bob/outbox?min_id=1';

		foreach (['/users/bob/outbox?min_id=1' => true, '/users/bob/outbox' => false] as $target => $withQuery) {
			$header = $signGet->invoke(null, [], $url, $owner, $withQuery);

			$signedData = $buildSignedData->invoke(null, ['(request-target)', 'date', 'host'], [
				'(request-target)' => 'get ' . $target,
				'date'             => $header['Date'],
				'host'             => $header['Host'],
			]);

			preg_match('/signature="([^"]+)"/', (string) $header['Signature'], $match);
			self::assertSame(1, openssl_verify($signedData, base64_decode($match[1]), $pubKey, OPENSSL_ALGO_SHA256));
		}
	}

	/**
	 * An RSA signature with no "alg" parameter is verified against both PKCS#1 v1.5
	 * and PSS (the FASP discovery service signs with rsa-pss-sha512 and no "alg").
	 */
	public function testRfc9421RsaWithoutAlgParameter(): void
	{
		$keypair = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
		openssl_pkey_export($keypair, $privKey);
		$pubKey = openssl_pkey_get_details($keypair)['key'];

		$context = ['method' => 'GET', 'scheme' => 'https', 'authority' => 'friendica.example', 'target' => '/followers/alice'];
		$digest  = 'sha-256=:47DEQpj8HBSa+/TImW+5JCeuQeRkm5NMpJWZG3hSuFU=:';
		$params  = '("@method" "@target-uri" "content-digest");created=1618884473;keyid="https://fasp.example/actor#main-key"';
		$base    = HTTPSignature::rfc9421SignatureBase(['@method', '@target-uri', 'content-digest'], $params, $context, ['content-digest' => $digest]);

		$key = \phpseclib3\Crypt\PublicKeyLoader::load($privKey);
		self::assertInstanceOf(\phpseclib3\Crypt\RSA::class, $key);
		$signer = $key->withPadding(\phpseclib3\Crypt\RSA::SIGNATURE_PSS)->withHash('sha512')->withMGFHash('sha512');
		self::assertInstanceOf(\phpseclib3\Crypt\Common\PrivateKey::class, $signer);
		$signature = $signer->sign($base);

		$algorithms    = new \ReflectionMethod(HTTPSignature::class, 'rfc9421Algorithms');
		$keyAlgorithms = new \ReflectionMethod(HTTPSignature::class, 'keyAlgorithms');
		$verify        = new \ReflectionMethod(HTTPSignature::class, 'verifySignature');

		$candidates = $keyAlgorithms->invoke(null, $pubKey, $algorithms->invoke(null, ''));
		self::assertSame(['sha256', 'rsa-pss-sha512'], $candidates);

		$verified = false;
		foreach ($candidates as $candidate) {
			$verified = $verified || $verify->invoke(null, $base, $signature, $pubKey, $candidate);
		}
		self::assertTrue($verified);

		// A multibase key never gets an RSA verification attempt
		self::assertSame(['ed25519'], $keyAlgorithms->invoke(null, 'z6MkExample', $algorithms->invoke(null, '')));
	}

	/**
	 * A signature without "created" has no lower time bound and is rejected, even
	 * when the body and its Content-Digest match.
	 */
	public function testRfc9421RequiresCreated(): void
	{
		$check = new \ReflectionMethod(HTTPSignature::class, 'rfc9421CheckContent');

		$components = ['@method', '@target-uri', 'content-digest'];
		$headers    = ['content-digest' => 'sha-256=:RBNvo1WzZ4oRRq0W9+hknpT7T8If536DEMBg9hyq/4o=:'];

		self::assertFalse($check->invoke(null, '{}', $components, $headers, 0, 0));
		self::assertTrue($check->invoke(null, '{}', $components, $headers, time(), 0));
	}
}
