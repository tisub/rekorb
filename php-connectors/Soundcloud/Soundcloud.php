<?php

define("__CLASSNAME__", "\\Soundcloud");

use com\busit as cb;
use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class Soundcloud implements cb\IConnector
{
	private $url = 'https://apps.busit.com/soundcloud/send';
	private $config;
	private $inputs;
	private $outputs;
	private $status = false;
	private $uid;
	private $messagelist;
	private $instance;
	
	public function init($config, $inputs, $outputs)
	{
		$this->uid = $config['uid'];
		$this->config = $config;
		$this->inputs = $inputs;
		$this->outputs = $outputs;
		
		$this->instance = new Services_Soundcloud('095fc14dcdbd7772e8925a86f6131283', '621c9b4681888b87548398018413c97d');
		$this->instance->setAccessToken($config['token']);
	}
	
	public function cron($message, $interfaceId)
	{
		$this->message = $message;
		$this->messagelist = new cb\MessageList();
		
		$mysql = new cad\mysql('sql', 'mysql-DUXJyTvD', 'gdx1n6hc', 'mysql-DUXJyTvD');	
		
		$this->url = 'https://apps.busit.com/soundcloud/pull';
		$result = file_get_contents($this->url . '?config=' . urlencode(json_encode($this->config)));
		$result = json_decode($result, true);
		
		// first time
		$sql = "SELECT * FROM buffer WHERE buffer_identifier = '{$this->uid}'";
		$row = $mysql->selectOne($sql);
		
		if( !$row['buffer_identifier'] )
		{
			$sql = "INSERT INTO buffer (buffer_identifier, buffer_time) VALUES ('{$this->uid}', '".time()."')";
			$mysql->insert($sql);
		}
		// end
		
		$result = array_reverse($result);
		if( count($result) > 0 )
		{
			foreach( $result as $r )
			{
				if( strlen($r['title']) > 1 )
				{
					$sql = "SELECT * FROM buffer WHERE buffer_identifier = '{$this->uid}' AND buffer_time >= '".strtotime($r['created_at'])."'";
					$row = $mysql->selectOne($sql);
				
					if( !$row['buffer_identifier'] )
					{
						$type = new cb\KnownType();
						$type->wkid(12);
						$type->name('Song');
						$type->format('[@title] - [@link]');
						$type->compatibility(array(12));
						
						$type['title'] = $r['title'];
						$type['description'] = $r['title'];
						$type['timestamp'] = strtotime($r['created_at']);
						$type['link'] = $r['permalink_url'];
						$type['date'] =  date('Y-m-d H:i:s', strtotime($r['created_at']));
						$start_memory = memory_get_usage();
						if( $r['download_url'] )
						{
							$content = $this->instance->download($r['id']);
							$this->message->addAttachment($r['title'], $content);
						}
						$type['size'] = ((memory_get_usage()-$start_memory)/1024/1024);
						
						$this->message->setKnownType($type);
						$this->messagelist[] = $this->message->duplicate();
						$this->status = true;
						
						$sql = "UPDATE buffer SET buffer_time = '".strtotime($r['created_at'])."' WHERE buffer_identifier = '{$this->uid}'";
						$mysql->update($sql);
					}
				}
			}
		}
	}
	
	public function setInput($message, $interfaceId)
	{
		$names = $message->getAttachmentNames();
		
		if( count($names) > 0 )
		{
			$n = $names[0];			
		
			$file['name'] = basename(str_replace("\\", "/", $n));
			$file['mime'] = 'application/octet-stream';
			$file['binary'] = $message->getAttachment($n);			
		}

		$msg = $message->getKnownType();
		if( $msg === null )
			$msg = $message->getContentUTF8();
		else
			$msg = $msg->toString();

		$this->send(array('message'=>json_encode($msg),'config'=>json_encode($this->config)), $file);
	}
	
	public function getOutput($interfaceId)
	{
		if( $this->status == true )
			return $this->message;
		else
			return null;			
	}
	
	public function send($params = array(), $file = null)
	{
		$boundary = "trlalaaaaaaaaaaaaaaaaalalalaalalaaaaaaaaaaa";
 
		$request = array( 'http' => array( 'user_agent' => 'PHP/5.x (Bus IT) API/1.0', 'method' => 'POST' ));
 
		if( $file !== null )
			$request['http']['content'] = self::buildMultipartQuery($params, $file);
		else
			$request['http']['content'] = http_build_query($params);
		
		$request['http']['header']  = 'Content-Length: ' . strlen($request['http']['content']) . "\r\n";
		
		if( $file !== null )
			$request['http']['header'] .= 'Content-Type: multipart/form-data, boundary=' . $boundary . "\r\n";
		else
			$request['http']['header'] .= 'Content-Type: application/x-www-form-urlencoded' . "\r\n";
 
		try
		{
			$fh = fopen($this->url, 'r', false, stream_context_create( $request ));
			if( $fh === false )
				throw new Exception("Internal communication error :: 500 :: The upstream API did not respond to");
 
			$response = stream_get_contents($fh);
			fclose($fh);
		}
		catch(Exception $e)
		{
			// get the E_WARNING from the fopen
			throw new Exception("Internal communication error :: 500 :: Upstream API failure :: ". $e->getMessage());
		}
	}
	
	public function buildMultipartQuery($params, $file)
	{
		$boundary = "trlalaaaaaaaaaaaaaaaaalalalaalalaaaaaaaaaaa";
		$content = '--' . $boundary . "\n";
		
		foreach( $params as $key => $value )
			$content .= 'content-disposition: form-data; name="' . $key . '"' . "\n\n" . $value . "\n" . '--' . $boundary . "\n";
		
		$content .= 'content-disposition: form-data; name="file"; filename="' . $file['name'] . '"' . "\n";
		$content .= 'Content-Type: ' . $file['mime'] . "\n";
		$content .= 'Content-Transfer-Encoding: binary' . "\n\n";
		$content .= $file['binary'];
		$content .= "\n" . '--' . $boundary . "\n";
 
		return $content;
	}
}

