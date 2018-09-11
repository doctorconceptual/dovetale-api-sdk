<?php

namespace DoveTale;

class Client {

	static $baseURL = 'https://api.dovetale.com/v2/';
	static $authURL = 'https://api.dovetale.com/oauth/token';

	const PROFILE_TYPE_URL = 'url';
	const PROFILE_TYPE_USERNAME =  'username';
	const PROFILE_TYPE_PLATFORM_ID = 'platformid';

	const PLATFORM_INSTAGRAM = 'instagram';
	const PLATFORM_TWITTER = 'twitter';
	const PLATFORM_YOUTUBE = 'youtube';
	const PLATFORM_FACEBOOK = 'facebook';
	const PLATOFRM_TWITCH = 'twitch';

	const METHOD_GET = 'get';
	const METHOD_POST = 'post';

	private $accessToken;

	public function __construct($clientID = null, $clientSecret = null)
	{
		if (empty($clientID) || empty($clientSecret))
			throw new Required_Param_Missing_Exception();

		$this->clientID = $clientID;
		$this->clientSecret = $clientSecret;
		$this->authorize();
	}

	/**
		* Authorize credentials to obtain an access token for subsequent calls
	*/
	public function authorize()
	{
		$headers = [
			'content-type: application/x-www-form-urlencoded',
			'cache-control: no-cache'
		];

		$data['grant_type'] = 'client_credentials';
		$data['client_id'] = $this->clientID;
		$data['client_secret'] = $this->clientSecret;
		$data['scope'] = 'enterprise_api';

		$ch = curl_init(self::$authURL);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$output = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if (!$output || $httpCode != 200)
			throw new Auth_Failed_Exception();

		$output = json_decode($output);
		if (!isset($output->access_token))
			throw new Auth_Failed_Exception();

		$this->accessToken = $output->access_token;
	}

	/**
		* Add a profile to DoveTale List. This is used to add a profile to DoveTale and DoveTale will update its data
		* @param $listID numeric list ID on DoveTale
		* @param $profileType url|username
		* @param $profile unique identifier based on profileType
		* @param $platform instagram|twitter|youtube|facebook|twitch; required for profileType username
		* @return response object containing profile data or a message that the profile is being added to DoveTale
	*/
	public function add_profile_to_list($listID, $profileType = self::PROFILE_TYPE_USERNAME, $profile = null, $platform = null)
	{
		$listID = (int) $listID;
		if (!$listID || !$profile)
			throw new Required_Param_Missing_Exception();

		$url = self::$baseURL . "lists/{$listID}";
		$headers = ['content-type: application/x-www-form-urlencoded',
				'cache-control: no-cache'];

		switch ($profileType)
		{
			case self::PROFILE_TYPE_URL:
				$params = ['url' => $profile];
				break;
			case self::PROFILE_TYPE_USERNAME:
				if (!$platform)
					throw new Required_Param_Missing_Exception();

				$params = ['platform' => $platform, 'username' => $profile];
				break;
		}

		return $this->make_call($url, self::METHOD_POST, $params, $headers);
	}

	/**
		* Get paginated results from a list on DoveTale
		* @param $listID numeric list ID on DoveTale
		* @param $pageNum page number from paginated list
		* @return response object containing profiles from the list
	*/
	public function get_list($listID, $pageNum = 1)
	{
		$listID = (int) $listID;
		$page = (int) $pageNum;
		if (!$listID || !$pageNum)
			throw new Required_Param_Missing_Exception();

		$url = self::$baseURL . "lists/{$listID}?page={$pageNum}";
		return $this->make_call($url, self::METHOD_GET);
	}

	/**
		* Get data from DoveTale based on social profile URL
		* @param $socialProfileURL full URL of a social profile e.g. https://twitter.com/XCELTALENT
		* @return response object containing profile data
	*/
	public function get_profile_by_url($socialProfileURL = null)
	{
		if (empty($socialProfileURL))
			throw new Required_Param_Missing_Exception();

		return $this->get_profile($socialProfileURL, self::PROFILE_TYPE_URL);
	}

	/**
		* Get data from DoveTale based on Instagram username
		* @param $igUsername Instagram username
		* @return response object containing profile data
	*/
	public function get_instagram_profile_by_username($igUsername = null)
	{
		if (empty($igUsername))
			throw new Required_Param_Missing_Exception();

		return $this->get_profile($igUsername, self::PROFILE_TYPE_USERNAME, self::PLATFORM_INSTAGRAM);
	}

	/**
		* Get data from DoveTale based on Instagram numeric profile_id
		* @param $igProfileID Instagram profile id, e.g. 6066860758
		* @return response object containing profile data
	*/
	public function get_instagram_profile_by_profile_id($igProfileID = null)
	{
		if (empty($igProfileID))
			throw new Required_Param_Missing_Exception();

		return $this->get_profile($igProfileID, self::PROFILE_TYPE_PLATFORM_ID, self::PLATFORM_INSTAGRAM);
	}

	/**
		* Get data from DoveTale based on Twitter username
		* @param $twitterUsername Twitter username
		* @return response object containing profile data
	*/
	public function get_twitter_profile_by_username($twitterUsername = null)
	{
		if (empty($twitterUsername))
			throw new Required_Param_Missing_Exception();

		return $this->get_profile($twitterUsername, self::PROFILE_TYPE_USERNAME, self::PLATFORM_TWITTER);
	}

