<?php

namespace RESOService;

class RESOService
{
	private static $validOutputFormats = array("json", "xml");
    	private static $requestAcceptType = "";
	public static $validInputNamesUsername = array("username", "j_username", "user", "email");
	public static $validInputNamesPassword = array("password", "j_password", "pass");
	private static $isMbstringAvailable = null;
    	private static $isDomAvailable = null;
    	private static $isXmlAvailable = null;
   	
	private static $instance;

    private $curl = null;
    private static $isCurlAvailable = null;
    protected $defaultOptions;
    protected $userAgentInfo;
    private $cookieFile = ".resocookie";
    const DEFAULT_TIMEOUT = 80;
    const DEFAULT_CONNECT_TIMEOUT = 30;
    const SDK_VERSION = '1.0.0';
    private $timeout = self::DEFAULT_TIMEOUT;
    private $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT;


	 // @var string The RESO API client_id to be used for auth and query requests.
    public static $clientId;

    // @var string The RESO API client_secret to be used for auth and query requests.
    public static $clientSecret;

    // @var string The RESO API access token.
    public static $accessToken;

    // @var string The authentication / authorization URL for RESO API Auth service.
    public static $apiAuthUrl = '';

    // @var string The token request URL for RESO API Auth service.
    public static $apiTokenUrl = '';

    // @var string The base URL for RESO API Request service.
    public static $apiRequestUrl = '';

    // @var boolean Defaults to false.
    public static $verifySslCerts = false;

	/**
     * @return string The RESO API client_id used for auth and query requests.
     */
    public static function getClientId()
    {
        if(!self::$clientId) throw new Error\Reso("API client_id is not set.");
        return self::$clientId;
    }

    /**
     * Sets the RESO API client_id to be used for auth and query requests.
     *
     * @param string $clientId
     */
    public static function setClientId($clientId)
    {
        self::logMessage("Setting RESO API client id to '".$clientId."'.");
        self::$clientId = $clientId;
    }

    /**
     * @return string The RESO API client_secret used for auth and query requests.
     */
    public static function getClientSecret()
    {
        if(!self::$clientSecret) throw new Error\Reso("API client_secret is not set.");
        return self::$clientSecret;
    }

    /**
     * Sets the RESO API client_secret to be used for requests.
     *
     * @param string $clientSecret
     */
    public static function setClientSecret($clientSecret)
    {
        self::logMessage("Setting RESO API client secret.");
        self::$clientSecret = $clientSecret;
    }

    /**
     * @return string The RESO API access token.
     */
    public static function getAccessToken()
    {
        return self::$accessToken;
    }

    /**
     * Sets the RESO API access token.
     *
     * @param string $accessToken
     */
    public static function setAccessToken($accessToken)
    {
        self::logMessage("Setting RESO API access token.");
        self::$accessToken = $accessToken;
    }

    /**
     * @return string The RESO API auth endpoint URL.
     */
    public static function getAPIAuthUrl()
    {
        if(!self::$apiAuthUrl) throw new Error\Reso("API auth endpoint URL is not set.");
        return self::$apiAuthUrl;
    }

    /**
     * Sets the RESO API auth endpoint URL.
     *
     * @param string $apiAuthUrl
     */
    public static function setAPIAuthUrl($apiAuthUrl)
    {
        self::logMessage("Setting RESO API auth URL to '".$apiAuthUrl."'.");
        self::$apiAuthUrl = $apiAuthUrl;
    }

    /**
     * @return string The RESO API token endpoint URL.
     */
    public static function getAPITokenUrl()
    {
        if(!self::$apiTokenUrl) throw new Error\Reso("API token endpoint URL is not set.");
        return self::$apiTokenUrl;
    }

    /**
     * Sets the RESO API token endpoint URL.
     *
     * @param string $apiTokenUrl
     */
    public static function setAPITokenUrl($apiTokenUrl)
    {
        self::logMessage("Setting RESO API token URL to '".$apiTokenUrl."'.");
        self::$apiTokenUrl = $apiTokenUrl;
    }

