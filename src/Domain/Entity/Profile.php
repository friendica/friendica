<?php

/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU APGL version 3 or any later version
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
 * Used to check/generate entities for the Friendica codebase
 */

declare(strict_types=1);

namespace Friendica\Domain\Entity;

use Friendica\BaseEntity;
use Friendica\Network\HTTPException\NotImplementedException;

/**
 * Entity class for table profile
 *
 * user profiles data
 */
class Profile extends BaseEntity
{
	/**
	 * @var int
	 * sequential ID
	 */
	private $id;

	/**
	 * @var int
	 * Owner User id
	 */
	private $uid = '0';

	/**
	 * @var string
	 * Deprecated
	 */
	private $profileName;

	/**
	 * @var bool
	 * Deprecated
	 */
	private $isDefault;

	/**
	 * @var bool
	 * Hide friend list from viewers of this profile
	 */
	private $hideFriends = '0';

	/**
	 * @var string
	 */
	private $name = '';

	/**
	 * @var string
	 * Deprecated
	 */
	private $pdesc;

	/**
	 * @var string
	 * Day of birth
	 */
	private $dob = '0000-00-00';

	/**
	 * @var string
	 */
	private $address = '';

	/**
	 * @var string
	 */
	private $locality = '';

	/**
	 * @var string
	 */
	private $region = '';

	/**
	 * @var string
	 */
	private $postalCode = '';

	/**
	 * @var string
	 */
	private $countryName = '';

	/**
	 * @var string
	 * Deprecated
	 */
	private $hometown;

	/**
	 * @var string
	 * Deprecated
	 */
	private $gender;

	/**
	 * @var string
	 * Deprecated
	 */
	private $marital;

	/**
	 * @var string
	 * Deprecated
	 */
	private $with;

	/**
	 * @var string
	 * Deprecated
	 */
	private $howlong;

	/**
	 * @var string
	 * Deprecated
	 */
	private $sexual;

	/**
	 * @var string
	 * Deprecated
	 */
	private $politic;

	/**
	 * @var string
	 * Deprecated
	 */
	private $religion;

	/**
	 * @var string
	 */
	private $pubKeywords;

	/**
	 * @var string
	 */
	private $prvKeywords;

	/**
	 * @var string
	 * Deprecated
	 */
	private $likes;

	/**
	 * @var string
	 * Deprecated
	 */
	private $dislikes;

	/**
	 * @var string
	 * Profile description
	 */
	private $about;

	/**
	 * @var string
	 * Deprecated
	 */
	private $summary;

	/**
	 * @var string
	 * Deprecated
	 */
	private $music;

	/**
	 * @var string
	 * Deprecated
	 */
	private $book;

	/**
	 * @var string
	 * Deprecated
	 */
	private $tv;

	/**
	 * @var string
	 * Deprecated
	 */
	private $film;

	/**
	 * @var string
	 * Deprecated
	 */
	private $interest;

	/**
	 * @var string
	 * Deprecated
	 */
	private $romance;

	/**
	 * @var string
	 * Deprecated
	 */
	private $work;

	/**
	 * @var string
	 * Deprecated
	 */
	private $education;

	/**
	 * @var string
	 * Deprecated
	 */
	private $contact;

	/**
	 * @var string
	 */
	private $homepage = '';

	/**
	 * @var string
	 */
	private $xmpp = '';

	/**
	 * @var string
	 */
	private $photo = '';

	/**
	 * @var string
	 */
	private $thumb = '';

	/**
	 * @var bool
	 * publish default profile in local directory
	 */
	private $publish = '0';