/**
 * SoundCloud API wrapper with support for authentication using OAuth 2
 *
 * @category  Services
 * @package   Services_Soundcloud
 * @author    Anton Lindqvist <anton@qvister.se>
 * @copyright 2010 Anton Lindqvist <anton@qvister.se>
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://github.com/mptre/php-soundcloud
 */
class Services_Soundcloud
{

    /**
     * Custom cURL option
     *
     * @var integer
     *
     * @access public
     */
    const CURLOPT_OAUTH_TOKEN = 173;

    /**
     * Access token returned by the service provider after a successful authentication
     *
     * @var string
     *
     * @access private
     */
    private $_accessToken;

    /**
     * Version of the API to use
     *
     * @var integer
     *
     * @access private
     * @static
     */
    private static $_apiVersion = 1;

    /**
     * Supported audio MIME types
     *
     * @var array
     *
     * @access private
     * @static
     */
    private static $_audioMimeTypes = array(
        'aac' => 'video/mp4',
        'aiff' => 'audio/x-aiff',
        'flac' => 'audio/flac',
        'mp3' => 'audio/mpeg',
        'ogg' => 'audio/ogg',
        'wav' => 'audio/x-wav'
    );

    /**
     * OAuth client id
     *
     * @var string
     *
     * @access private
     */
    private $_clientId;

    /**
     * OAuth client secret
     *
     * @var string
     *
     * @access private
     */
    private $_clientSecret;

    /**
     * Default cURL options
     *
     * @var array
     *
     * @access private
     * @static
     */
     private static $_curlDefaultOptions = array(
         CURLOPT_HEADER => true,
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_USERAGENT => ''
     );