    /**
     * @return string The RESO API request endpoint URL.
     */
    public static function getAPIRequestUrl()
    {
        if(!self::$apiRequestUrl) throw new Error\Reso("API request endpoint URL is not set.");
        return self::$apiRequestUrl;
    }

    /**
     * Sets the RESO API request endpoint URL.
     *
     * @param string $apiRequestUrl
     */
    public static function setAPIRequestUrl($apiRequestUrl)
    {
        self::logMessage("Setting RESO API request URL to '".$apiRequestUrl."'.");
        self::$apiRequestUrl = $apiRequestUrl;
    }


    /**
     * @return string The RESO API SDK version.
     */
    public static function getApiSdkVersion()
    {
        return self::$apiSdkVersion;
    }

    /**
     * @return boolean True / false to verify SSL certs in cURL requests.
     */
    public static function getVerifySslCerts()
    {
        return self::$verifySslCerts;
    }

    /**
     * Sets true / false to verify SSL certs in cURL requests.
     *
     * @param boolean $bool
     */
    public static function setVerifySslCerts($bool)
    {
        self::logMessage("Setting SSL certificate verification to '".(string)$bool."'.");
        self::$verifySslCerts = $bool;
    }
	
	/**
     * Sends GET request and returns output in specified format.
     *
     * @param string $request
     * @param string $output_format
     * @param string $decode_json
     * @param string $accept_format
     *
     * @return mixed API Request response in requested data format.
     */
    public static function request($request, $output_format = "xml", $decode_json = false)
    {
        \RESO\RESO::logMessage("Sending request '".$request."' to RESO API.");

        // Get variables
        $api_request_url = \RESO\RESO::getAPIRequestUrl();
        $token = \RESO\RESO::getAccessToken();

        if(!in_array($output_format, self::$validOutputFormats)) {
            $output_format = "json";
        }

        $curl = new \RESO\HttpClient\CurlClient();

        // Parse and validate request parameters
        $request = self::formatRequestParameters($request);

        // Build request URL
        $url = rtrim($api_request_url, "/") . "/" . $request;

        // Set the accept type
        if(self::$requestAcceptType) {
            $accept = "application/".self::$requestAcceptType;
        } else {
            $accept = "*/*";
        }

        // Set headers
        $headers = array(
            "Accept: ".$accept,
            "Authorization: Bearer ".$token
        );

        // Send request
        $response = $curl->request("get", $url, $headers, null, false);
        if(!$response || !is_array($response) || $response[1] != 200) {
            switch($response[1]) {
                case "406":
                    throw new Error\Api("API returned HTTP code 406 - Not Acceptable. Please, setup a valid Accept type using Request::setAcceptType(). Request URL: " . $api_request_url . "; Request string: " . $request . "; Response: " . $response[0]);
                default:
                    throw new Error\Api("Could not retrieve API response. Request URL: " . $api_request_url . "; Request string: " . $request . "; Response: " . $response[0]);
            }
        }

        // Decode the JSON response to PHP array, if $decode_json == true
        $is_json = Util\Util::isJson($response[0]);
        if($is_json && $output_format == "json" && $decode_json) {
            $return = json_decode($response[0], true);
            if(!is_array($response))
                throw new Error\Api("Could not decode API response. Request URL: ".$api_request_url."; Request string: ".$request."; Response: ".$response[0]);
        } elseif($is_json && $output_format == "xml") {
            $return = Util\Util::arrayToXml(json_decode($response[0], true));
        } else {
            $return = $response[0];
        }

        return $return;
    }

