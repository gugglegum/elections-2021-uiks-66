<?php

namespace App\Console\Actions;

use App\Parsers\TikPageParser;
use App\Parsers\TikPassportParser;
use App\UrlFetcher;

class FetchUikListAction extends AbstractAction
{
    private $summary = [
        'parsed' => 0,
        'skipped' => 0,
    ];

    /**
     * @return int
     * @throws \Throwable
     */
    public function __invoke(): int
    {
        $tikListFile = $this->resources->getDirectoryHelper()->getTikListFilePath();
        if (($tikListJson = file_get_contents($tikListFile)) === false) {
            throw new \Exception("Failed to read {$tikListFile}");
        }
        if (($tikList = json_decode($tikListJson, true)) === NULL) {
            throw new \Exception("Failed to decode JSON from {$tikListFile}");
        }

        $uikList = [];
        $tikListNotParsed = [];

        $tikCounter = 0;
        foreach ($tikList as $tik) {
            $tikCounter++;
            echo "\n{$tikCounter}. TIK \"{$tik['name']}\"\n";
            if (preg_match('/^http:\/\/sverdlovsk\.izbirkom\.ru\/stranitsy-tik\/\d+/', $tik['url'])) {
                echo "Fetching TIK page {$tik['url']}\n";
                $response = UrlFetcher::fetch($tik['url'], $statusCode);
                echo "Response {$statusCode}\n";
                if ($statusCode != 200) {
                    throw new \Exception("Failed to fetch TIK page");
                }
                try {
                    $tikPageDetails = TikPageParser::parse($response);
                } catch (\Exception $e) {
                    echo "{$e->getMessage()} - skipped\n";
                    $this->summary['skipped']++;
                    $tikListNotParsed[] = $tik;
                    continue;
                }

                foreach ($tikPageDetails['passportUrls'] as $tikPassportUrl) {
                    echo "Fetch TIK passport {$tikPassportUrl}\n";
                    $response = UrlFetcher::fetch($tikPassportUrl, $statusCode);
                    echo "Response {$statusCode}\n";
                    if ($statusCode != 200) {
                        throw new \Exception("Failed to fetch TIK passport");
                    }
                    try {
                        $tikPassport = TikPassportParser::parse($response);
                    } catch (\Exception $e) {
                        echo "{$e->getMessage()} - skipped\n";
                        $this->summary['skipped']++;
                        $tikListNotParsed[] = $tik;
                        continue;
                    }
                    foreach ($tikPassport['uiks'] as $uik) {
                        $uikList[] = array_merge($uik, [
                            'tik' => [
                                'name' => $tik['name'],
                            ],
                        ]);
                    }
                }

                $this->summary['parsed']++;
//                break;
//                var_dump($tikPassportDetails);

            } else {
                echo "No handle for URL {$tik['url']}\n";
                $this->summary['skipped']++;
                $tikListNotParsed[] = $tik;
            }
        }

        $uikListFile = $this->resources->getDirectoryHelper()->getUikListFilePath();
        if (@file_put_contents($uikListFile, json_encode($uikList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
            throw new \Exception("Failed to save UIK list into {$uikListFile}");
        }
        echo "\nUIK list saved into {$uikListFile}\n";

        echo "List of not parsed TIKs:\n";
        echo json_encode($tikListNotParsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

        echo "Summary\n";
        echo "Parsed: {$this->summary['parsed']}\n";
        echo "Skipped: {$this->summary['skipped']}\n";

        echo "\nWell done!\n";
        return 0;
    }
}
