<?php
/**
 * Copyright (C) 2020 by NetResults S.r.l. ( http://www.netresults.it )
 * Author(s):
 *     Roberto Santini         <r.santini@netresults.it>
 */

require_once __DIR__.'/../globals.php';
require_once __DIR__.'/../Utils.php';

// REST API URL
define('DELETE_BACKUP_URL', 'http://%s/rest/backup/%s');

// For this example we need the CLI to ask the backup name to download
if ('cli' !== PHP_SAPI) {
    echo sprintf("You are in '%s' environment, 'cli' environment is required!\n", PHP_SAPI);
    exit(1);
}

// Ask to the user the name of the backup to delete
echo 'Insert the name of the backup you want to delete: ';
$backupName = trim(fgets(STDIN));
if ('' === $backupName) {
    echo "I'm sorry, I can't delete anything if you don't say to me what you want!\n";
    exit(1);
}
if (false !== strpos($backupName, ' ')) {
    echo "The backup name can't contains spaces.\n";
    exit(1);
}

echo sprintf("I'm trying to delete the backup with name '%s'\n", $backupName);

// Instantiate the Utils object
$utils = new Utils();

// Delete backup
$response = $utils->executeRequest(
    sprintf(DELETE_BACKUP_URL, PBX_IP_ADDRESS, $backupName),
    null,
    [],
    Utils::REQUEST_TYPE_DELETE
);
if (false === $response) {
    echo "Something went wrong! :(\nBackup not deleted.\n";
    exit(1);
}

// Check the status code
$requestData = $utils->getLastRequestInfo();
if (404 === $requestData['http_code']) {
    // Specific not found error
    echo sprintf("I'm sorry, there isn't any backup named '%s'.\n", $backupName);
    exit(1);
}
if (200 !== $requestData['http_code']) {
    // Other errors
    echo sprintf("Oops, we have received a %d error with the following response\n\n", $requestData['http_code']);
    echo $response."\n";
    exit(1);
}

echo sprintf("Backup file '%s' deleted correctly.\n", $backupName);

echo "Done\n";
