<?php
/**
 * Copyright (C) 2020 by NetResults S.r.l. ( http://www.netresults.it )
 * Author(s):
 *     Roberto Santini         <r.santini@netresults.it>.
 */

use NetResults\KalliopePBX\RestApiUtils;

require_once '../vendor/autoload.php';
require_once 'globals.php';

define('GET_CTI_USERS_URL', 'http://%s/rest/ctiUser/');

// Create the RestApiUtils object and generate the authentication header.
$restApiUtils = new RestApiUtils();
$tenantSalt = $restApiUtils->getTenantSalt(TENANT_DOMAIN, PBX_IP_ADDRESS);
$authHeader = $restApiUtils->generateAuthHeader(USERNAME, TENANT_DOMAIN, PASSWORD, $tenantSalt, false);

// Create the full API path
$ctiUserUrl = sprintf(GET_CTI_USERS_URL, PBX_IP_ADDRESS);

// Init the cURL object to do the REST API request
$ch = curl_init($ctiUserUrl);

// Add the authentication header and the 'Accept' header to get a JSON response
curl_setopt(
    $ch,
    CURLOPT_HTTPHEADER,
    [
        'Accept: application/json',
        RestApiUtils::X_AUTHENTICATE_HEADER_NAME.': '.$authHeader,
    ]
);

/*
 * From PHP curl_setopt documentation:
 * TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it directly.
 */
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute the request
$curlResponse = curl_exec($ch);

if (false !== $curlResponse) {
    /*
     * All went fine!
     * The following code prints the response to STDOUT, you can handle the response as you wish.
     */
    echo "CTI users:\n";
    echo $curlResponse."\n";
} else {
    // Something went wrong, prints what's append.
    echo 'cURL error: '.curl_error($ch)."\n";
}

// The cURL resources must be always closed.
curl_close($ch);
