<?php

namespace DaveRandom\AsyncMicrosoftTranslate;

use Amp\Artax\FormBody;
use Amp\Artax\HttpClient;
use Amp\Artax\Request as HttpRequest;
use Amp\Artax\Response as HttpResponse;
use Amp\Promise;
use function Amp\resolve;
use function Room11\DOMUtils\domdocument_load_xml;

class Client
{
    private $httpClient;

    private function sendApiRequest(HttpRequest $request)
    {
        /** @var HttpResponse $response */
        $response = yield $this->httpClient->request($request);

        return domdocument_load_xml($response->getBody());
    }

    private function callApiGetMethod(string $accessToken, string $method, array $params = null)
    {
        $url = SERVICE_URL . '/' . $method . ($params ? '?' . http_build_query($params) : '');

        $request = (new HttpRequest)
            ->setUri($url)
            ->setHeader('Authorization', 'Bearer ' . $accessToken);

        return yield from $this->sendApiRequest($request);
    }

    private function callApiPostMethod(string $accessToken, string $method, \DOMDocument $body)
    {
        $url = SERVICE_URL . '/' . $method;

        $request = (new HttpRequest)
            ->setUri($url)
            ->setMethod('POST')
            ->setHeader('Authorization', 'Bearer ' . $accessToken)
            ->setHeader('Content-Type', 'text/xml')
            ->setBody($body->saveXML());

        return yield from $this->sendApiRequest($request);
    }

    private function doGetAccessToken(Credentials $credentials)
    {
        $body = (new FormBody)
            ->addField('grant_type', 'client_credentials')
            ->addField('scope', BASE_URL)
            ->addField('client_id', $credentials->getClientId())
            ->addField('client_secret', $credentials->getClientSecret());

        $request = (new HttpRequest)
            ->setMethod('POST')
            ->setUri(AUTH_URL)
            ->setBody($body);

        /** @var HttpResponse $response */
        $response = yield $this->httpClient->request($request);

        $decoded = json_try_decode($response->getBody());

        if (!empty($decoded->error)){
            throw new \RuntimeException($decoded->error_description);
        }

        return $decoded->access_token;
    }

    private function doGetSupportedLanguages(string $accessToken, string $locale)
    {
        /** @var \DOMDocument $doc */
        $doc = yield from $this->callApiGetMethod($accessToken, 'GetLanguagesForTranslate');

        $codes = $languages = [];

        foreach ($doc->getElementsByTagName('string') as $string) {
            $codes[] = $string->textContent;
        }

        $doc = yield from $this->callApiPostMethod($accessToken, 'GetLanguageNames?locale=' . urlencode($locale), $doc);

        foreach ($doc->getElementsByTagName('string') as $i => $string) {
            $languages[$codes[$i]] = $string->textContent;
        }

        asort($languages);

        return $languages;
    }

    private function doDetectLanguage(string $accessToken, string $text)
    {
        /** @var \DOMDocument $result */
        $result = yield from $this->callApiGetMethod($accessToken, 'Detect', ['text' => $text]);

        return $result->textContent;
    }

    private function doGetTranslation(string $accessToken, string $text, string $to, string $from = null)
    {
        if ($from === null) {
            $from = yield from $this->doDetectLanguage($accessToken, $text);
        }

        /** @var \DOMDocument $result */
        $result = yield from $this->callApiGetMethod($accessToken, 'Translate', [
            'text' => $text,
            'from' => $from,
            'to' => $to,
        ]);

        return $result->textContent;
    }

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function getAccessToken(Credentials $credentials): Promise
    {
        return resolve($this->doGetAccessToken($credentials));
    }

    public function getSupportedLanguages(string $accessToken, string $locale = DEFAULT_LOCALE): Promise
    {
        return resolve($this->doGetSupportedLanguages($accessToken, $locale));
    }

    public function detectLanguage(string $accessToken, string $text): Promise
    {
        return resolve($this->doDetectLanguage($accessToken, $text));
    }

    public function getTranslation(string $accessToken, string $text, string $to, string $from = null): Promise
    {
        return resolve($this->doGetTranslation($accessToken, $text, $from, $to));
    }
}
