<?php
/**
 * Copyright (C) 2019 by NetResults S.r.l. ( http://www.netresults.it )
 * Author(s):
 *     Roberto Santini         <r.santini@netresults.it>.
 */
use NetResults\KalliopePBX\RestApiUtils;

require_once '../vendor/autoload.php';

$restApiUtils = new RestApiUtils();
$tenantSalt = $restApiUtils->getTenantSalt('default', '192.168.23.249');

for ($i = 0; $i < 10; ++$i) {
    echo $restApiUtils->generateAuthHeader('admin', 'default', 'admin', $tenantSalt)."\n";
}