    /**
     * cURL options
     *
     * @var array
     *
     * @access private
     */
    private $_curlOptions;

    /**
     * Development mode
     *
     * @var boolean
     *
     * @access private
     */
     private $_development;

    /**
     * Available API domains
     *
     * @var array
     *
     * @access private
     * @static
     */
    private static $_domains = array(
        'development' => 'sandbox-soundcloud.com',
        'production' => 'soundcloud.com'
    );

    /**
     * HTTP response body from the last request
     *
     * @var string
     *
     * @access private
     */
    private $_lastHttpResponseBody;

    /**
     * HTTP response code from the last request
     *
     * @var integer
     *
     * @access private
     */
    private $_lastHttpResponseCode;

    /**
     * HTTP response headers from last request
     *
     * @var array
     *
     * @access private
     */
    private $_lastHttpResponseHeaders;

    /**
     * OAuth paths
     *
     * @var array
     *
     * @access private
     * @static
     */
    private static $_paths = array(
        'authorize' => 'connect',
        'access_token' => 'oauth2/token',
    );

    /**
     * OAuth redirect URI
     *
     * @var string
     *
     * @access private
     */
    private $_redirectUri;

    /**
     * API response format MIME type
     *
     * @var string
     *
     * @access private
     */
    private $_requestFormat;

    /**
     * Available response formats
     *
     * @var array
     *
     * @access private
     * @static
     */
    private static $_responseFormats = array(
        '*' => '*/*',
        'json' => 'application/json',
        'xml' => 'application/xml'
    );

    /**
     * HTTP user agent
     *
     * @var string
     *
     * @access private
     * @static
     */
    private static $_userAgent = 'PHP-SoundCloud';

    /**
     * Class constructor
     *
     * @param string  $clientId     OAuth client id
     * @param string  $clientSecret OAuth client secret
     * @param string  $redirectUri  OAuth redirect URI
     * @param boolean $development  Sandbox mode
     *
     * @return void
     * @throws Services_Soundcloud_Missing_Client_Id_Exception
     *
     * @access public
     */
    function __construct($clientId, $clientSecret, $redirectUri = null, $development = false)
    {
        if (empty($clientId)) {
            throw new Services_Soundcloud_Missing_Client_Id_Exception();
        }

        $this->_clientId = $clientId;
        $this->_clientSecret = $clientSecret;
        $this->_redirectUri = $redirectUri;
        $this->_development = $development;
        $this->_responseFormat = self::$_responseFormats['json'];
        $this->_curlOptions = self::$_curlDefaultOptions;
        $this->_curlOptions[CURLOPT_USERAGENT] .= $this->_getUserAgent();
    }

    /**
     * Get authorization URL
     *
     * @param array $params Optional query string parameters
     *
     * @return string
     *
     * @access public
     * @see Soundcloud::_buildUrl()
     */
    function getAuthorizeUrl($params = array())
    {
        $defaultParams = array(
            'client_id' => $this->_clientId,
            'redirect_uri' => $this->_redirectUri,
            'response_type' => 'code'
        );
        $params = array_merge($defaultParams, $params);

        return $this->_buildUrl(self::$_paths['authorize'], $params, false);
    }

    /**
     * Get access token URL
     *
     * @param array $params Optional query string parameters
     *
     * @return string
     *
     * @access public
     * @see Soundcloud::_buildUrl()
     */
    function getAccessTokenUrl($params = array())
    {
        return $this->_buildUrl(self::$_paths['access_token'], $params, false);
    }

    /**
     * Retrieve access token through credentials flow
     *
     * @param string $username Username
     * @param string $password Password
     *
     * @return mixed
     *
     * @access public
     */
    function credentialsFlow($username, $password)
    {
        $postData = array(
            'client_id' => $this->_clientId,
            'client_secret' => $this->_clientSecret,
            'username' => $username,
            'password' => $password,
            'grant_type' => 'password'
        );

        $options = array(CURLOPT_POST => true, CURLOPT_POSTFIELDS => $postData);
        $response = json_decode(
            $this->_request($this->getAccessTokenUrl(), $options),
            true
        );

        if (array_key_exists('access_token', $response)) {
            $this->_accessToken = $response['access_token'];

            return $response;
        } else {
            return false;
        }
    }

