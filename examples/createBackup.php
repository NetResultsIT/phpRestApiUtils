<?php
/**
 * Copyright (C) 2020 by NetResults S.r.l. ( http://www.netresults.it )
 * Author(s):
 *     Roberto Santini         <r.santini@netresults.it>.
 */

require_once 'globals.php';
require_once 'Utils.php';

// REST API URL
define('BACKUP_CREATE_URL', 'http://%s/rest/backup/create/%s');

$saveBackupToDisk = false;
if ('cli' === PHP_SAPI) {
    echo 'Do you want to save the backup file to the current folder? [y/N]: ';
    $input = trim(fgets(STDIN));
    $saveBackupToDisk = 0 === strcasecmp($input, 'y');
}

// Instantiate the Utils object
$utils = new Utils();

// Gets the firmware version, this info is required to create the backup
$fwVersion = $utils->getFirmwareVersion();

if (false === $fwVersion) {
    echo "Unable to retrieve the current firmware version!\n";
    exit(1);
}

// The backup create API requires a body with the name of the backup and an optional comment
try {
    $backupName = 'backup_'.str_pad(random_int(10, 1000), 4, '0', STR_PAD_LEFT);
} catch (Exception $e) {
    // Exception for random_int function if is not possible to gather sufficient entropy
    $backupName = 'backup_0001';
}

echo sprintf("Tying to create a new backup with name '%s' for firmware version %s\n", $backupName, $fwVersion);

$requestBody = [
    'filename' => $backupName,
    'comment' => '',
];

// Execute the request
$response = $utils->executeRequest(
    sprintf(BACKUP_CREATE_URL, PBX_IP_ADDRESS, $fwVersion),
    json_encode($requestBody),
    ['Content-Type: application/json']
);

if (false === $response) {
    echo "Something went wrong! :(\nBackup not created.\n";
    exit(1);
}

$requestData = $utils->getLastRequestInfo();
if (200 !== $requestData['http_code']) {
    echo sprintf("Response code: %d\n\n%s\n", $requestData['http_code'], $response);
    exit(1);
}

if ($saveBackupToDisk) {
    $cwd = getcwd();
    if (false === $cwd) {
        echo "Unable to get the current folder, you can find the backup in the KalliopePBX backup panel.\n";
    }
    echo sprintf("Saving backup file '%s'\n", $backupName);
    file_put_contents($cwd.'/'.$backupName, $response);
}

echo "Done\n";
