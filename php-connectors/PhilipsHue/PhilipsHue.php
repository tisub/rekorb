<?php

define("__CLASSNAME__", "\\PhilipsHue");

use com\busit as cb;
use com\anotherservice\util as cau;

class PhilipsHue implements cb\IConnector
{
	private $config;
	private $inputs;
	private $outputs;
	private $message;

	public function init($config, $inputs, $outputs)
	{
		$this->config = $config;
		$this->inputs = $inputs;
		$this->outputs = $outputs;
		$this->url = 'http://' . $this->config['bridge'];
	}
	
	public function cron($message, $interfaceId)
	{
		$this->message = $message;
	
		$type = new cb\KnownType();
		$type->wkid(25);
		$type->name('LightStatus');
		$type->compatibility(array(25));
		$type->format('[@light_name]: [@light_status]');
						
		$response = Unirest::get($this->url . '/api/' . $this->config['user'] . '/lights/' . $this->config['light'], array());
		$response = json_decode($response->raw_body, true);
		
		$type['light_status'] = $response['state']['on'];
		$type['light_color'] = $response['state']['hue'];
		$type['light_brightness'] = $response['state']['bri'];
		$type['light_saturation'] = $response['state']['sat'];
		$type['light_name'] = $response['name'];
		$type['timestamp'] = time();
		$type['date'] =  date('Y-m-d H:i:s', time());
		
		$this->message->setKnownType($type);
	}
	
	public function setInput($message, $interfaceId)
	{
		$msg = $message->getKnownType();
		
		if( $msg['color'] != null )
			$color = $msg['color'];
		else
			$color = rand(0, 65280);
		
		switch( $this->inputs[$interfaceId]['key'] )
		{
			case 'blink':
				$response = Unirest::get($this->url . '/api/' . $this->config['user'] . '/lights/' . $this->config['light'], array());
				$response = json_decode($response->raw_body, true);
				
				if( $response['state']['on'] == 1 )
				{
					$body = '{"on": false}';
					$url = $this->url . '/api/' . $this->config['user'] . '/lights/' . $this->config['light'] . '/state';
					Unirest::put("{$url}", array(), $body);
					
					$body = '{"on": true, "bri": 255, "sat": 255}';
					$url = $this->url . '/api/' . $this->config['user'] . '/lights/' . $this->config['light'] . '/state';
					Unirest::put("{$url}", array(), $body);
				}
				else
				{
					$body = '{"on": true, "bri": 255, "sat": 255}';
					$url = $this->url . '/api/' . $this->config['user'] . '/lights/' . $this->config['light'] . '/state';
					Unirest::put("{$url}", array(), $body);
					
					$body = '{"on": false}';
					$url = $this->url . '/api/' . $this->config['user'] . '/lights/' . $this->config['light'] . '/state';
					Unirest::put("{$url}", array(), $body);
				}
			break;
			case 'blue':
				$body = '{"hue": 46920, "bri": 200, "sat": 255}';
				$url = $this->url . '/api/' . $this->config['user'] . '/lights/' . $this->config['light'] . '/state';
				Unirest::put("{$url}", array(), $body);
			break;
			case 'color':
				$body = '{"hue": ' . $color . ', "bri": 200, "sat": 255}';
				$url = $this->url . '/api/' . $this->config['user'] . '/lights/' . $this->config['light'] . '/state';
				Unirest::put("{$url}", array(), $body);
			break;
			case 'green':
				$body = '{"hue": 25500, "bri": 200, "sat": 255}';
				$url = $this->url . '/api/' . $this->config['user'] . '/lights/' . $this->config['light'] . '/state';
				Unirest::put("{$url}", array(), $body);
			break;
			case 'off':
				$body = '{"on": false}';
				$url = $this->url . '/api/' . $this->config['user'] . '/lights/' . $this->config['light'] . '/state';
				Unirest::put("{$url}", array(), $body);
			break;
			case 'on':
				$body = '{"on": true}';
				$url = $this->url . '/api/' . $this->config['user'] . '/lights/' . $this->config['light'] . '/state';
				Unirest::put("{$url}", array(), $body);
			break;
			case 'purple':
				$body = '{"hue": 56100, "bri": 200, "sat": 255}';
				$url = $this->url . '/api/' . $this->config['user'] . '/lights/' . $this->config['light'] . '/state';
				Unirest::put("{$url}", array(), $body);
			break;
			case 'red':
				$body = '{"hue": 65280, "bri": 200, "sat": 255}';
				$url = $this->url . '/api/' . $this->config['user'] . '/lights/' . $this->config['light'] . '/state';
				Unirest::put("{$url}", array(), $body);
			break;
			case 'white':
				$body = '{"hue": 12750, "bri": 200, "sat": 255}';
				$url = $this->url . '/api/' . $this->config['user'] . '/lights/' . $this->config['light'] . '/state';
				Unirest::put("{$url}", array(), $body);
			break;
		}
		
		$this->message = $message;
	}
	
