<?php

declare(strict_types=1);

namespace App\Parsers;

use DOMDocument;
use DOMXPath;
use Exception;

class TikPageParser
{
    /**
     * @param string $response
     * @return array
     * @throws Exception
     */
    public static function parse(string $response): array
    {
        $tikPageDetails = [];
        $dom = new DOMDocument();
        if (@$dom->loadHTML($response)) {
            $xpath = new DOMXPath($dom);
            echo "Looking for TIK passport link\n";
            $passportLinkResult = $xpath->query("//*[normalize-space(text()) = 'Паспорт комиссии']", $dom);
            if ($passportLinkResult->count() == 1) {
                echo "Found - {$passportLinkResult->item(0)->attributes->getNamedItem('href')->nodeValue}\n";
                $tikPageDetails['passportUrls'][] = $passportLinkResult->item(0)->attributes->getNamedItem('href')->nodeValue;
            } else {
                if ($passportLinkResult->count() == 0) {
                    $passportLinkResult = $xpath->query("//*[contains(text(), 'Электоральный паспорт')]", $dom);
                    if ($passportLinkResult->count() > 0) {
                        foreach ($passportLinkResult as $passportLinkElement) {
                            $passportLink = $passportLinkElement->attributes->getNamedItem('href')->nodeValue;
                            echo "Found - {$passportLink}\n";
                            $tikPageDetails['passportUrls'][] = $passportLink;
                        }
                    } else {
                        throw new Exception("No TIK passport link(s) found in HTML");
                    }
                } else {
                    throw new Exception("More than one TIK passport link(s) found in HTML");
                }
            }
        } else {
            throw new Exception("Failed to parse HTML");
        }
        return $tikPageDetails;
    }

}