	/**
	 * @var bool
	 * publish profile in global directory
	 */
	private $netPublish = '0';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'uid' => $this->uid,
			'profile-name' => $this->profileName,
			'is-default' => $this->isDefault,
			'hide-friends' => $this->hideFriends,
			'name' => $this->name,
			'pdesc' => $this->pdesc,
			'dob' => $this->dob,
			'address' => $this->address,
			'locality' => $this->locality,
			'region' => $this->region,
			'postal-code' => $this->postalCode,
			'country-name' => $this->countryName,
			'hometown' => $this->hometown,
			'gender' => $this->gender,
			'marital' => $this->marital,
			'with' => $this->with,
			'howlong' => $this->howlong,
			'sexual' => $this->sexual,
			'politic' => $this->politic,
			'religion' => $this->religion,
			'pub_keywords' => $this->pubKeywords,
			'prv_keywords' => $this->prvKeywords,
			'likes' => $this->likes,
			'dislikes' => $this->dislikes,
			'about' => $this->about,
			'summary' => $this->summary,
			'music' => $this->music,
			'book' => $this->book,
			'tv' => $this->tv,
			'film' => $this->film,
			'interest' => $this->interest,
			'romance' => $this->romance,
			'work' => $this->work,
			'education' => $this->education,
			'contact' => $this->contact,
			'homepage' => $this->homepage,
			'xmpp' => $this->xmpp,
			'photo' => $this->photo,
			'thumb' => $this->thumb,
			'publish' => $this->publish,
			'net-publish' => $this->netPublish,
		];
	}

	/**
	 * @return int
	 * Get sequential ID
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return int
	 * Get Owner User id
	 */
	public function getUid()
	{
		return $this->uid;
	}

	/**
	 * @param int $uid
	 * Set Owner User id
	 */
	public function setUid(int $uid)
	{
		$this->uid = $uid;
	}

	/**
	 * Get User
	 *
	 * @return User
	 */
	public function getUser()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for uid is not implemented yet');
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getProfileName()
	{
		return $this->profileName;
	}

	/**
	 * @param string $profileName
	 * Set Deprecated
	 */
	public function setProfileName(string $profileName)
	{
		$this->profileName = $profileName;
	}

	/**
	 * @return bool
	 * Get Deprecated
	 */
	public function getIsDefault()
	{
		return $this->isDefault;
	}

	/**
	 * @param bool $isDefault
	 * Set Deprecated
	 */
	public function setIsDefault(bool $isDefault)
	{
		$this->isDefault = $isDefault;
	}

	/**
	 * @return bool
	 * Get Hide friend list from viewers of this profile
	 */
	public function getHideFriends()
	{
		return $this->hideFriends;
	}

	/**
	 * @param bool $hideFriends
	 * Set Hide friend list from viewers of this profile
	 */
	public function setHideFriends(bool $hideFriends)
	{
		$this->hideFriends = $hideFriends;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 * Set
	 */
	public function setName(string $name)
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getPdesc()
	{
		return $this->pdesc;
	}

	/**
	 * @param string $pdesc
	 * Set Deprecated
	 */
	public function setPdesc(string $pdesc)
	{
		$this->pdesc = $pdesc;
	}

	/**
	 * @return string
	 * Get Day of birth
	 */
	public function getDob()
	{
		return $this->dob;
	}

	/**
	 * @param string $dob
	 * Set Day of birth
	 */
	public function setDob(string $dob)
	{
		$this->dob = $dob;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getAddress()
	{
		return $this->address;
	}

	/**
	 * @param string $address
	 * Set
	 */
	public function setAddress(string $address)
	{
		$this->address = $address;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getLocality()
	{
		return $this->locality;
	}

	/**
	 * @param string $locality
	 * Set
	 */
	public function setLocality(string $locality)
	{
		$this->locality = $locality;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getRegion()
	{
		return $this->region;
	}

	/**
	 * @param string $region
	 * Set
	 */
	public function setRegion(string $region)
	{
		$this->region = $region;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getPostalCode()
	{
		return $this->postalCode;
	}

	/**
	 * @param string $postalCode
	 * Set
	 */
	public function setPostalCode(string $postalCode)
	{
		$this->postalCode = $postalCode;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getCountryName()
	{
		return $this->countryName;
	}

	/**
	 * @param string $countryName
	 * Set
	 */
	public function setCountryName(string $countryName)
	{
		$this->countryName = $countryName;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getHometown()
	{
		return $this->hometown;
	}

	/**
	 * @param string $hometown
	 * Set Deprecated
	 */
	public function setHometown(string $hometown)
	{
		$this->hometown = $hometown;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getGender()
	{
		return $this->gender;
	}

	/**
	 * @param string $gender
	 * Set Deprecated
	 */
	public function setGender(string $gender)
	{
		$this->gender = $gender;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getMarital()
	{
		return $this->marital;
	}

	/**
	 * @param string $marital
	 * Set Deprecated
	 */
	public function setMarital(string $marital)
	{
		$this->marital = $marital;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getWith()
	{
		return $this->with;
	}

	/**
	 * @param string $with
	 * Set Deprecated
	 */
	public function setWith(string $with)
	{
		$this->with = $with;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getHowlong()
	{
		return $this->howlong;
	}

	/**
	 * @param string $howlong
	 * Set Deprecated
	 */
	public function setHowlong(string $howlong)
	{
		$this->howlong = $howlong;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getSexual()
	{
		return $this->sexual;
	}

	/**
	 * @param string $sexual
	 * Set Deprecated
	 */
	public function setSexual(string $sexual)
	{
		$this->sexual = $sexual;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getPolitic()
	{
		return $this->politic;
	}

	/**
	 * @param string $politic
	 * Set Deprecated
	 */
	public function setPolitic(string $politic)
	{
		$this->politic = $politic;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getReligion()
	{
		return $this->religion;
	}

	/**
	 * @param string $religion
	 * Set Deprecated
	 */
	public function setReligion(string $religion)
	{
		$this->religion = $religion;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getPubKeywords()
	{
		return $this->pubKeywords;
	}

	/**
	 * @param string $pubKeywords
	 * Set
	 */
	public function setPubKeywords(string $pubKeywords)
	{
		$this->pubKeywords = $pubKeywords;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getPrvKeywords()
	{
		return $this->prvKeywords;
	}

	/**
	 * @param string $prvKeywords
	 * Set
	 */
	public function setPrvKeywords(string $prvKeywords)
	{
		$this->prvKeywords = $prvKeywords;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getLikes()
	{
		return $this->likes;
	}

	/**
	 * @param string $likes
	 * Set Deprecated
	 */
	public function setLikes(string $likes)
	{
		$this->likes = $likes;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getDislikes()
	{
		return $this->dislikes;
	}

	/**
	 * @param string $dislikes
	 * Set Deprecated
	 */
	public function setDislikes(string $dislikes)
	{
		$this->dislikes = $dislikes;
	}

	/**
	 * @return string
	 * Get Profile description
	 */
	public function getAbout()
	{
		return $this->about;
	}

	/**
	 * @param string $about
	 * Set Profile description
	 */
	public function setAbout(string $about)
	{
		$this->about = $about;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getSummary()
	{
		return $this->summary;
	}

	/**
	 * @param string $summary
	 * Set Deprecated
	 */
	public function setSummary(string $summary)
	{
		$this->summary = $summary;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getMusic()
	{
		return $this->music;
	}

	/**
	 * @param string $music
	 * Set Deprecated
	 */
	public function setMusic(string $music)
	{
		$this->music = $music;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getBook()
	{
		return $this->book;
	}

	/**
	 * @param string $book
	 * Set Deprecated
	 */
	public function setBook(string $book)
	{
		$this->book = $book;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getTv()
	{
		return $this->tv;
	}

	/**
	 * @param string $tv
	 * Set Deprecated
	 */
	public function setTv(string $tv)
	{
		$this->tv = $tv;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getFilm()
	{
		return $this->film;
	}

	/**
	 * @param string $film
	 * Set Deprecated
	 */
	public function setFilm(string $film)
	{
		$this->film = $film;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getInterest()
	{
		return $this->interest;
	}

	/**
	 * @param string $interest
	 * Set Deprecated
	 */
	public function setInterest(string $interest)
	{
		$this->interest = $interest;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getRomance()
	{
		return $this->romance;
	}

	/**
	 * @param string $romance
	 * Set Deprecated
	 */
	public function setRomance(string $romance)
	{
		$this->romance = $romance;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getWork()
	{
		return $this->work;
	}

	/**
	 * @param string $work
	 * Set Deprecated
	 */
	public function setWork(string $work)
	{
		$this->work = $work;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getEducation()
	{
		return $this->education;
	}

	/**
	 * @param string $education
	 * Set Deprecated
	 */
	public function setEducation(string $education)
	{
		$this->education = $education;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getContact()
	{
		return $this->contact;
	}

	/**
	 * @param string $contact
	 * Set Deprecated
	 */
	public function setContact(string $contact)
	{
		$this->contact = $contact;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getHomepage()
	{
		return $this->homepage;
	}

	/**
	 * @param string $homepage
	 * Set
	 */
	public function setHomepage(string $homepage)
	{
		$this->homepage = $homepage;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getXmpp()
	{
		return $this->xmpp;
	}

	/**
	 * @param string $xmpp
	 * Set
	 */
	public function setXmpp(string $xmpp)
	{
		$this->xmpp = $xmpp;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getPhoto()
	{
		return $this->photo;
	}

	/**
	 * @param string $photo
	 * Set
	 */
	public function setPhoto(string $photo)
	{
		$this->photo = $photo;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getThumb()
	{
		return $this->thumb;
	}

	/**
	 * @param string $thumb
	 * Set
	 */
	public function setThumb(string $thumb)
	{
		$this->thumb = $thumb;
	}

	/**
	 * @return bool
	 * Get publish default profile in local directory
	 */
	public function getPublish()
	{
		return $this->publish;
	}

	/**
	 * @param bool $publish
	 * Set publish default profile in local directory
	 */
	public function setPublish(bool $publish)
	{
		$this->publish = $publish;
	}

	/**
	 * @return bool
	 * Get publish profile in global directory
	 */
	public function getNetPublish()
	{
		return $this->netPublish;
	}

	/**
	 * @param bool $netPublish
	 * Set publish profile in global directory
	 */
	public function setNetPublish(bool $netPublish)
	{
		$this->netPublish = $netPublish;
	}
}