	public function getOutput($interfaceId)
	{		
		return $this->message;
	}
}

interface HttpMethod
{
    
    const DELETE = "DELETE";
    const GET = "GET";
    const POST = "POST";
    const PUT = "PUT";
    const PATCH = "PATCH";
    
}

class Unirest
{
    
    private static $verifyPeer = true;
    private static $socketTimeout = null;
    private static $defaultHeaders = array();
    
    /**
     * Verify SSL peer
     * @param bool $enabled enable SSL verification, by default is true
     */
    public static function verifyPeer($enabled)
    {
        Unirest::$verifyPeer = $enabled;
    }
    
    /**
     * Set a timeout
     * @param integer $seconds timeout value in seconds
     */
    public static function timeout($seconds)
    {
        Unirest::$socketTimeout = $seconds;
    }
    
    /**
     * Set a new default header to send on every request
     * @param string $name header name
     * @param string $value header value
     */
    public static function defaultHeader($name, $value)
    {
        Unirest::$defaultHeaders[$name] = $value;
    }
    
    /**
     * Clear all the default headers
     */
    public static function clearDefaultHeaders()
    {
        Unirest::$defaultHeaders = array();
    }
    
    /**
     * Send a GET request to a URL
     * @param string $url URL to send the GET request to
     * @param array $headers additional headers to send
     * @param mixed $parameters parameters to send in the querystring
     * @param string $username Basic Authentication username
     * @param string $password Basic Authentication password
     * @return string|stdObj response string or stdObj if response is json-decodable
     */
    public static function get($url, $headers = array(), $parameters = NULL, $username = NULL, $password = NULL)
    {
        return Unirest::request(HttpMethod::GET, $url, $parameters, $headers, $username, $password);
    }
    
    /**
     * Send POST request to a URL
     * @param string $url URL to send the POST request to
     * @param array $headers additional headers to send
     * @param mixed $body POST body data
     * @param string $username Basic Authentication username
     * @param string $password Basic Authentication password
     * @return string|stdObj response string or stdObj if response is json-decodable
     */
    public static function post($url, $headers = array(), $body = NULL, $username = NULL, $password = NULL)
    {
        return Unirest::request(HttpMethod::POST, $url, $body, $headers, $username, $password);
    }
    
    /**
     * Send DELETE request to a URL
     * @param string $url URL to send the DELETE request to
     * @param array $headers additional headers to send
     * @param mixed $body DELETE body data
     * @param string $username Basic Authentication username
     * @param string $password Basic Authentication password
     * @return string|stdObj response string or stdObj if response is json-decodable
     */
    public static function delete($url, $headers = array(), $body = NULL, $username = NULL, $password = NULL)
    {
        return Unirest::request(HttpMethod::DELETE, $url, $body, $headers, $username, $password);
    }
    
    /**
     * Send PUT request to a URL
     * @param string $url URL to send the PUT request to
     * @param array $headers additional headers to send
     * @param mixed $body PUT body data
     * @param string $username Basic Authentication username
     * @param string $password Basic Authentication password
     * @return string|stdObj response string or stdObj if response is json-decodable
     */
    public static function put($url, $headers = array(), $body = NULL, $username = NULL, $password = NULL)
    {
        return Unirest::request(HttpMethod::PUT, $url, $body, $headers, $username, $password);
    }
    
