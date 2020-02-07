<?php
/**
 * Copyright (C) 2020 by NetResults S.r.l. ( http://www.netresults.it )
 * Author(s):
 *     Roberto Santini         <r.santini@netresults.it>.
 */
use NetResults\KalliopePBX\RestApiUtils;

require_once __DIR__.'/../vendor/autoload.php';

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
     * @var string
     */
    private $pbxIpAddress = '';

    /**
     * @var string
     */
    private $username = '';

    /**
     * @var string
     */
    private $password = '';

    /**
     * @var string
     */
    private $tenantDomain = '';

    /**
     * @var array
     */
    private $lastRequestInfo = [];

    /**
     * Utils constructor.
     *
     * @param string $pbxIpAddress
     * @param string $username
     * @param string $password
     * @param string $tenantDomain
     */
    public function __construct(string $pbxIpAddress = '', string $username = '', string $password = '', string $tenantDomain = '')
    {
        if ('' !== $pbxIpAddress) {
            $this->pbxIpAddress = $pbxIpAddress;
        } elseif (defined('PBX_IP_ADDRESS')) {
            $this->pbxIpAddress = PBX_IP_ADDRESS;
        }

        if ('' !== $username) {
            $this->username = $username;
        } elseif (defined('USERNAME')) {
            $this->username = USERNAME;
        }

        if ('' !== $password) {
            $this->password = $password;
        } elseif (defined('PASSWORD')) {
            $this->password = PASSWORD;
        }

        if ('' !== $tenantDomain) {
            $this->tenantDomain = $tenantDomain;
        } elseif (defined('TENANT_DOMAIN')) {
            $this->tenantDomain = TENANT_DOMAIN;
        }

        $this->restApiUtils = new RestApiUtils();
    }

    /**
     * Returns the version of the running firmware.
     *
     * @return string
     */
    public function getFirmwareVersion(): string
    {
        $requestUrl = sprintf(static::GET_FIRMWARE_VERSION_URL, $this->getPbxIpAddress());
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
                    $this->getUsername(),
                    $this->getTenantDomain(),
                    $this->getPassword(),
                    $this->restApiUtils->getTenantSalt($this->getTenantDomain(), $this->getPbxIpAddress()),
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

    /**
     * @return string
     */
    public function getPbxIpAddress(): string
    {
        return $this->pbxIpAddress;
    }

    /**
     * @param string $pbxIpAddress
     *
     * @return Utils
     */
    public function setPbxIpAddress(string $pbxIpAddress): Utils
    {
        $this->pbxIpAddress = $pbxIpAddress;

        return $this;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return Utils
     */
    public function setUsername(string $username): Utils
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return Utils
     */
    public function setPassword(string $password): Utils
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string
     */
    public function getTenantDomain(): string
    {
        return $this->tenantDomain;
    }

    /**
     * @param string $tenantDomain
     *
     * @return Utils
     */
    public function setTenantDomain(string $tenantDomain): Utils
    {
        $this->tenantDomain = $tenantDomain;

        return $this;
    }
}