    /**
     * Retrieve access token
     *
     * @param string $code        Optional OAuth code returned from the service provider
     * @param array  $postData    Optional post data
     * @param array  $curlOptions Optional cURL options
     *
     * @return mixed
     *
     * @access public
     * @see Soundcloud::_getAccessToken()
     */
    function accessToken($code = null, $postData = array(), $curlOptions = array())
    {
        $defaultPostData = array(
            'code' => $code,
            'client_id' => $this->_clientId,
            'client_secret' => $this->_clientSecret,
            'redirect_uri' => $this->_redirectUri,
            'grant_type' => 'authorization_code'
        );
        $postData = array_filter(array_merge($defaultPostData, $postData));

        return $this->_getAccessToken($postData, $curlOptions);
    }

    /**
     * Refresh access token
     *
     * @param string $refreshToken The token to refresh
     * @param array  $postData     Optional post data
     * @param array  $curlOptions  Optional cURL options
     *
     * @return mixed
     * @see Soundcloud::_getAccessToken()
     *
     * @access public
     */
    function accessTokenRefresh($refreshToken, $postData = array(), $curlOptions = array())
    {
        $defaultPostData = array(
            'refresh_token' => $refreshToken,
            'client_id' => $this->_clientId,
            'client_secret' => $this->_clientSecret,
            'redirect_uri' => $this->_redirectUri,
            'grant_type' => 'refresh_token'
        );
        $postData = array_merge($defaultPostData, $postData);

        return $this->_getAccessToken($postData, $curlOptions);
    }

    /**
     * Get access token
     *
     * @return mixed
     *
     * @access public
     */
    function getAccessToken()
    {
        return $this->_accessToken;
    }

    /**
     * Get API version
     *
     * @return integer
     *
     * @access public
     */
    function getApiVersion()
    {
        return self::$_apiVersion;
    }

    /**
     * Get the corresponding MIME type for a given file extension
     *
     * @param string $extension Given extension
     *
     * @return string
     * @throws Services_Soundcloud_Unsupported_Audio_Format_Exception
     *
     * @access public
     */
    function getAudioMimeType($extension)
    {
        if (array_key_exists($extension, self::$_audioMimeTypes)) {
            return self::$_audioMimeTypes[$extension];
        } else {
            throw new Services_Soundcloud_Unsupported_Audio_Format_Exception();
        }
    }

    /**
     * Get cURL options
     *
     * @param string $key Optional options key
     *
     * @return mixed
     *
     * @access public
     */
    function getCurlOptions($key = null)
    {
        if ($key) {
            return (array_key_exists($key, $this->_curlOptions))
                ? $this->_curlOptions[$key]
                : false;
        } else {
            return $this->_curlOptions;
        }
    }

    /**
     * Get development mode
     *
     * @return boolean
     *
     * @access public
     */
    function getDevelopment()
    {
        return $this->_development;
    }

    /**
     * Get HTTP response header
     *
     * @param string $header Name of the header
     *
     * @return mixed
     *
     * @access public
     */
    function getHttpHeader($header)
    {
        if (is_array($this->_lastHttpResponseHeaders)
            && array_key_exists($header, $this->_lastHttpResponseHeaders)
        ) {
            return $this->_lastHttpResponseHeaders[$header];
        } else {
            return false;
        }
    }

    /**
     * Get redirect URI
     *
     * @return string
     *
     * @access public
     */
    function getRedirectUri()
    {
        return $this->_redirectUri;
    }