	/**
		* Get data from DoveTale based on Twitter numeric profile_id
		* @param $twitterProfileID Twitter profile ID, e.g. 29991757
		* @return response object containing profile data
	*/
	public function get_twitter_profile_by_profile_id($twitterProfileID = null)
	{
		if (empty($twitterProfileID))
			throw new Required_Param_Missing_Exception();

		return $this->get_profile($twitterProfileID, self::PROFILE_TYPE_PLATFORM_ID, self::PLATFORM_TWITTER);
	}

	/**
		* Get data from DoveTale based on Facebook username
		* @param $fbUsername Facebook username
		* @return response object containing profile data
	*/
	public function get_facebook_profile_by_username($fbUsername = null)
	{
		if (empty($fbUsername))
			throw new Required_Param_Missing_Exception();

		return $this->get_profile($fbUsername, self::PROFILE_TYPE_USERNAME, self::PLATFORM_FACEBOOK);
	}

	/**
		* Get data from DoveTale based on Facebook numeric profile_id
		* @param $fbProfileID Facebook profile ID e.g. 439037252899560
		* @return response object containing profile data
	*/
	public function get_facebook_profile_by_profile_id($fbProfileID = null)
	{
		if (empty($fbProfileID))
			throw new Required_Param_Missing_Exception();

		return $this->get_profile($fbProfileID, self::PROFILE_TYPE_PLATFORM_ID, self::PLATFORM_FACEBOOK);
	}

	/**
		* Get data from DoveTale based on Youtube username
		* @param $youtubeUsername Youtube username
		* @return response object containing profile data
	*/
	public function get_youtube_profile_by_username($youtubeUsername = null)
	{
		if (empty($youtubeUsername))
			throw new Required_Param_Missing_Exception();

		return $this->get_profile($youtubeUsername, self::PROFILE_TYPE_USERNAME, self::PLATFORM_YOUTUBE);
	}

	/**
		* Get data from DoveTale based on Youtube numeric profile_id
		* @param $youtubeProfileID Youtube profile ID
		* @return response object containing profile data
	*/
	public function get_youtube_profile_by_profile_id($youtubeProfileID = null)
	{
		if (empty($youtubeProfileID))
			throw new Required_Param_Missing_Exception();

		return $this->get_profile($youtubeProfileID, self::PROFILE_TYPE_PLATFORM_ID, self::PLATFORM_YOUTUBE);
	}

	/**
		* Get data from DoveTale based on Twitch username
		* @param $twitchUsername Twitch username
		* @return response object containing profile data
	*/
	public function get_twitch_profile_by_username($twitchUsername = null)
	{
		if (empty($twitchUsername))
			throw new Required_Param_Missing_Exception();

		return $this->get_profile($twitchUsername, self::PROFILE_TYPE_USERNAME, self::PLATOFRM_TWITCH);
	}

	/**
		* Get data from DoveTale based on Twitch numeric profile_id
		* @param $twitchProfileID Twitch profile ID e.g. 12378973
		* @return response object containing profile data
	*/
	public function get_twitch_profile_by_profile_id($twitchProfileID = null)
	{
		if (empty($twitchProfileID))
			throw new Required_Param_Missing_Exception();

		return $this->get_profile($twitchProfileID, self::PROFILE_TYPE_PLATFORM_ID, self::PLATOFRM_TWITCH);
	}

	/**
		* Get a social profile from DoveTale
		* @param $profile unique identifier based on profileType; MUST have been added to Dovetale
		* @param $profileType url|username|platformid
		* @param $platform instagram|twitter|youtube|facebook|twitch; required for profileType username or platform id
		* @return response object containing profile data
	*/
	private function get_profile($profile, $profileType = self::PROFILE_TYPE_USERNAME, $platform = null)
	{
		$url = self::$baseURL . "accounts";
		switch ($profileType)
		{
			case self::PROFILE_TYPE_URL:
				$params = ['url' => $profile];
				break;
			case self::PROFILE_TYPE_USERNAME:
				$params = ['platform' => $platform, 'username' => $profile];
				break;
			case self::PROFILE_TYPE_PLATFORM_ID:
				$params = ['platform' => $platform, 'platform_id' => $profile];
				break;
		}

		return $this->make_call($url, self::METHOD_GET, $params);
	}

	/**
		* Make a call to DoveTale API
		* @param $url e.g. https://api.dovetale.com/v2/accounts
		* @param $method either get or post
		* @param $params array of parameters to be associated with the call
		* @param $headers (optional) if any headers to be added to the request
		* @return response from API
	*/
	public function make_call($url, $method = self::METHOD_GET, $params = [], $headers = [])
	{
		$authString = "Bearer {$this->accessToken}";
		$reqHeaders = ["authorization: {$authString}"];

		if (count($headers))
		{
			foreach ($headers as $i => $header)
				$reqHeaders[] = $header;
		}


		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $reqHeaders);
		if ($method == self::METHOD_POST)
		{
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		}
		else
		{
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		}

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$output = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($output)
			$output = json_decode($output);

		return $output;

	}
}
