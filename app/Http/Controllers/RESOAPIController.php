<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Service\RESOService;

class RESOAPIController extends Controller
{
	private $service;

	print_r "WELCOME ---";	
	function __construct(RESOAPIService $service)
        {
                $this->service = $service;
        }

	function getjsondata()
{
	// Set the variables
$id = $service::setClientId($client_id);
$secret = $service::setClientSecret($client_secret);
$url = $service::setAPIAuthUrl($api_auth_url);
$tokenurl = $service::setAPITokenUrl($api_token_url);
$requesturl = $service::setAPIRequestUrl($api_request_url);

print $id . "--" . $tokenurl . "--" . $url . "\n";
// Authorize user
$auth_code = $servic::authorize($auth_username, $auth_password, $redirect_uri, $scope);

// Get access token
$service::setAccessToken(RESO\OpenIDConnect::requestAccessToken($auth_code, $redirect_uri, $scope));

// Set the Accept header (if needed)
$service::setAcceptType("json");

// Retrieve metadata from RESO API
/*
$data = $service::requestMetadata();
// Print Metadata
echo "\nMetadata:\n\n";
print_r($data);
echo "\n\n";
*/

// Retrieve top 10 properties from the RESO API endpoint
$data = $service::request("Property?\$top=10", "json", true);

// Display records
echo "Records retrieved from RESO API: ".count($data["value"])."\n\nRecords:\n";
print_r($data);

// Save output to file
$service::requestToFile("test.json", "Property?\$top=10", "json", true);
}
}