    /**
     * Get response format
     *
     * @return string
     *
     * @access public
     */
    function getResponseFormat()
    {
        return $this->_responseFormat;
    }

    /**
     * Set access token
     *
     * @param string $accessToken Access token
     *
     * @return object
     *
     * @access public
     */
    function setAccessToken($accessToken)
    {
        $this->_accessToken = $accessToken;

        return $this;
    }

    /**
     * Set cURL options
     *
     * The method accepts arguments in two ways.
     *
     * You could pass two arguments when adding a single option.
     * <code>
     * $soundcloud->setCurlOptions(CURLOPT_SSL_VERIFYHOST, 0);
     * </code>
     *
     * You could also pass an associative array when adding multiple options.
     * <code>
     * $soundcloud->setCurlOptions(array(
     *     CURLOPT_SSL_VERIFYHOST => 0,
     *    CURLOPT_SSL_VERIFYPEER => 0
     * ));
     * </code>
     *
     * @return object
     *
     * @access public
     */
    function setCurlOptions()
    {
        $args = func_get_args();
        $options = (is_array($args[0]))
            ? $args[0]
            : array($args[0] => $args[1]);

        foreach ($options as $key => $val) {
            $this->_curlOptions[$key] = $val;
        }

        return $this;
    }

    /**
     * Set redirect URI
     *
     * @param string $redirectUri Redirect URI
     *
     * @return object
     *
     * @access public
     */
    function setRedirectUri($redirectUri)
    {
        $this->_redirectUri = $redirectUri;

        return $this;
    }

    /**
     * Set response format
     *
     * @param string $format Response format, could either be XML or JSON
     *
     * @return object
     * @throws Services_Soundcloud_Unsupported_Response_Format_Exception
     *
     * @access public
     */
    function setResponseFormat($format)
    {
        if (array_key_exists($format, self::$_responseFormats)) {
            $this->_responseFormat = self::$_responseFormats[$format];
        } else {
            throw new Services_Soundcloud_Unsupported_Response_Format_Exception();
        }

        return $this;
    }

    /**
     * Set development mode
     *
     * @param boolean $development Development mode
     *
     * @return object
     *
     * @access public
     */
    function setDevelopment($development)
    {
        $this->_development = $development;

        return $this;
    }

    /**
     * Send a GET HTTP request
     *
     * @param string $path        Request path
     * @param array  $params      Optional query string parameters
     * @param array  $curlOptions Optional cURL options
     *
     * @return mixed
     *
     * @access public
     * @see Soundcloud::_request()
     */
    function get($path, $params = array(), $curlOptions = array())
    {
        $url = $this->_buildUrl($path, $params);

        return $this->_request($url, $curlOptions);
    }

    /**
     * Send a POST HTTP request
     *
     * @param string $path        Request path
     * @param array  $postData    Optional post data
     * @param array  $curlOptions Optional cURL options
     *
     * @return mixed
     *
     * @access public
     * @see Soundcloud::_request()
     */
    function post($path, $postData = array(), $curlOptions = array())
    {
        $url = $this->_buildUrl($path);
        $options = array(CURLOPT_POST => true, CURLOPT_POSTFIELDS => $postData);
        $options += $curlOptions;

        return $this->_request($url, $options);
    }

    /**
     * Send a PUT HTTP request
     *
     * @param string $path        Request path
     * @param array  $postData    Optional post data
     * @param array  $curlOptions Optional cURL options
     *
     * @return mixed
     *
     * @access public
     * @see Soundcloud::_request()
     */
    function put($path, $postData, $curlOptions = array())
    {
        $url = $this->_buildUrl($path);
        $options = array(
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $postData
        );
        $options += $curlOptions;

        return $this->_request($url, $options);
    }