    /**
     * Send PATCH request to a URL
     * @param string $url URL to send the PATCH request to
     * @param array $headers additional headers to send
     * @param mixed $body PATCH body data
     * @param string $username Basic Authentication username
     * @param string $password Basic Authentication password
     * @return string|stdObj response string or stdObj if response is json-decodable
     */
    public static function patch($url, $headers = array(), $body = NULL, $username = NULL, $password = NULL)
    {
        return Unirest::request(HttpMethod::PATCH, $url, $body, $headers, $username, $password);
    }
    
    /**
     * Prepares a file for upload. To be used inside the parameters declaration for a request.
     * @param string $path The file path
     */
    public static function file($path)
    {
        if (function_exists("curl_file_create")) {
            return curl_file_create($path);
        } else {
            return "@" . $path;
        }
    }
    
    /**
     * This function is useful for serializing multidimensional arrays, and avoid getting
     * the "Array to string conversion" notice
     */
    public static function http_build_query_for_curl($arrays, &$new = array(), $prefix = null)
    {
        if (is_object($arrays)) {
            $arrays = get_object_vars($arrays);
        }
        
        foreach ($arrays AS $key => $value) {
            $k = isset($prefix) ? $prefix . '[' . $key . ']' : $key;
            if (!$value instanceof \CURLFile AND (is_array($value) OR is_object($value))) {
                Unirest::http_build_query_for_curl($value, $new, $k);
            } else {
                $new[$k] = $value;
            }
        }
    }
    
    /**
     * Send a cURL request
     * @param string $httpMethod HTTP method to use (based off \Unirest\HttpMethod constants)
     * @param string $url URL to send the request to
     * @param mixed $body request body
     * @param array $headers additional headers to send
     * @param string $username  Basic Authentication username
     * @param string $password  Basic Authentication password
     * @throws Exception if a cURL error occurs
     * @return HttpResponse
     */
    private static function request($httpMethod, $url, $body = NULL, $headers = array(), $username = NULL, $password = NULL)
    {
        if ($headers == NULL)
            $headers = array();

        $lowercaseHeaders = array();
        $finalHeaders = array_merge($headers, Unirest::$defaultHeaders);
        foreach ($finalHeaders as $key => $val) {
            $lowercaseHeaders[] = Unirest::getHeader($key, $val);
        }
        
        $lowerCaseFinalHeaders = array_change_key_case($finalHeaders);
        if (!array_key_exists("user-agent", $lowerCaseFinalHeaders)) {
            $lowercaseHeaders[] = "user-agent: unirest-php/1.1";
        }
        if (!array_key_exists("expect", $lowerCaseFinalHeaders)) {
            $lowercaseHeaders[] = "expect:";
        }
        
        $ch = curl_init();
        if ($httpMethod != HttpMethod::GET) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod);
            if (is_array($body) || $body instanceof Traversable) {
                Unirest::http_build_query_for_curl($body, $postBody);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postBody);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        } else if (is_array($body)) {
            if (strpos($url, '?') !== false) {
                $url .= "&";
            } else {
                $url .= "?";
            }
            Unirest::http_build_query_for_curl($body, $postBody);
            $url .= urldecode(http_build_query($postBody));
        }
        
        curl_setopt($ch, CURLOPT_URL, Unirest::encodeUrl($url));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $lowercaseHeaders);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, Unirest::$verifyPeer);
        curl_setopt($ch, CURLOPT_ENCODING, ""); // If an empty string, "", is set, a header containing all supported encoding types is sent.
        if (Unirest::$socketTimeout != null) {
            curl_setopt($ch, CURLOPT_TIMEOUT, Unirest::$socketTimeout);
        }
        if (!empty($username)) {
            curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . ((empty($password)) ? "" : $password));
        }
        
        $response = curl_exec($ch);
        $error    = curl_error($ch);
        if ($error) {
            throw new Exception($error);
        }
        
        // Split the full response in its headers and body
        $curl_info   = curl_getinfo($ch);
        $header_size = $curl_info["header_size"];
        $header      = substr($response, 0, $header_size);
        $body        = substr($response, $header_size);
        $httpCode    = $curl_info["http_code"];
        
        return new HttpResponse($httpCode, $body, $header);
    }
    
    private static function getArrayFromQuerystring($querystring)
    {
        $pairs = explode("&", $querystring);
        $vars  = array();
        foreach ($pairs as $pair) {
            $nv          = explode("=", $pair, 2);
            $name        = $nv[0];
            $value       = $nv[1];
            $vars[$name] = $value;
        }
        return $vars;
    }
    
    /**
     * Ensure that a URL is encoded and safe to use with cURL
     * @param  string $url URL to encode
     * @return string
     */
    private static function encodeUrl($url)
    {
        $url_parsed = parse_url($url);
        
        $scheme = $url_parsed['scheme'] . '://';
        $host   = $url_parsed['host'];
        $port   = (isset($url_parsed['port']) ? $url_parsed['port'] : null);
        $path   = (isset($url_parsed['path']) ? $url_parsed['path'] : null);
        $query  = (isset($url_parsed['query']) ? $url_parsed['query'] : null);
        
        if ($query != null) {
            $query = '?' . http_build_query(Unirest::getArrayFromQuerystring($url_parsed['query']));
        }
        
        if ($port && $port[0] != ":")
            $port = ":" . $port;
        
        $result = $scheme . $host . $port . $path . $query;
        return $result;
    }
    
    private static function getHeader($key, $val)
    {
        $key = trim(strtolower($key));
        return $key . ": " . $val;
    }
    
}

