<?php
/**
 * Copyright (C) 2020 by NetResults S.r.l. ( http://www.netresults.it )
 * Author(s):
 *     Roberto Santini         <r.santini@netresults.it>.
 */

require_once __DIR__.'/../../Utils.php';

/*
 * This example cover the following scenario:
 *
 * - create and download the backup from PBX A
 * - restore the downloaded backup to PBX 2
*/

define('PBX_A_IP_ADDRESS', 'yourA.kalliopepbx.address');
define('PBX_B_IP_ADDRESS', 'yourB.kalliopepbx.address');
define('TENANT_DOMAIN', 'default');
define('USERNAME', 'admin');
define('PASSWORD', 'admin');

define('BACKUP_CREATE_URL', 'http://%s/rest/backup/create/%s');
define('UPLOAD_AND_RESTORE_BACKUP_URL', 'http://%s/rest/backup/restore');
define('DELETE_BACKUP_URL', 'http://%s/rest/backup/%s');

// Instantiate the Utils object
$utils = new Utils(PBX_A_IP_ADDRESS, USERNAME, PASSWORD, TENANT_DOMAIN);

/*
 * create and download the backup from PBX A
 */

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

echo sprintf(
    "Tying to create a new backup with name '%s' for firmware version %s on PBX %s\n",
    $backupName,
    $fwVersion,
    PBX_A_IP_ADDRESS
);

$requestBody = [
    'filename' => $backupName,
    'comment' => '',
];

// Execute the request
$response = $utils->executeRequest(
    sprintf(BACKUP_CREATE_URL, PBX_A_IP_ADDRESS, $fwVersion),
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

// Save the backup
$tmpFile = tempnam(sys_get_temp_dir(), 'backup_');
if (false === $tmpFile) {
    echo "Unable to create a temp file to save the backup.\n";
    exit(1);
}
if (false === file_put_contents($tmpFile, $response)) {
    echo "Unable to save the backup.\n";
    exit(1);
}

echo sprintf("Backup saved in a temp file '%s'\n", $tmpFile);

/*
 * restore the downloaded backup to PBX 2
 */

$utils->setPbxIpAddress(PBX_B_IP_ADDRESS);

echo sprintf("Trying to upload and restore the backup %s to PBX %s\n", $backupName, PBX_B_IP_ADDRESS);

// Upload the backup
$postName = $backupName.'.bak';
$response = $utils->executeRequest(
    sprintf(UPLOAD_AND_RESTORE_BACKUP_URL, PBX_B_IP_ADDRESS),
    ['backupFile' => curl_file_create($tmpFile, '', $postName)]
);

// Delete the backup from disk
@unlink($tmpFile);
echo sprintf("Backup file '%s' deleted\n", $tmpFile);

if (false === $response) {
    echo "Something went wrong! :(\nBackup not uploaded.\n";
    exit(1);
}

$requestData = $utils->getLastRequestInfo();
if (503 === $requestData['http_code']) {
    // Returned when a backup restore process is already ongoing or if the lock can not be acquired
    echo "I'm sorry, backup restore process is already ongoing or the lock can not be acquired.\n";
    exit(1);
}
if (200 !== $requestData['http_code']) {
    echo sprintf("Response code: %d\n\n%s\n", $requestData['http_code'], $response);
    exit(1);
}

echo sprintf("Backup '%s' uploaded and restored on PBX %s.\n", $backupName, PBX_B_IP_ADDRESS);