    /**
     * Send a DELETE HTTP request
     *
     * @param string $path        Request path
     * @param array  $params      Optional query string parameters
     * @param array  $curlOptions Optional cURL options
     *
     * @return mixed
     *
     * @access public
     * @see Soundcloud::_request()
     */
    function delete($path, $params = array(), $curlOptions = array())
    {
        $url = $this->_buildUrl($path, $params);
        $options = array(CURLOPT_CUSTOMREQUEST => 'DELETE');
        $options += $curlOptions;

        return $this->_request($url, $options);
    }

    /**
     * Download track
     *
     * @param integer $trackId     Track id to download
     * @param array   $params      Optional query string parameters
     * @param array   $curlOptions Optional cURL options
     *
     * @return mixed
     *
     * @access public
     * @see Soundcloud::_request()
     */
    function download($trackId, $params = array(), $curlOptions = array())
    {
        $lastResponseFormat = array_pop(explode('/', $this->getResponseFormat()));
        $defaultParams = array('oauth_token' => $this->getAccessToken());
        $defaultCurlOptions = array(
            CURLOPT_FOLLOWLOCATION => true,
            self::CURLOPT_OAUTH_TOKEN => false
        );
        $url = $this->_buildUrl(
            'tracks/' . $trackId . '/download',
            array_merge($defaultParams, $params)
        );
        $options = $defaultCurlOptions + $curlOptions;

        $this->setResponseFormat('*');

        $response = $this->_request($url, $options);

        // rollback to the previously defined response format.
        $this->setResponseFormat($lastResponseFormat);

        return $response;
    }

    /**
     * Update a existing playlist
     *
     * @param integer $playlistId       The playlist id
     * @param array   $trackIds         Tracks to add to the playlist
     * @param array   $optionalPostData Optional playlist fields to update
     *
     * @return mixed
     *
     * @access public
     * @see Soundcloud::_request()
     */
    public function updatePlaylist($playlistId, $trackIds, $optionalPostData = null)
    {
        $url = $this->_buildUrl('playlists/' . $playlistId);
        $postData = array();

        foreach ($trackIds as $trackId) {
            array_push($postData, 'playlist[tracks][][id]=' . $trackId);
        }

        if (is_array($optionalPostData)) {
            foreach ($optionalPostData as $key => $val) {
                array_push($postData, 'playlist[' . $key . ']=' . $val);
            }
        }

        $postData = implode('&', $postData);
        $curlOptions = array(
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_HTTPHEADER => array('Content-Length' => strlen($postData)),
            CURLOPT_POSTFIELDS => $postData
        );

        return $this->_request($url, $curlOptions);
    }

    /**
     * Construct default HTTP request headers
     *
     * @param boolean $includeAccessToken Include access token
     *
     * @return array $headers
     *
     * @access protected
     */
    protected function _buildDefaultHeaders($includeAccessToken = true)
    {
        $headers = array();

        if ($this->_responseFormat) {
            array_push($headers, 'Accept: ' . $this->_responseFormat);
        }

        if ($includeAccessToken && $this->_accessToken) {
            array_push($headers, 'Authorization: OAuth ' . $this->_accessToken);
        }

        return $headers;
    }

    /**
     * Construct a URL
     *
     * @param string  $path           Relative or absolute URI
     * @param array   $params         Optional query string parameters
     * @param boolean $includeVersion Include API version
     *
     * @return string $url
     *
     * @access protected
     */
    protected function _buildUrl($path, $params = array(), $includeVersion = true)
    {
        if (!$this->_accessToken) {
            $params['consumer_key'] = $this->_clientId;
        }

        if (preg_match('/^https?\:\/\//', $path)) {
            $url = $path;
        } else {
            $url = 'https://';
            $url .= (!preg_match('/connect/', $path)) ? 'api.' : '';
            $url .= ($this->_development)
                ? self::$_domains['development']
                : self::$_domains['production'];
            $url .= '/';
            $url .= ($includeVersion) ? 'v' . self::$_apiVersion . '/' : '';
            $url .= $path;
        }

        $url .= (count($params)) ? '?' . http_build_query($params) : '';

        return $url;
    }