if (!function_exists('http_chunked_decode')) {
    /**
     * Dechunk an http 'transfer-encoding: chunked' message 
     * @param string $chunk the encoded message 
     * @return string the decoded message
     */
    function http_chunked_decode($chunk)
    {
        $pos     = 0;
        $len     = strlen($chunk);
        $dechunk = null;
        
        while (($pos < $len) && ($chunkLenHex = substr($chunk, $pos, ($newlineAt = strpos($chunk, "\n", $pos + 1)) - $pos))) {
            
            if (!is_hex($chunkLenHex)) {
                trigger_error('Value is not properly chunk encoded', E_USER_WARNING);
                return $chunk;
            }
            
            $pos      = $newlineAt + 1;
            $chunkLen = hexdec(rtrim($chunkLenHex, "\r\n"));
            $dechunk .= substr($chunk, $pos, $chunkLen);
            $pos = strpos($chunk, "\n", $pos + $chunkLen) + 1;
        }
        
        return $dechunk;
    }
}

/**
 * determine if a string can represent a number in hexadecimal 
 * @link http://uk1.php.net/ctype_xdigit
 * @param string $hex 
 * @return boolean true if the string is a hex, otherwise false 
 */
function is_hex($hex)
{
    return ctype_xdigit($hex);
}

class HttpResponse
{
    
    private $code;
    private $raw_body;
    private $body;
    private $headers;
    
    /**
     * @param int $code response code of the cURL request
     * @param string $raw_body the raw body of the cURL response
     * @param string $headers raw header string from cURL response
     */
    public function __construct($code, $raw_body, $headers)
    {
        $this->code     = $code;
        $this->headers  = $this->get_headers_from_curl_response($headers);
        $this->raw_body = $raw_body;
        $this->body     = $raw_body;
        $json           = json_decode($raw_body);
        
        if (json_last_error() == JSON_ERROR_NONE) {
            $this->body = $json;
        }
    }
    
    /**
     * Return a property of the response if it exists.
     * Possibilities include: code, raw_body, headers, body (if the response is json-decodable)
     * @return mixed
     */
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }
    
    /**
     * Set the properties of this object
     * @param string $property the property name
     * @param mixed $value the property value
     */
    public function __set($property, $value)
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }
        return $this;
    }
    
    /**
     * Retrieve the cURL response headers from the
     * header string and convert it into an array
     * @param  string $headers header string from cURL response
     * @return array
     */
    private function get_headers_from_curl_response($headers)
    {
        $headers = explode("\r\n", $headers);
        array_shift($headers);
        
        foreach ($headers as $line) {
            if (strstr($line, ': ')) {
                list($key, $value) = explode(': ', $line);
                $result[$key] = $value;
            }
        }
        
        return $result;
    }
    
}

?>