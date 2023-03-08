<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test\src\Util;

use Friendica\Util\HTTPSignature;
use PHPUnit\Framework\TestCase;

/**
 * HTTP Signature utility test class
 */
class HTTPSignatureTest extends TestCase
{
	public function dataParseSigned()
	{
		return [
			'signed1' => [
				'header' => 'keyId="test-key-a", algorithm="hs2019",
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
				]
			]
		];
	}

	public function dataHeader()
	{
		return [
			'signed' => [
				'privKey' => '-----BEGIN PRIVATE KEY-----
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
G1vVmRgkLDqhc4+r3wDz3qy6JpV7tg==
-----END PRIVATE KEY-----',
				'keyId'  => 'acct:admin@friendica.local',
				'header' => [
					'Accept'          => ['application/x-dfrn+json', 'application/x-zot+json'],
					'X-Open-Web-Auth' => ['1dde649b855fd1aae542a91c4edd8c3a7a4c59d8eaf3136cdee05dfc16a30bac'],
				],
				'signature' => 'Signature keyId="acct:admin@friendica.local",algorithm="rsa-sha512",headers="accept x-open-web-auth",signature="cb09/wdmRdFhrQUczL0lR6LTkVh8qb/vYh70DFCW40QrzvuUYHzJ+GqqJW6tcCb2rXP4t+aobWKfZ4wFMBtVbejAiCgF01pzEBJfDevdyu7khlfKo+Gtw1CGk4/0so1QmqASeHdlG3ID3GnmuovqZn2v5f5D+1UAJ6Pu6mtAXrGRntoEM/oqYBAsRrMtDSDAE4tnOSDu2YfVJJLfyUX1ZWjUK0ejZXZ0YTDJwU8PqRcRX17NhBaDq82dEHlfhD7I/aOclwVbfKIi5Ud619XCxPq0sAAYso17MhUm40oBCJVze2x4pswJhv9IFYohtR5G/fKkz2eosu3xeRflvGicS4JdclhTRgCGWDy10DV76FiXiS5N2clLXreHItWEXlZKZx6m9zAZoEX92VRnc4BY4jDsRR89Pl88hELaxiNipviyHS7XYZTRnkLM+nvtOcxkHSCbEs7oGw+AX+pLHoU5otNOqy+ZbXQ1cUvFOBYZmYdX3DiWaLfBKraakPkslJuU3yJu95X1qYmQTpOZDR4Ma/yf5fmWJh5D9ywnXxxd6RaupoO6HTtIl6gmsfcsyZNi5hRbbgPI3BiQwGYVGF6qzJpEOMzEyHyAuFeanhicc8b+P+DCwXni5sjM7ntKwShbCBP80KHSdoumORin3/PYgHCmHZVv71N0HNlPZnyVzZw="',
			]
		];
	}

	/**
	 * @dataProvider dataParseSigned
	 */
	public function testParseSigheader(string $signature, array $assertion)
	{
		$headers = HTTPSignature::parseSigheader($signature);
		self::assertEquals($assertion, $headers);
	}

	/**
	 * @dataProvider dataHeader
	 */
	public function testSignHeader(string $privKey, string $keyId, array $header, string $signature)
	{
		$signed = HTTPSignature::createSig($header, $privKey, $keyId);
		self::assertEquals($signature, $signed['Authorization'][0]);
	}
}