    /**
     * Sends POST request with specified parameters.
     *
     * @param string $request
     * @param array $params
     * @param string $accept_format
     *
     * @return mixed API Request response.
     */
    public static function requestPost($request, $params = array())
    {
        \RESO\RESO::logMessage("Sending POST request '".$request."' to RESO API.");

        // Get variables
        $api_request_url = \RESO\RESO::getAPIRequestUrl();
        $token = \RESO\RESO::getAccessToken();

        $curl = new \RESO\HttpClient\CurlClient();

        // Build request URL
        $url = rtrim($api_request_url, "/") . "/" . $request;

        // Set the accept type
        if(self::$requestAcceptType) {
            $accept = "application/".self::$requestAcceptType;
        } else {
            $accept = "*/*";
        }

        $headers = array(
            "Accept: ".$accept,
            "Authorization: Bearer ".$token
        );

        // Send request
        $response = $curl->request("post", $url, $headers, $params, false);
        if(!$response || !is_array($response) || $response[1] != 200) {
            switch($response[1]) {
                case "406":
                    throw new Error\Api("API returned HTTP code 406 - Not Acceptable. Please, setup a valid Accept type using Request::setAcceptType(). Request URL: " . $api_request_url . "; Request string: " . $request . "; Response: " . $response[0]);
                default:
                    throw new Error\Api("Could not retrieve API response. Request URL: " . $api_request_url . "; Request string: " . $request . "; Response: " . $response[0]);
            }
        }

        // Decode the JSON response
        $is_json = Util\Util::isJson($response[0]);
        if($is_json) {
            $return = json_decode($response[0], true);
        } else {
            $return = $response[0];
        }

        return $return;
    }

    /**
     * Requests RESO API output and saves the output to file.
     *
     * @param string $file_name
     * @param string $request
     * @param string $output_format
     * @param bool $overwrite
     *
     * @return True / false output saved to file.
     */
    public static function requestToFile($file_name, $request, $output_format = "xml", $overwrite = false, $accept_format = "json") {
        \RESO\RESO::logMessage("Sending request '".$request."' to RESO API and storing output to file '".$file_name."'.");

        if(!$overwrite && is_file($file_name)) {
            throw new Error\Reso("File '".$file_name."' already exists. Use variable 'overwrite' to overwrite the output file.");
        }

        if(!is_dir(dirname($file_name))) {
            throw new Error\Reso("Directory '".dir($file_name)."' does not exist.");
        }

        $output_data = self::request($request, $output_format, false, $accept_format);
        if(!$output_data) {
            \RESO\RESO::logMessage("Request output save to file failed - empty or erroneous data.");
            return false;
        }

        file_put_contents($file_name, $output_data);
        if(!is_file($file_name)) {
            \RESO\RESO::logMessage("Request output save to file failed - could not create output file.");
            return false;
        }

        \RESO\RESO::logMessage("Request output save to file succeeded.");
        return true;
    }

    /**
     * Requests RESO API metadata output.
     *
     * @return Metadata request output.
     */
    public static function requestMetadata() {
        \RESO\RESO::logMessage("Requesting resource metadata.");
        return self::request("\$metadata");
    }

    /**
     * Sets accept Accept content type in all requests.
     *
     * @param string
     */
    public static function setAcceptType($type = "") {
        if(in_array($type, self::$validOutputFormats)) {
            self::$requestAcceptType = $type;
        }
    }

    /**
     * Formats request parameters to compatible string
     *
     * @param string
     */
    public static function formatRequestParameters($parameters_string) {
        parse_str($parameters_string, $parsed);
        if(!is_array($parsed) || empty($parsed)) {
            throw new Error\Reso("Could not parse the request parameters.");
        }

        $params = array();
        foreach($parsed as $key => $param) {
            if($param) {
                $params[] = $key . "=" . rawurlencode($param);
            } else {
                $params[] = $key;
            }
        }

        return implode("&", $params);
    }

