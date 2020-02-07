<?php
/**
 * Copyright (C) 2020 by NetResults S.r.l. ( http://www.netresults.it )
 * Author(s):
 *     Roberto Santini         <r.santini@netresults.it>.
 */

require_once __DIR__.'/../globals.php';
require_once __DIR__.'/../Utils.php';

// REST API URL
define('DOWNLOAD_BACKUP_URL', 'http://%s/rest/backup/%s');

// For this example we need the CLI to ask the backup name to download
if ('cli' !== PHP_SAPI) {
    echo sprintf("You are in '%s' environment, 'cli' environment is required!\n", PHP_SAPI);
    exit(1);
}

// Ask to the user the name of the backup to download
echo 'Insert the name of the backup you want to download: ';
$backupName = trim(fgets(STDIN));
if ('' === $backupName) {
    echo "I'm sorry, I can't download anything if you don't say to me what you want!\n";
    exit(1);
}
if (false !== strpos($backupName, ' ')) {
    echo "The backup name can't contains spaces.\n";
    exit(1);
}

echo sprintf("I'm trying to download the backup with name '%s'\n", $backupName);

// Instantiate the Utils object
$utils = new Utils();

// Download backup
$response = $utils->executeRequest(sprintf(DOWNLOAD_BACKUP_URL, PBX_IP_ADDRESS, $backupName));
if (false === $response) {
    echo "Something went wrong! :(\nBackup list not retrieved.\n";
    exit(1);
}

// Check the status code
$responseInfo = $utils->getLastRequestInfo();
if (404 === $responseInfo['http_code']) {
    // Specific not found error
    echo sprintf("I'm sorry, there isn't any backup named '%s'.\n", $backupName);
    exit(1);
}
if (200 !== $responseInfo['http_code']) {
    // Other errors
    echo sprintf("Oops, we have received a %d error with the following response\n\n", $responseInfo['http_code']);
    echo $response."\n";
    exit(1);
}

$cwd = getcwd();
if (false === $cwd) {
    echo "Unable to get the current folder. The backup has been downloaded but I can't save the file.\n";
    exit(1);
}

echo sprintf("Saving backup file '%s'\n", $cwd.DIRECTORY_SEPARATOR.$backupName);
file_put_contents($cwd.DIRECTORY_SEPARATOR.$backupName, $response);

echo "Done\n";
