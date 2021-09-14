<?php

declare(strict_types=1);

namespace App;

use App\Helpers\RetryHelper;
use Throwable;

class UrlFetcher
{
    const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.159 Safari/537.36';
    const MIN_DELAY_MS = 100;
    const MAX_DELAY_MS = 1000;

    /**
     * @param string $url
     * @param  $statusCode
     * @return string
     * @throws Throwable
     */
    public static function fetch(string $url, &$statusCode): string
    {
        static $lastFetchAt;

        if ($lastFetchAt !== null) {
            $sleepMs = max(0, (int) round((rand(self::MIN_DELAY_MS, self::MAX_DELAY_MS) / 1000 - (gettimeofday(true) - $lastFetchAt)) * 1000000));
            // echo "Sleeping " . (number_format($sleepMs / 1000000, 3)) . " s\n";
            usleep($sleepMs);
            $lastFetchAt = gettimeofday(true);
        }

        $lastFetchAt = gettimeofday(true);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);

        $retryHelper = new RetryHelper();
        $response = $retryHelper->doSeveralAttempts(function() use ($ch) {
            $response = curl_exec($ch);
            if ($response === false) {
                throw new Exception(curl_error($ch));
            }
            return $response;
        }, 10);

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $response;
    }
}
