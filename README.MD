# REST API Utils
Contiene una classe `PHP` che si occupa di generare gli header per l'autenticazione delle API REST.

#### Installazione

Nel vostro `composer.json` aggiungere il seguente blocco di configurazione

```
"repositories": [
  {
    "type": "git",
    "url": "git@github.com:robertonetresults/phpRestApiUtils.git"
  }
],
"require": {
  "net-results/php-rest-api-utils": "dev-master"
}
```

dopo di che eseguire il comando

```
php composer.phar update
```

#### Utilizzo

```php
<?php
use NetResults\KalliopePBX\RestApiUtils;

require_once '../vendor/autoload.php';

$restApiUtils = new RestApiUtils();
$tenantSalt = $restApiUtils->getTenantSalt('default', 'your.kalliopepbx.address');
echo $restApiUtils->generateAuthHeader('admin', 'default', 'admin', $tenantSalt)."\n";
```

#### Esempi

Nella catella `examples` sono presenti alcuni script di esmpio di utilizzo delle API. E' presente il file `globals.php` nel quale sono presenti delle `DEFINE` con dei parametri globali utilizzati dagli script di esempio. Modificare i valori delle `DEFINE` secondo le proprie necessità per rendere gli esempi funzionanti nei propri scenari.

```bash
cd examples
# Modificare i valori delle DEFINE in globals.php
php <script.php>
```