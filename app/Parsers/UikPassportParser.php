<?php

declare(strict_types=1);

namespace App\Parsers;

use App\Helpers\StringHelper;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Exception;

class UikPassportParser
{
    /**
     * @param string $response
     * @return array
     * @throws Exception
     */
    public static function parse(string $response): array
    {
        $uikPassportDetails = [];
        $dom = new DOMDocument();
        if (@$dom->loadHTML($response)) {
//            var_dump($response); die;
            $xpath = new DOMXPath($dom);
            echo "Looking for passport section\n";
            $passportResult = $xpath->query("//div[@class='passport']");
            if ($passportResult->count() == 1) {
                echo "Found\n";
                $passportElement = $passportResult->item(0);

                try {
                    $uikPassportDetails['phone'] = self::parsePhone($xpath, $passportElement);
                } catch (Exception $e) {
                    echo "{$e->getMessage()}\n";
                }

                try {
                    $uikPassportDetails['address'] = self::parseAddress($xpath, $passportElement);
                } catch (Exception $e) {
                    echo "{$e->getMessage()}\n";
                }

                try {
                    $uikPassportDetails['building'] = self::parseBuilding($xpath, $passportElement);
                } catch (Exception $e) {
                    echo "{$e->getMessage()}\n";
                }

                try {
                    $uikPassportDetails['numberOfVoters'] = self::parseNumberOfVoters($xpath, $passportElement);
                } catch (Exception $e) {
                    echo "{$e->getMessage()}\n";
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
        return $uikPassportDetails;
    }

    /**
     * @param DOMXPath $xpath
     * @param DOMElement $passportElement
     * @return string
     * @throws Exception
     */
    private static function parsePhone(DOMXPath $xpath, DOMElement $passportElement): string
    {
        echo "Looking for UIK phone\n";
        $result = $xpath->query("//div[@class='telephone']", $passportElement);
        if ($result->count() == 1) {
            $element = $result->item(0);
            return rtrim(ltrim(preg_replace('/^Телефон:/', '', StringHelper::trimRedundantWhiteSpaces($element->nodeValue))), '.');
        } else {
            if ($result->count() == 0) {
                throw new Exception("No phone found in HTML");
            } else {
                throw new Exception("More than one phone found in HTML");
            }
        }
    }

    /**
     * @param DOMXPath $xpath
     * @param DOMElement $passportElement
     * @return string
     * @throws Exception
     */
    private static function parseAddress(DOMXPath $xpath, DOMElement $passportElement): string
    {
        echo "Looking for UIK address\n";
        $result = $xpath->query("//div[@class='adress']", $passportElement);
        if ($result->count() == 1) {
            $element = $result->item(0);
            return rtrim(ltrim(preg_replace('/^Адрес:/', '', StringHelper::trimRedundantWhiteSpaces($element->nodeValue))), '.');
        } else {
            if ($result->count() == 0) {
                throw new Exception("No address found in HTML");
            } else {
                throw new Exception("More than one address found in HTML");
            }
        }
    }

    /**
     * @param DOMXPath $xpath
     * @param DOMElement $passportElement
     * @return string
     * @throws Exception
     */
    private static function parseBuilding(DOMXPath $xpath, DOMElement $passportElement): string
    {
        echo "Looking for UIK building\n";
        $result = $xpath->query("//div[@class='build']", $passportElement);
        if ($result->count() == 1) {
            $element = $result->item(0);
            return rtrim(ltrim(preg_replace('/^Здание:/', '', StringHelper::trimRedundantWhiteSpaces($element->nodeValue))), '.');
        } else {
            if ($result->count() == 0) {
                throw new Exception("No building found in HTML");
            } else {
                throw new Exception("More than one building found in HTML");
            }
        }
    }

    /**
     * @param DOMXPath $xpath
     * @param DOMElement $passportElement
     * @return int
     * @throws Exception
     */
    private static function parseNumberOfVoters(DOMXPath $xpath, DOMElement $passportElement): int
    {
        echo "Looking for number of voters\n";
        $result = $xpath->query("//div[@class='descript']", $passportElement);
        if ($result->count() == 1) {
            $element = $result->item(0);
            $textLines = preg_split('/\R+/u', $element->textContent, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($textLines as $textLine) {
                $textLine = StringHelper::trimRedundantWhiteSpaces($textLine);
                if (preg_match('/^Численность\s+избирателей/ui', trim($textLine))) {
//                    echo "Before:";
//                    var_dump($textLine);
                    $textLine = preg_replace('/\([^)]*\)/u', '', $textLine);
//                    echo "After: ";
//                    var_dump($textLine);
                    $textLine = preg_replace('/(?:по\s+состоянию\s+)?на\s+(?:\d\d\.\d\d\.\d\d\d\d|\d{1,2}\s+\w+(?:\s+\d{4})?)(?:\s+года|\s+г\.?)?/u', '', $textLine);
//                    $textLine = preg_replace('/(?:по\s+состоянию\s+)?на\s+\d{1,2}\s+\w+/u', '', $textLine);
//                    echo "After: ";
//                    var_dump($textLine);
                    $textLine = preg_replace('/[()\-–:.]/u', ' ', $textLine);
                    $textLine = StringHelper::trimRedundantWhiteSpaces($textLine);
//                    echo "After: ";
//                    var_dump($textLine);
                    if (preg_match('/^Численность избирателей (\d+)/ui', $textLine, $m)) {
//                        var_dump((int) $m[1]);
//                        sleep(1);
                        return (int) $m[1];
                    }
                }

            }
//            foreach ($textLines as $textLine) {
//                var_dump($textLine);
//            }
//            sleep(10);
//            fgets(STDIN);
            throw new Exception("No number of voters found in HTML");
        } else {
            if ($result->count() == 0) {
                throw new Exception("No description section found in HTML");
            } else {
                throw new Exception("More than one description section found in HTML");
            }
        }

//        $result = $xpath->query("//div[@class='descript']//*[contains(text(), 'Численность избирателей:')]", $passportElement);
//        if ($result->count() == 1) {
//            $element = $result->item(0);
//            $value = rtrim(ltrim(preg_replace('/^Численность избирателей:/', '', StringHelper::trimRedundantWhiteSpaces($element->nodeValue))), '.');
//            var_dump($value);
//            $value = strtok($value, " "); // Trim everything after a number (a first word)
//            if (preg_match('/^\d+$/', $value)) {
//                return (int) $value;
//            } else {
//                throw new Exception("Number of voters is not integer (\"{$value}\")");
//            }
//        } else {
//            if ($result->count() == 0) {
//                throw new Exception("No number of voters found in HTML");
//            } else {
//                throw new Exception("More than one number of voters found in HTML");
//            }
//        }


    }

}
