<?php
/**
 * Copyright (C) 2020 by NetResults S.r.l. ( http://www.netresults.it )
 * Author(s):
 *     Roberto Santini         <r.santini@netresults.it>.
 */
use NetResults\KalliopePBX\RestApiUtils;

require_once '../vendor/autoload.php';
require_once 'globals.php';

class Utils
{
    private const GET_FIRMWARE_VERSION_URL = 'http://%s/rest/dashboard/firmwareVersion';

    /**
     * @var RestApiUtils
     */
    private $restApiUtils;

    /**
     * Utils constructor.
     */
    public function __construct()
    {
        $this->restApiUtils = new RestApiUtils();
    }

    /**
     * Returns the version of the running firmware.
     *
     * @return string
     */
    public function getFirmwareVersion(): string
    {
        $requestUrl = sprintf(static::GET_FIRMWARE_VERSION_URL, PBX_IP_ADDRESS);
        $fwVersion = $this->executeRequest($requestUrl);

        return false !== $fwVersion ? $fwVersion : '';
    }

    /**
     * Sends the request to KalliopePBX and returns the response. Returns false if something went wrong.
     *
     * @param string $requestUrl
     *
     * @return bool|string
     */
    public function executeRequest(string $requestUrl)
    {
        if ('' === $requestUrl) {
            // Invalid URL
            return false;
        }

        // Generate the authentication header.
        $tenantSalt = $this->restApiUtils->getTenantSalt(TENANT_DOMAIN, PBX_IP_ADDRESS);
        $authHeader = $this->restApiUtils->generateAuthHeader(USERNAME, TENANT_DOMAIN, PASSWORD, $tenantSalt, false);

        // Init the cURL object to do the REST API request
        $ch = curl_init($requestUrl);

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

        // The cURL resources must be always closed.
        curl_close($ch);

        if (false === $curlResponse) {
            // Something went wrong, prints what's append.
            echo 'cURL error: '.curl_error($ch)."\n";
        }

        return $curlResponse;
    }
}