    /**
     * Retrieve access token
     *
     * @param array $postData    Post data
     * @param array $curlOptions Optional cURL options
     *
     * @return mixed
     *
     * @access protected
     */
    protected function _getAccessToken($postData, $curlOptions = array())
    {
        $options = array(CURLOPT_POST => true, CURLOPT_POSTFIELDS => $postData);
        $options += $curlOptions;
        $response = json_decode(
            $this->_request($this->getAccessTokenUrl(), $options),
            true
        );

        if (array_key_exists('access_token', $response)) {
            $this->_accessToken = $response['access_token'];

            return $response;
        } else {
            return false;
        }
    }

    /**
     * Get HTTP user agent
     *
     * @return string
     *
     * @access protected
     */
    protected function _getUserAgent()
    {
        return self::$_userAgent . '/' . new Services_Soundcloud_Version;
    }

    /**
     * Parse HTTP headers
     *
     * @param string $headers HTTP headers
     *
     * @return array $parsedHeaders
     *
     * @access protected
     */
    protected function _parseHttpHeaders($headers)
    {
        $headers = explode("\n", trim($headers));
        $parsedHeaders = array();

        foreach ($headers as $header) {
            if (!preg_match('/\:\s/', $header)) {
                continue;
            }

            list($key, $val) = explode(': ', $header, 2);
            $key = str_replace('-', '_', strtolower($key));
            $val = trim($val);

            $parsedHeaders[$key] = $val;
        }

        return $parsedHeaders;
    }

    /**
     * Validate HTTP response code
     *
     * @param integer $code HTTP code
     *
     * @return boolean
     *
     * @access protected
     */
    protected function _validResponseCode($code)
    {
        return (bool)preg_match('/^20[0-9]{1}$/', $code);
    }

    /**
     * Performs the actual HTTP request using cURL
     *
     * @param string $url         Absolute URL to request
     * @param array  $curlOptions Optional cURL options
     *
     * @return mixed
     * @throws Services_Soundcloud_Invalid_Http_Response_Code_Exception
     *
     * @access protected
     */
    protected function _request($url, $curlOptions = array())
    {
        $ch = curl_init($url);
        $options = $this->_curlOptions;
        $options += $curlOptions;

        if (array_key_exists(self::CURLOPT_OAUTH_TOKEN, $options)) {
            $includeAccessToken = $options[self::CURLOPT_OAUTH_TOKEN];
            unset($options[self::CURLOPT_OAUTH_TOKEN]);
        } else {
            $includeAccessToken = true;
        }

        if (array_key_exists(CURLOPT_HTTPHEADER, $options)) {
            $options[CURLOPT_HTTPHEADER] = array_merge(
                $this->_buildDefaultHeaders(),
                $curlOptions[CURLOPT_HTTPHEADER]
            );
        } else {
            $options[CURLOPT_HTTPHEADER] = $this->_buildDefaultHeaders(
                $includeAccessToken
            );
        }

        curl_setopt_array($ch, $options);

        $data = curl_exec($ch);
        $info = curl_getinfo($ch);

        curl_close($ch);

        if (array_key_exists(CURLOPT_HEADER, $options) && $options[CURLOPT_HEADER]) {
            $this->_lastHttpResponseHeaders = $this->_parseHttpHeaders(
                substr($data, 0, $info['header_size'])
            );
            $this->_lastHttpResponseBody = substr($data, $info['header_size']);
        } else {
            $this->_lastHttpResponseHeaders = array();
            $this->_lastHttpResponseBody = $data;
        }

        $this->_lastHttpResponseCode = $info['http_code'];

        if ($this->_validResponseCode($this->_lastHttpResponseCode)) {
            return $this->_lastHttpResponseBody;
        } else {
            throw new Services_Soundcloud_Invalid_Http_Response_Code_Exception(
                null,
                0,
                $this->_lastHttpResponseBody,
                $this->_lastHttpResponseCode
            );
        }
    }
}

