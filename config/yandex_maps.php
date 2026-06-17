<?php

return [

    'base_url' => 'https://yandex.ru',

    'api_endpoint' => 'https://yandex.ru/maps/api/business/fetchReviews',

    'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36',

    'headers' => [
        'accept' => '*/*',
        'accept-language' => 'en-US,en;q=0.6',
        'sec-ch-ua' => '"Brave";v="149", "Chromium";v="149", "Not)A;Brand";v="24"',
        'sec-ch-ua-mobile' => '?0',
        'sec-ch-ua-platform' => '"Linux"',
        'sec-fetch-dest' => 'empty',
        'sec-fetch-mode' => 'cors',
        'sec-fetch-site' => 'same-origin',
        'priority' => 'u=1, i',
        'sec-gpc' => '1',
    ],

    'html_headers' => [
        'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'accept-language' => 'en-US,en;q=0.6',
        'sec-ch-ua' => '"Brave";v="149", "Chromium";v="149", "Not)A;Brand";v="24"',
        'sec-ch-ua-mobile' => '?0',
        'sec-ch-ua-platform' => '"Linux"',
        'sec-fetch-dest' => 'document',
        'sec-fetch-mode' => 'navigate',
        'sec-gpc' => '1',
    ],

    'parsing' => [
        'page_size' => 50,
        'max_pages' => 12,
        'min_delay_ms' => 1000,
        'max_delay_ms' => 3000,
        'rate_limit_minutes' => 15,
        'concurrency' => 3,
    ],

    'retry' => [
        'tries' => 3,
        'backoff' => [1, 5, 10],
    ],

];
