# WLS API Client

## Basic Usage

### Installation

Install with [Composer](https://getcomposer.org/).

Example `composer.json`:

```
{
    "name": "MyApplication",
    "require": {
        "mediabandit/wls-client": "dev-master"
    }
}
```

### Usage

```
require_once '/path/to/wls-client/vendor/autoload.php';

$urlToQuery = '[valid API URL]';

$options = array(
    'publicKey' => '[Your Public Key]',
    'privateKey' => '[Your Private Key]'
);

$client    = new Wls\Client($options);
$signedUrl = $client->signUrl($urlToQuery);

// Query the URL using cURL

```