/**
 * Soundcloud missing client id exception.
 *
 * @category Services
 * @package Services_Soundcloud
 * @author Anton Lindqvist <anton@qvister.se>
 * @copyright 2010 Anton Lindqvist <anton@qvister.se>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://github.com/mptre/php-soundcloud
 */
class Services_Soundcloud_Missing_Client_Id_Exception extends Exception {

    /**
     * Default message.
     *
     * @access protected
     *
     * @var string
     */
    protected $message = 'All requests must include a consumer key. Referred to as client_id in OAuth2.';

}

/**
 * Soundcloud invalid HTTP response code exception.
 *
 * @category Services
 * @package Services_Soundcloud
 * @author Anton Lindqvist <anton@qvister.se>
 * @copyright 2010 Anton Lindqvist <anton@qvister.se>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://github.com/mptre/php-soundcloud
 */
class Services_Soundcloud_Invalid_Http_Response_Code_Exception extends Exception {

    /**
     * HTTP response body.
     *
     * @access protected
     *
     * @var string
     */
    protected $httpBody;

    /**
     * HTTP response code.
     *
     * @access protected
     *
     * @var integer
     */
    protected $httpCode;

    /**
     * Default message.
     *
     * @access protected
     *
     * @var string
     */
    protected $message = 'The requested URL responded with HTTP code %d.';

    /**
     * Constructor.
     *
     * @param string $message
     * @param string $code
     * @param string $httpBody
     * @param integer $httpCode
     *
     * @return void
     */
    function __construct($message = null, $code = 0, $httpBody = null, $httpCode = 0) {
        $this->httpBody = $httpBody;
        $this->httpCode = $httpCode;
        $message = sprintf($this->message, $httpCode);

        parent::__construct($message, $code);
    }

    /**
     * Get HTTP response body.
     *
     * @return mixed
     */
    function getHttpBody() {
        return $this->httpBody;
    }

    /**
     * Get HTTP response code.
     *
     * @return mixed
     */
    function getHttpCode() {
        return $this->httpCode;
    }

}

/**
 * Soundcloud unsupported response format exception.
 *
 * @category Services
 * @package Services_Soundcloud
 * @author Anton Lindqvist <anton@qvister.se>
 * @copyright 2010 Anton Lindqvist <anton@qvister.se>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://github.com/mptre/php-soundcloud
 */
class Services_Soundcloud_Unsupported_Response_Format_Exception extends Exception {

    /**
     * Default message.
     *
     * @access protected
     *
     * @var string
     */
    protected $message = 'The given response format is unsupported.';

}

/**
 * Soundcloud unsupported audio format exception.
 *
 * @category Services
 * @package Services_Soundcloud
 * @author Anton Lindqvist <anton@qvister.se>
 * @copyright 2010 Anton Lindqvist <anton@qvister.se>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://github.com/mptre/php-soundcloud
 */
class Services_Soundcloud_Unsupported_Audio_Format_Exception extends Exception {

    /**
     * Default message.
     *
     * @access protected
     *
     * @var string
     */
    protected $message = 'The given audio format is unsupported.';

}

/**
 * Soundcloud package version
 *
 * @category  Services
 * @package   Services_Soundcloud
 * @author    Anton Lindqvist <anton@qvister.se>
 * @copyright 2010 Anton Lindqvist <anton@qvister.se>
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://github.com/mptre/php-soundcloud
 */
class Services_Soundcloud_Version
{

    const MAJOR = 2;
    const MINOR = 3;
    const PATCH = 2;

    /**
     * Magic to string method
     *
     * @return string
     *
     * @access public
     */
    function __toString()
    {
        return implode('.', array(self::MAJOR, self::MINOR, self::PATCH));
    }

}

?>