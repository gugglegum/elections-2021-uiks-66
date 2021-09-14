<?php

namespace App\Console\Actions;

use App\Parsers\UikPassportParser;
use App\UrlFetcher;
use Throwable;

class FetchUikDetailsAction extends AbstractAction
{
    /**
     * @return int
     * @throws Throwable
     * @throws \Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function __invoke(): int
    {
        $cache = $this->resources->getCache();

        $uikListFile = $this->resources->getDirectoryHelper()->getUikListFilePath();
        if (($uikListJson = file_get_contents($uikListFile)) === false) {
            throw new \Exception("Failed to read {$uikListFile}");
        }
        if (($uikList = json_decode($uikListJson, true)) === NULL) {
            throw new \Exception("Failed to decode JSON from {$uikListFile}");
        }

        $uikCounter = 0;

        foreach ($uikList as $uik) {
            $uikCounter++;

            echo "\n{$uikCounter}. UIK № {$uik['number']}\n";

            $uikDetailsFile = $this->resources->getDirectoryHelper()->getUikDetailsFilePath($uik['number']);

//            if (file_exists($uikDetailsFile)) {
//                echo "Details already exists - skipping\n";
//                continue;
//            }

            echo "Fetching UIK passport {$uik['url']}\n";

            $cacheKey = 'http-response-for-url|' . md5($uik['url']);
            $cacheItem = $cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                echo "Using response from cache\n";
                $response = $cache->getItem($cacheKey)->get();
            } else {
                $response = UrlFetcher::fetch($uik['url'], $statusCode);
                echo "Response {$statusCode}\n";
                if ($statusCode != 200) {
                    throw new \Exception("Failed to fetch UIK page");
                }
                $cacheItem->set($response)
                    ->expiresAfter(3600 * 24 * 30);
                $cache->save($cacheItem);
            }

            try {
                $uikPassport = UikPassportParser::parse($response);

                if (@file_put_contents($uikDetailsFile, json_encode($uikPassport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
                    throw new \Exception("Failed to save TIK list into {$uikDetailsFile}");
                }
                echo "\nUIK details saved into {$uikDetailsFile}\n";

            } catch (\Exception $e) {
                echo "{$e->getMessage()} - skipped\n";
                continue;
            }
        }

        echo "\nWell done!\n";
        return 0;
    }
}
