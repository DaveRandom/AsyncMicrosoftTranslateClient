<?php

namespace DaveRandom\AsyncMicrosoftTranslate\Examples;

use DaveRandom\AsyncMicrosoftTranslate\Client;
use DaveRandom\AsyncMicrosoftTranslate\Credentials;
use Amp\Artax\Client as HttpClient;
use function Amp\resolve;

require_once __DIR__ . '/../vendor/autoload.php';

$credentials = new Credentials(client_id, azure_subscription_key);

$client = new Client(new HttpClient());

function token($client, $credentials)
{
    return yield $client->getAccessToken($credentials);
}

\Amp\run(function () use ($client, $credentials) {
    //text to be translated
    $text = 'Alles ist entweder eine Kartoffel oder nicht';

    //access token - note that token has lifespan
    $token = yield $client->getAccessToken($credentials);

    //get list of supported languages
    $langs = yield $client->getSupportedLanguages($token);

    //detect text language
    $lang = yield $client->detectLanguage($token, $text);

    //get translation using token
    $trans = yield $client->getTranslation($token, $text, 'en', $lang);
    echo $trans;
});