	/**
     * Autheticates user to the RESO API endpoint and returns authorization code.
     *
     * @param string $username
     * @param string $password
     * @param string $redirect_uri
     * @param string $scope
     *
     * @return string Athorization code.
     */
    public static function authorize($username, $password, $redirect_uri, $scope = "ODataApi")
    {
        \RESO\RESO::logMessage("Initiating RESO API authorization.");

        // Get variables
        $api_auth_url = \RESO\RESO::getAPIAuthUrl();
        $client_id = \RESO\RESO::getClientId();

        $curl = new \RESO\HttpClient\CurlClient();

        // Authentication request parameters
        $params = array(
            "client_id" => $client_id,
            "scope" => $scope,
            "redirect_uri" => $redirect_uri,
            "response_type" => "code"
        );

        // Request authentication
        $response = $curl->request("get", $api_auth_url, null, $params, false)[0];
        $params = @Util\Util::extractFormParameters($response);

        // Do login form POST
        // Build login URL
        $parsed_url = parse_url($api_auth_url);
        if(stripos($params["url"], "{{model.loginUrl}}") !== FALSE) {
            $modelJson = @Util\Util::extractModelJson($response);
            if(!$modelJson || !is_array($modelJson) || !isset($modelJson))
                throw new Error\Api("Could not authenticate to the RESO API auth.");
            $url = $parsed_url["scheme"]."://" .$parsed_url["host"] . $modelJson["loginUrl"];
            foreach($modelJson as $key => $value) {
                if($key == "loginUrl") {
                    continue;
                } else if($key == "antiForgery") {
                    $params["inputs"][$value["name"]] = $value["value"];
                } else {
                    $params["inputs"][$key] = $value;
                }
            }
        } else {
            if (strpos($params["url"], "://") !== FALSE) {
                $url = $params["url"];
            } else {
                $url = $parsed_url["scheme"] . "://" . $parsed_url["host"] . $params["url"];
            }
        }

        // Check if we have valid login url
        if(!parse_url($url))
            throw new Error\Api("Could not obtain RESO API login URL from the response.");
        $params["url"] = $url;

        // Fill in Login parameters
        foreach($params["inputs"] as $key => $value) {
            if($value) continue;
            if(in_array($key, self::$validInputNamesUsername)) {
                $params["inputs"][$key] = $username;
            } else if(in_array($key, self::$validInputNamesPassword) ) {
                $params["inputs"][$key] = $password;
            }
        }
        $headers = array("Content-Type: application/x-www-form-urlencoded");

        // Request login
        $response_curl_info = $curl->request("post", $url, $headers, $params["inputs"], false)[3];

        // Extract code
        $auth_code = @Util\Util::extractCode($response_curl_info["url"]);
        if(!$auth_code)
            throw new Error\Api("Failed to obtain auth code.");

        // Close cURL instance
        $curl->close();

        return $auth_code;
    }

    /**
     * Retrieves the access token of an authorized user session.
     *
     * @param string $redirect_uri
     * @param string $auth_code
     * @param string $scope
     *
     * @return string Access token.
     */
    public static function requestAccessToken($auth_code, $redirect_uri, $scope = "ODataApi")
    {
        \RESO\RESO::logMessage("Sending authorization request to retrieve access token.");

        // Get variables
        $api_token_url = \RESO\RESO::getAPITokenUrl();
        $client_id = \RESO\RESO::getClientId();
        $client_secret = \RESO\RESO::getClientSecret();

        $curl = new \RESO\HttpClient\CurlClient();

        $headers = array(
            'Authorization: Basic '.base64_encode($client_id.":".$client_secret)
        );
        $params = array(
            "grant_type" => "authorization_code",
            "client_id" => $client_id,
            "redirect_uri" => $redirect_uri,
            "code" => $auth_code
        );

        $response = json_decode($curl->request("post", $api_token_url, $headers, $params, false)[0], true);
        if(!$response || !is_array($response) || !isset($response["access_token"]))
            throw new Error\Api("Failed to obtain access token.");

        return $response["access_token"];
    }

    /**
     * Retrieves new access token (refresh).
     *
     * @return string Refreshed access token.
     */
    public static function requestRefreshToken()
    {
        \RESO\RESO::logMessage("Requesting refresh token.");

        // Get variables
        $access_token = \RESO\RESO::getAccessToken();
        $api_token_url = \RESO\RESO::getAPITokenUrl();
        $client_id = \RESO\RESO::getClientId();
        $client_secret = \RESO\RESO::getClientSecret();

        $curl = new \RESO\HttpClient\CurlClient();

        $headers = array(
            'Authorization: Basic '.base64_encode($client_id.":".$client_secret)
        );
        $params = array(
            "grant_type" => "authorization_code",
            "refresh_token" => $access_token
        );

        $response = json_decode($curl->request("post", $api_token_url, $headers, $params, false)[0], true);
        if(!$response || !is_array($response) || !isset($response["refresh_token"]))
            throw new Error\Api("Failed to refresh token.");

        return $response["refresh_token"];
    }

