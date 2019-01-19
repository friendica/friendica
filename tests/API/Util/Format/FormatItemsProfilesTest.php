<?php

namespace Friendica\Test\API;

class FormatItemsProfilesTest extends ApiTest
{
	/**
	 * Test the api_format_items_profiles() function.
	 * @return void
	 */
	public function testDefault()
	{
		$profile_row = [
			'id' => 'profile_id',
			'profile-name' => 'profile_name',
			'is-default' => true,
			'hide-friends' => true,
			'photo' => 'profile_photo',
			'thumb' => 'profile_thumb',
			'publish' => true,
			'net-publish' => true,
			'pdesc' => 'description',
			'dob' => 'date_of_birth',
			'address' => 'address',
			'locality' => 'city',
			'region' => 'region',
			'postal-code' => 'postal_code',
			'country-name' => 'country',
			'hometown' => 'hometown',
			'gender' => 'gender',
			'marital' => 'marital',
			'with' => 'marital_with',
			'howlong' => 'marital_since',
			'sexual' => 'sexual',
			'politic' => 'politic',
			'religion' => 'religion',
			'pub_keywords' => 'public_keywords',
			'prv_keywords' => 'private_keywords',

			'likes' => 'likes',
			'dislikes' => 'dislikes',
			'about' => 'about',
			'music' => 'music',
			'book' => 'book',
			'tv' => 'tv',
			'film' => 'film',
			'interest' => 'interest',
			'romance' => 'romance',
			'work' => 'work',
			'education' => 'education',
			'contact' => 'social_networks',
			'homepage' => 'homepage'
		];
		$result = api_format_items_profiles($profile_row);
		$this->assertEquals(
			[
				'profile_id' => 'profile_id',
				'profile_name' => 'profile_name',
				'is_default' => true,
				'hide_friends' => true,
				'profile_photo' => 'profile_photo',
				'profile_thumb' => 'profile_thumb',
				'publish' => true,
				'net_publish' => true,
				'description' => 'description',
				'date_of_birth' => 'date_of_birth',
				'address' => 'address',
				'city' => 'city',
				'region' => 'region',
				'postal_code' => 'postal_code',
				'country' => 'country',
				'hometown' => 'hometown',
				'gender' => 'gender',
				'marital' => 'marital',
				'marital_with' => 'marital_with',
				'marital_since' => 'marital_since',
				'sexual' => 'sexual',
				'politic' => 'politic',
				'religion' => 'religion',
				'public_keywords' => 'public_keywords',
				'private_keywords' => 'private_keywords',

				'likes' => 'likes',
				'dislikes' => 'dislikes',
				'about' => 'about',
				'music' => 'music',
				'book' => 'book',
				'tv' => 'tv',
				'film' => 'film',
				'interest' => 'interest',
				'romance' => 'romance',
				'work' => 'work',
				'education' => 'education',
				'social_networks' => 'social_networks',
				'homepage' => 'homepage',
				'users' => null
			],
			$result
		);
	}
}
