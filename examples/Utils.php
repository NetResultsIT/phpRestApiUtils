<?php
/**
 * Copyright (C) 2020 by NetResults S.r.l. ( http://www.netresults.it )
 * Author(s):
 *     Roberto Santini         <r.santini@netresults.it>.
 */
use NetResults\KalliopePBX\RestApiUtils;

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/globals.php';

class Utils
{
    private const DEFAULT_ACCEPT_HEADER = 'Accept: application/json';
    private const GET_FIRMWARE_VERSION_URL = 'http://%s/rest/dashboard/firmwareVersion';

    public const REQUEST_TYPE_DELETE = 'DELETE';
    public const REQUEST_TYPE_GET = 'GET';
    public const REQUEST_TYPE_POST = 'POST';
    public const REQUEST_TYPE_PUT = 'PUT';

    /**
     * @var RestApiUtils
     */
    private $restApiUtils;

    /**
     * @var array
     */
    private $lastRequestInfo = [];

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
     * @param string            $requestUrl
     * @param array|string|null $postData
     * @param array             $headers
     * @param string            $type
     *
     * @return bool|string
     */
    public function executeRequest(
        string $requestUrl,
        $postData = null,
        array $headers = [],
        string $type = Utils::REQUEST_TYPE_GET
    ) {
        if ('' === $requestUrl) {
            // Invalid URL
            return false;
        }

        // Check if $type is valid, if not force to GET
        $type = in_array($type,
            [
                static::REQUEST_TYPE_DELETE,
                static::REQUEST_TYPE_GET,
                static::REQUEST_TYPE_POST,
                static::REQUEST_TYPE_PUT,
            ],
            true
        ) ? $type : static::REQUEST_TYPE_GET;

        // Init the cURL object to do the REST API request
        $ch = curl_init($requestUrl);

        // Handle POST data
        if (null !== $postData) {
            // We assume that the $headers already contains the 'Content-Type' header with the right value.
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

            // The request type can't be GET with a post data. If $type is GET we force the request to be a POST.
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, (static::REQUEST_TYPE_GET !== $type) ? $type : static::REQUEST_TYPE_POST);
        } else {
            // Sets the request type according to the $type parameter.
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        }

        // Add the headers to the request
        $this->integrateRequestHeaders($headers);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        /*
         * From PHP curl_setopt documentation:
         * TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it directly.
         */
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute the request
        $curlResponse = curl_exec($ch);

        // Collect request info
        $this->lastRequestInfo = curl_getinfo($ch);

        // The cURL resources must be always closed.
        curl_close($ch);

        if (false === $curlResponse) {
            // Something went wrong, prints what's append.
            echo 'cURL error: '.curl_error($ch)."\n";
        }

        return $curlResponse;
    }

    /**
     * Adds the 'Accept' and 'X-authenticate' header if are not present in the headers array.
     *
     * @param array $headers
     */
    private function integrateRequestHeaders(array &$headers): void
    {
        $acceptPresent = false;
        $xAuthPresent = false;

        // Iterate over all headers searching for the 'Accept' and 'X-authenticate' headers
        foreach ($headers as $header) {
            if (0 === stripos(ltrim($header), 'accept:')) {
                $acceptPresent = true;
                continue;
            }

            if (0 === stripos(ltrim($header), RestApiUtils::X_AUTHENTICATE_HEADER_NAME.':')) {
                $xAuthPresent = true;
            }
        }

        if (!$acceptPresent) {
            // 'Accept' header not present, we add the default one.
            $headers[] = static::DEFAULT_ACCEPT_HEADER;
        }
        if (!$xAuthPresent) {
            // Authentication header not present, generate and add it.
            $headers[] = RestApiUtils::X_AUTHENTICATE_HEADER_NAME.': '.$this->restApiUtils->generateAuthHeader(
                    USERNAME,
                    TENANT_DOMAIN,
                    PASSWORD,
                    $this->restApiUtils->getTenantSalt(TENANT_DOMAIN, PBX_IP_ADDRESS),
                    false
                );
        }
    }

    /**
     * Returns an array with all the data returned by the curl_getinfo function for the last executed request.
     *
     * @return array
     */
    public function getLastRequestInfo(): array
    {
        return $this->lastRequestInfo;
    }
}