    /**
     * Retrieves RESO API auth login page URL.
     *
     * @param string $redirect_uri
     * @param string $scope
     *
     * @return string RESO API auth login page URL.
     */
    public static function getLoginUrl($redirect_uri, $scope = "ODataApi")
    {
        // Get variables
        $api_auth_url = \RESO\RESO::getAPIAuthUrl();
        $client_id = \RESO\RESO::getClientId();

        // Authentication request parameters
        $params = array(
            "client_id" => $client_id,
            "scope" => $scope,
            "redirect_uri" => $redirect_uri,
            "response_type" => "code"
        );

        return $api_auth_url . '?' . http_build_query($params);
    }

	/**
     * @param array $array
     * @param SimpleXMLElement &$xml
     *
     * @return bool True if the string is JSON, otherwise - False.
     */
    public static function _arrayToXml($array, &$xml) {
        foreach ($array as $key => $value) {
            if(is_array($value)){
                if(is_int($key)){
                    $key = "e";
                }
                $label = $xml->addChild($key);
                self::_arrayToXml($value, $label);
            }
            else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }

/**
     * @param array $array
     *
     * @return string Returns XML formatted string.
     */
    public static function arrayToXml($array) {
        if (self::$isXmlAvailable === null) {
            self::$isXmlAvailable = function_exists('simplexml_load_file');

            if (!self::$isXmlAvailable) {
                throw new Error\Reso("It looks like the XML extension is not enabled. " .
                    "XML extension is required to use the RESO API PHP SDK, if the request response format is set to XML.");
            }
        }

        $xml = new \SimpleXMLElement('<root/>');
        self::_arrayToXml($array, $xml);
        return $xml->asXML();
    }

 /**
     * @param string $string
     *
     * @return bool True if the string is JSON, otherwise - False.
     */
    public static function isJson($string) {
        if(is_numeric($string)) return false;
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }


 /**
     * @param array $arr A map of param keys to values.
     * @param string|null $prefix
     *
     * @return string A querystring, essentially.
     */
    public static function urlEncode($arr, $prefix = null)
    {
        if (!is_array($arr)) {
            return $arr;
        }

        $r = array();
        foreach ($arr as $k => $v) {
            if (is_null($v)) {
                continue;
            }

            if ($prefix) {
                if ($k !== null && (!is_int($k) || is_array($v))) {
                    $k = $prefix."[".$k."]";
                } else {
                    $k = $prefix."[]";
                }
            }

            if (is_array($v)) {
                $enc = self::urlEncode($v, $k);
                if ($enc) {
                    $r[] = $enc;
                }
            } else {
                $r[] = urlencode($k)."=".urlencode($v);
            }
        }

        return implode("&", $r);
    }

	 /**
     * @param string $response_body HTML response with login form.
     *
     * @return array Form parameters and input field names and values.
     */
    public static function extractFormParameters($response_body) {
        $dom = new \DOMDocument();
        $returnArray = array();
        if(@$dom->loadHTML($response_body)) {
            $form = $dom->getelementsbytagname('form')[0];
            $returnArray["url"] = $form->getAttribute('action');
            $returnArray["method"] = $form->getAttribute('method');
            $returnArray["inputs"] = array();
            $inputs = $dom->getelementsbytagname('input');
            foreach ($inputs as $input) {
                $returnArray["inputs"][$input->getAttribute('name')] = $input->getAttribute('value');
            }
        }
        return $returnArray;
    }


/**
     * @param string $response_body HTML response with modelJson tag.
     *
     * @return array Extracted modelJson values in PHP array format.
     */
    public static function extractModelJson($response_body) {
        if (self::$isDomAvailable === null) {
            self::$isDomAvailable = extension_loaded("dom");

            if (!self::$isDomAvailable) {
                throw new Error\Reso("It looks like the DOM extension is not enabled. " .
                    "DOM extension is required to use the RESO API PHP SDK.");
            }
        }

        $doc = new \DOMDocument();
        @$doc->loadHTML($response_body);
        $extract = json_decode(htmlspecialchars_decode($doc->getElementById("modelJson")->textContent), true);
        return $extract;
    }


/**
     * @param string $url_string URL string with the code parameter.
     *
     * @return string Authentification code string.
     */
    public static function extractCode($url_string) {
        return explode("=", parse_url($url_string)["query"])[1];
    }


	/**
     * @param string|mixed $value A string to UTF8-encode.
     *
     * @return string|mixed The UTF8-encoded string, or the object passed in if
     *    it wasn't a string.
     */
    public static function utf8($value)
    {
        if (self::$isMbstringAvailable === null) {
            self::$isMbstringAvailable = function_exists('mb_detect_encoding');

            if (!self::$isMbstringAvailable) {
                trigger_error("It looks like the mbstring extension is not enabled. " .
                    "UTF-8 strings will not properly be encoded.", E_USER_WARNING);
            }
        }

        if (is_string($value) && self::$isMbstringAvailable && mb_detect_encoding($value, "UTF-8", true) != "UTF-8") {
            return utf8_encode($value);
        } else {
            return $value;
        }
    }

	public static function getTimeString() {
        return "[".date("c")."]";
    }

	public static function logMessage($message) {
        if(!\RESO\RESO::getLogEnabled()) {
            return false;
        }

        if(\RESO\RESO::getLogConsole()) {
            self::logConsole($message);
        }

        if(\RESO\RESO::getLogFile() && \RESO\RESO::getLogFileName()) {
            self::logFile(\RESO\RESO::getLogFileName(), $message);
        }
    }

    public static function logConsole($message) {
        $message = self::getTimeString()." ".$message;
        echo($message."\n");
        return true;
    }

    public static function logFile($file_name, $message) {
        $message = self::getTimeString()." ".$message;
        if(is_dir(dirname($file_name))) {
            file_put_contents($file_name, $message . "\n", FILE_APPEND);
            return true;
        } else {
            return false;
        }
    }

	 public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }	

