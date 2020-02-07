<?php
/**
 * Copyright (C) 2020 by NetResults S.r.l. ( http://www.netresults.it )
 * Author(s):
 *     Roberto Santini         <r.santini@netresults.it>.
 */

require_once 'globals.php';
require_once 'Utils.php';

// REST API URL
define('GET_CTI_USERS_URL', 'http://%s/rest/ctiUser/');

// Create the full API path
$ctiUserUrl = sprintf(GET_CTI_USERS_URL, PBX_IP_ADDRESS);

// Instantiate the Utils object
$utils = new Utils();
$response = $utils->executeRequest($ctiUserUrl);

if (false !== $response) {
    echo $response."\n";
}

