<?php

declare(strict_types = 1);

namespace App\Console\Actions;

use App\Parsers\TikListParser;
use App\UrlFetcher;

class FetchTikListAction extends AbstractAction
{
    /**
     * @return int
     * @throws \Exception
     */
    public function __invoke(): int
    {
        echo "Fetching TIK list\n";
        $response = UrlFetcher::fetch('http://www.sverdlovsk.izbirkom.ru/stranitsy-tik/stranitsy-territorialny-izbiratelnykh-komissiy.php', $statusCode);
        echo "Response {$statusCode}\n";
        if ($statusCode != 200) {
            throw new \Exception("Failed to fetch TIK list");
        }
        $tikList = TikListParser::parse($response);
        $tikListFile = $this->resources->getDirectoryHelper()->getTikListFilePath();
        if (@file_put_contents($tikListFile, json_encode($tikList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
            throw new \Exception("Failed to save TIK list into {$tikListFile}");
        }
        echo "\nTIK list saved into {$tikListFile}\n";
        echo "\nWell done!\n";
        return 0;
    }
}