	**
     * CurlClient constructor.
     *
     * Pass in a callable to $defaultOptions that returns an array of CURLOPT_* values to start
     * off a request with, or an flat array with the same format used by curl_setopt_array() to
     * provide a static set of options. Note that many options are overridden later in the request
     * call, including timeouts, which can be set via setTimeout() and setConnectTimeout().
     *
     * Note that request() will silently ignore a non-callable, non-array $defaultOptions, and will
     * throw an exception if $defaultOptions returns a non-array value.
     *
     * @param array|callable|null $defaultOptions
     */
    public function __construct($defaultOptions = null)
    {
        $this->defaultOptions = $defaultOptions;
        $this->initUserAgentInfo();

        self::$isCurlAvailable = extension_loaded("curl");

        if (!self::$isCurlAvailable) {
            throw new Error\Reso("It looks like the cURL extension is not enabled. " .
                "cURL extension is required to use the RESO API PHP SDK.");
        }

        if(file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
    }

    public function __destruct() {
        if($this->curl) {
            $this->close();
        }

        if(file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
    }

    public function initUserAgentInfo()
    {
        $curlVersion = curl_version();
        $this->userAgentInfo = array(
            'httplib' =>  'curl ' . $curlVersion['version'],
            'ssllib'  => $curlVersion['ssl_version'],
            'sdkInfo' => "RESO-RETS-SDK/" . self::SDK_VERSION
        );
    }

    public function getDefaultOptions()
    {
        return $this->defaultOptions;
    }

    public function getUserAgentInfo()
    {
        return $this->userAgentInfo;
    }

    public function setTimeout($seconds)
    {
        $this->timeout = (int) max($seconds, 0);
        return $this;
    }

    public function setConnectTimeout($seconds)
    {
        $this->connectTimeout = (int) max($seconds, 0);
        return $this;
    }

    public function getTimeout()
    {
        return $this->timeout;
    }

    public function getConnectTimeout()
    {
        return $this->connectTimeout;
    }

    public function request($method, $absUrl, $headers, $params, $hasFile)
    {
        if($headers == null || !is_array($headers)) {
            $headers = array();
        }

        if(!$this->curl) {
            $this->curl = curl_init();
        }
        $method = strtolower($method);

        $opts = array();
        if (is_callable($this->defaultOptions)) { // call defaultOptions callback, set options to return value
            $opts = call_user_func_array($this->defaultOptions, func_get_args());
            if (!is_array($opts)) {
                throw new Error\Api("Non-array value returned by defaultOptions CurlClient callback");
            }
        } elseif (is_array($this->defaultOptions)) { // set default curlopts from array
            $opts = $this->defaultOptions;
        }

        if ($method == 'get') {
            if ($hasFile) {
                throw new Error\Api(
                    "Issuing a GET request with a file parameter"
                );
            }
            $opts[CURLOPT_HTTPGET] = 1;
            if (is_array($params) && count($params) > 0) {
                $encoded = Util\Util::urlEncode($params);
                $absUrl = "$absUrl?$encoded";
            }
        } elseif ($method == 'post') {
            $opts[CURLOPT_POST] = count($params);
            $opts[CURLOPT_POSTFIELDS] = $hasFile ? $params : Util\Util::urlEncode($params);
        } else {
            throw new Error\Api("Unrecognized method $method");
        }

        // Create a callback to capture HTTP headers for the response
        $rheaders = array();
        $headerCallback = function ($curl, $header_line) use (&$rheaders) {
            // Ignore the HTTP request line (HTTP/1.1 200 OK)
            if (strpos($header_line, ":") === false) {
                return strlen($header_line);
            }
            list($key, $value) = explode(":", trim($header_line), 2);
            $rheaders[trim($key)] = trim($value);
            return strlen($header_line);
        };

        $absUrl = Util\Util::utf8($absUrl);
        $opts[CURLOPT_URL] = $absUrl;
        $opts[CURLOPT_RETURNTRANSFER] = true;
        $opts[CURLOPT_FOLLOWLOCATION] = true;
        $opts[CURLOPT_AUTOREFERER] = true;
        $opts[CURLOPT_COOKIESESSION] = true;
        $opts[CURLOPT_COOKIEJAR] = $this->cookieFile;
        $opts[CURLOPT_COOKIEFILE] = $this->cookieFile;
        $opts[CURLOPT_CONNECTTIMEOUT] = $this->connectTimeout;
        $opts[CURLOPT_TIMEOUT] = $this->timeout;
        $opts[CURLOPT_HEADERFUNCTION] = $headerCallback;
        if($headers) {
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }
        if (!RESO::$verifySslCerts) {
            $opts[CURLOPT_SSL_VERIFYHOST] = false;
            $opts[CURLOPT_SSL_VERIFYPEER] = false;
        }

        curl_setopt_array($this->curl, $opts);
        $rbody = curl_exec($this->curl);

        if ($rbody === false) {
            $errno = curl_errno($this->curl);
            $message = curl_error($this->curl);
            $this->handleCurlError($absUrl, $errno, $message);
        }

        $curlInfo = curl_getinfo($this->curl);
        return array($rbody, $curlInfo["http_code"], $rheaders, $curlInfo);
    }

    public function close() {
        if($this->curl) {
            curl_close($this->curl);
            $this->curl = null;
        }
    }

    /**
     * @param number $errno
     * @param string $message
     * @throws Error\ApiConnection
     */
    private function handleCurlError($url, $errno, $message)
    {
        switch ($errno) {
            case CURLE_COULDNT_CONNECT:
            case CURLE_COULDNT_RESOLVE_HOST:
            case CURLE_OPERATION_TIMEOUTED:
                $msg = "Could not connect to RESO API ($url).";
                break;
            case CURLE_SSL_CACERT:
            case CURLE_SSL_PEER_CERTIFICATE:
                $msg = "Could not verify RESO API endpoint's SSL certificate.  Please make sure "
                    . "that your network is not intercepting certificates.  "
                    . "(Try going to $url in your browser.)  "
                    . "If this problem persists,";
                break;
            default:
                $msg = "Unexpected error communicating with RESO. "
                    . "If this problem persists,";
        }
        $msg .= " let us know at info@reso.org.";

        $msg .= "\n\n(Network error [errno $errno]: $message)";
        throw new Error\ApiConnection($msg);
    }
	 public function __toString()
    {
        $id = $this->requestId ? " from API request '{$this->requestId}'": "";
        $message = explode("\n", parent::__toString());
        $message[0] .= $id;
        return implode("\n", $message);
    }


}


