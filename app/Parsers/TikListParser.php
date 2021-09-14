<?php

declare(strict_types=1);

namespace App\Parsers;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Exception;

class TikListParser
{
    /**
     * @param string $response
     * @return array
     * @throws Exception
     */
    public static function parse(string $response): array
    {
        $tikList = [];
        $dom = new DOMDocument();
        if (@$dom->loadHTML($response)) {
//            file_put_contents('tik-list.html', $response);
            $xpath = new DOMXPath($dom);
            echo "Looking for tables with TIK lists\n";
            $tableResult = $xpath->query("//div[@class='tab-content']/div[@class='tab-pane active']/div[@class='text_block']/table[@class='table table-striped table-hover table-condensed']", $dom);
            if ($tableResult->count() > 0) {
                echo "Found {$tableResult->count()} table(s)\n";
                for ($tableIndex = 0; $tableIndex < $tableResult->count(); $tableIndex++) {
                    $tableElement = $tableResult->item($tableIndex);
                    $linksResult = $xpath->query("tbody/tr/td/a", $tableElement);
                    /** @var DOMElement $linkElement */
                    foreach($linksResult as $linkElement) {
                        echo " * {$linkElement->nodeValue}\n";
                        $tikList[] = [
                            'name' => $linkElement->nodeValue,
                            'url' => $linkElement->attributes->getNamedItem('href')->nodeValue,
                        ];
                    }
                }
            } else {
                throw new Exception("No TIK tables found in HTML");
            }
        } else {
            throw new Exception("Failed to parse HTML");
        }

        return $tikList;
    }
}
