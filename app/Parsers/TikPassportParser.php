<?php

declare(strict_types=1);

namespace App\Parsers;

use App\Helpers\StringHelper;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Exception;

class TikPassportParser
{
    /**
     * @param string $response
     * @return array
     * @throws Exception
     */
    public static function parse(string $response): array
    {
        $tikPassportDetails = [];
        $dom = new DOMDocument();
        if (@$dom->loadHTML($response)) {
//            var_dump($response); die;
            $xpath = new DOMXPath($dom);
            echo "Looking for passport section\n";
            $passportResult = $xpath->query("//div[@class='passport']");
            if ($passportResult->count() == 1) {
                echo "Found\n";
                $passportElement = $passportResult->item(0);

//                echo "Looking for TIK address\n";
//                $addressResult = $xpath->query("//div[@class='adress']", $passportElement);
//                if ($addressResult->count() == 1) {
//                    $addressElement = $addressResult->item(0);
//                    $tikPassportDetails['address'] = ltrim(preg_replace('/^Адрес:/', '', StringHelper::trimRedundantWhiteSpaces($addressElement->nodeValue)));
//                } else {
//                    if ($addressResult->count() == 0) {
//                        throw new Exception("No address found in HTML");
//                    } else {
//                        throw new Exception("More than one address found in HTML");
//                    }
//                }

                echo "Looking for UIK links\n";
                $uikLinksResult = $xpath->query("//div[@class='uiks']/div[@class='items']/div[@class='item']/a", $passportElement);
                if ($uikLinksResult->count() > 0) {
                    echo "Found {$uikLinksResult->count()} UIK links\n";
                    $tikPassportDetails['uiks'] = [];
                    /** @var DOMElement $uikLink */
                    foreach ($uikLinksResult as $uikLink) {
                        $uikNumber = trim($uikLink->nodeValue);
                        $uikUrl = $uikLink->attributes->getNamedItem('href')->nodeValue;
                        echo " * {$uikNumber} - {$uikUrl}\n";
                        $tikPassportDetails['uiks'][] = [
                            'number' => $uikNumber,
                            'url' => 'http://ikso.org/' . $uikUrl,
                        ];
                    }
                } else {
                    echo "No one found\n";
                }

            } else {
                if ($passportResult->count() == 0) {
                    throw new Exception("No passport section found in HTML");
                } else {
                    throw new Exception("More than one passport section found in HTML");
                }
            }
        } else {
            throw new Exception("Failed to parse HTML");
        }
        return $tikPassportDetails;
    }

}
