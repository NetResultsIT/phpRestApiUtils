<?php
/**
 * Copyright (C) 2020 by NetResults S.r.l. ( http://www.netresults.it )
 * Author(s):
 *     Roberto Santini         <r.santini@netresults.it>.
 */

require_once __DIR__.'/../globals.php';
require_once __DIR__.'/../Utils.php';

// REST API URL
define('GET_BACKUP_LIST_URL', 'http://%s/rest/backup');

// Instantiate the Utils object
$utils = new Utils();

// Get backup list
$response = $utils->executeRequest(sprintf(GET_BACKUP_LIST_URL, PBX_IP_ADDRESS));
if (false === $response) {
    echo "Something went wrong! :(\nBackup list not retrieved.\n";
    exit(1);
}

// Deserialize the string response to an array
$backupList = json_decode($response, true);
if (null === $backupList) {
    echo 'Unable to decode the response!';
    exit(1);
}

// Iterate over all backups, if any
if (0 === count($backupList)) {
    echo "No backups found on KalliopePBX\n";
} else {
    echo "KalliopePBX has the following backups:\n";
    foreach ($backupList as $backupName) {
        echo sprintf(" * %s\n", $backupName);
    }
}
