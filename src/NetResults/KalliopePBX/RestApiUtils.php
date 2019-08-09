<?php
/**
 * Copyright (C) 2019 by NetResults S.r.l. ( http://www.netresults.it )
 * Author(s):
 *     Roberto Santini         <r.santini@netresults.it>.
 */

namespace NetResults\KalliopePBX;

use DateTime;
use DateTimeZone;
use Exception;
use RuntimeException;

/**
 * Class RestApiUtils.
 */
class RestApiUtils
{
    private const GET_TENANT_SALT_URL = '/rest/salt/%s';
    public const X_AUTHENTICATE_HEADER_NAME = 'X-authenticate';

    /**
     * @var string
     */
    private $tenantSalt;

    /**
     * @var string
     */
    private $tenantDomain;

    /**
     * @var string
     */
    private $pbxIpAddress;

    /**
     * Contatta il PBX per recuperare il salt del tenant.
     * Se il modulo cURL non e' installato lancia un'eccezione.
     *
     * @param string $tenantDomain
     * @param string $pbxIpAddress
     *
     * @return string
     */
    public function getTenantSalt(string $tenantDomain, string $pbxIpAddress): string
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL module not installed.');
        }

        if ('' === $tenantDomain || '' === $pbxIpAddress) {
            return '';
        }

        if ($tenantDomain === $this->tenantDomain && $pbxIpAddress === $this->pbxIpAddress && '' !== $this->tenantSalt) {
            return $this->tenantSalt;
        }

        $urlString = 'http://'.$pbxIpAddress.sprintf(self::GET_TENANT_SALT_URL, $tenantDomain);
        $ch = curl_init($urlString);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curlResponse = curl_exec($ch);
        curl_close($ch);
        $this->tenantSalt = '';
        if (false !== $curlResponse) {
            $responseArray = json_decode($curlResponse, true);
            if (array_key_exists('salt', $responseArray)) {
                $this->tenantSalt = $responseArray['salt'];
            }
        }

        return $this->tenantSalt;
    }

    /**
     * Genera e restituisce il valore dell'header da aggiungere alla richiesta per l'autenticazione delle API.
     * Se il salt e' vuoto viene  utilizzato quello eventualmente presente nello stato della classe recuperato dall'invocazione
     * di getTenantSalt.
     * Se il parametro returnFullHeader e' true viene restituita la stringa completa da passare alla funzione header() di
     * PHP, diversamente viene restituito solamente il valore e non il nome dell'header.
     *
     * @param string      $username
     * @param string      $domain
     * @param string      $cleanPassword
     * @param string|null $salt
     * @param bool        $returnFullHeader
     *
     * @return string
     *
     * @throws Exception
     */
    public function generateAuthHeader(
        string $username,
        string $domain,
        string $cleanPassword,
        ?string $salt = null,
        bool $returnFullHeader = false
    ): string {
        if ('' === $username || '' === $domain || '' === $cleanPassword || ((null === $salt || '' === $salt) && (null === $this->tenantSalt || '' === $this->tenantSalt))) {
            return '';
        }
        if ('' === $salt || null === $salt) {
            $salt = $this->tenantSalt;
        }

        $digestPassword = $this->calculateUserPassword($cleanPassword, $salt);
        if ('' === $digestPassword) {
            return '';
        }

        $createdDt = new DateTime(null, new DateTimeZone('UTC'));
        $created = $createdDt->format('Y-m-d\TH:i:s\Z');

        $nonce = md5($created.uniqid(mt_rand(), true));
        $digest = base64_encode(hash('sha256', $nonce.$digestPassword.$username.$domain.$created, true));
        $header = 'RestApiUsernameToken Username="'.$username.'", Domain="'.$domain.'", Digest="'.$digest.'", Nonce="'.$nonce.'", Created="'.$created.'"';
        if ($returnFullHeader) {
            $header = self::X_AUTHENTICATE_HEADER_NAME.': '.$header;
        }

        return $header;
    }

    /**
     * Calcola la password cifrata dell'utente.
     *
     * @param $cleanPassword
     * @param $salt
     *
     * @return string
     */
    public function calculateUserPassword(string $cleanPassword, string $salt): string
    {
        if ('' === $cleanPassword || '' === $salt) {
            return '';
        }

        return hash('sha256', $cleanPassword.'{'.$salt.'}');
    }
}
