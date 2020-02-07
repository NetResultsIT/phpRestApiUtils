<?php
/**
 * Copyright (C) 2020 by NetResults S.r.l. ( http://www.netresults.it )
 * Author(s):
 *     Roberto Santini         <r.santini@netresults.it>
 */

require_once __DIR__.'/../globals.php';
require_once __DIR__.'/../Utils.php';

// REST API URL
define('UPLOAD_AND_RESTORE_BACKUP_URL', 'http://%s/rest/backup/restore');

// For this example we need the CLI to ask the backup name to download
if ('cli' !== PHP_SAPI) {
    echo sprintf("You are in '%s' environment, 'cli' environment is required!\n", PHP_SAPI);
    exit(1);
}

// Instantiate the Utils object
$utils = new Utils();

// Ask to the user the name of the backup to upload
echo 'Insert the name of the backup you want to upload and restore: ';
$backupName = trim(fgets(STDIN));
if ('' === $backupName) {
    echo "I'm sorry, I can't upload and restore anything if you don't say to me what you want!\n";
    exit(1);
}
if (false !== strpos($backupName, ' ')) {
    echo "The backup name can't contains spaces.\n";
    exit(1);
}

// Search the backup file
if (0 !== strpos($backupName, DIRECTORY_SEPARATOR) &&
    0 !== strpos($backupName, '.'.DIRECTORY_SEPARATOR) &&
    0 !== strpos($backupName, '..'.DIRECTORY_SEPARATOR)) {
    // Only file name given, prepend the current working dir
    $cwd = getcwd();
    if (false === $cwd) {
        echo "Unable to get the current folder, I can't find the backup file.\n";
        exit(1);
    }
    $backupName = $cwd.DIRECTORY_SEPARATOR.$backupName;
}
$realPath = realpath($backupName);
if (false === $realPath) {
    echo sprintf("Unable to find the file '%s'!\n", $backupName);
    exit(1);
}
$postName = basename($realPath);
if (1 !== preg_match('/\.bak$/', $postName)) {
    // The backup filename must ends with '.bak'
    $postName .= '.bak';
}

// Upload the backup
$response = $utils->executeRequest(
    sprintf(UPLOAD_AND_RESTORE_BACKUP_URL, PBX_IP_ADDRESS),
    ['backupFile' => curl_file_create($realPath, '', $postName)]
);

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

echo sprintf("Backup '%s' uploaded and restored.\n", $backupName);
