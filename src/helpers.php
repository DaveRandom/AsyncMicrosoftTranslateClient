<?php declare(strict_types = 1);

namespace DaveRandom\AsyncMicrosoftTranslate;

const AUTH_URL    = 'https://api.cognitive.microsoft.com/sts/v1.0/issueToken';
const BASE_URL    = 'http://api.microsofttranslator.com';
const SERVICE_URL = BASE_URL . '/V2/Http.svc';

const DEFAULT_LOCALE = 'en';
