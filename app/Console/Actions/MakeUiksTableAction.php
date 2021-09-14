<?php

namespace App\Console\Actions;

use gugglegum\CsvRw\CsvFormat;
use gugglegum\CsvRw\CsvReader;
use gugglegum\CsvRw\CsvWriter;

class MakeUiksTableAction extends AbstractAction
{
    /**
     * @return int
     * @throws \Exception
     */
    public function __invoke(): int
    {
        $uikListFile = $this->resources->getDirectoryHelper()->getUikListFilePath();
        if (($uikListJson = file_get_contents($uikListFile)) === false) {
            throw new \Exception("Failed to read {$uikListFile}");
        }
        if (($uikList = json_decode($uikListJson, true)) === NULL) {
            throw new \Exception("Failed to decode JSON from {$uikListFile}");
        }

        $uikNumbersWithKoib = $this->getUikNumbersWithKoib();

        $csv = new CsvWriter(new CsvFormat([
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
        ]));

        $outputFile = PROJECT_ROOT_DIR . "/output/uiks-66-sverdlovsk.csv";
        $csv->open($outputFile, CsvWriter::WITH_HEADERS, ['ТИК', 'УИК', 'Адрес', 'Телефон', 'Здание', 'Избирателей', 'КОИБ']);

        $uikCounter = 0;
        foreach ($uikList as $uik) {
            $uikCounter++;
            echo "{$uikCounter}. UIK № {$uik['number']}\n";

            $uikDetailsFile = $this->resources->getDirectoryHelper()->getUikDetailsFilePath($uik['number']);

            if (!file_exists($uikDetailsFile)) {
                echo "Details not exists - skipping\n";
                continue;
            }

            if (($uikDetailsJson = file_get_contents($uikDetailsFile)) === false) {
                throw new \Exception("Failed to read {$uikDetailsFile}");
            }
            if (($uikDetails = json_decode($uikDetailsJson, true)) === NULL) {
                throw new \Exception("Failed to decode JSON from {$uikDetailsFile}");
            }

            $csv->writeRow([
                'ТИК' => $uik['tik']['name'],
                'УИК' => $uik['number'],
                'Адрес' => $uikDetails['address'] ?? '',
                'Телефон' => $uikDetails['phone'] ?? '',
                'Здание' => $uikDetails['building'] ?? '',
                'Избирателей' => $uikDetails['numberOfVoters'] ?? '',
                'КОИБ' => in_array($uik['number'], $uikNumbersWithKoib) ? 'Да' : 'Нет',
            ]);

        }
        $csv->close();

        echo "\nWell done!\n";
        return 0;
    }

    private function getUikNumbersWithKoib(): array
    {
        $index = 1;
        $koibFile = $this->resources->getDirectoryHelper()->getKoibFilePath($index);
        $uiksNumbersWithKoib = [];
        while (file_exists($koibFile)) {
            echo "Reading {$koibFile}\n";
            $csv = new CsvReader(new CsvFormat([
                'delimiter' => "\t",
                'enclosure' => '"',
                'escape' => '\\',
            ]));
            $csv->open($koibFile, CsvReader::WITHOUT_HEADERS, ['index', 'number', 'address', 'voters']);
            foreach ($csv as $row) {
                $uiksNumbersWithKoib[] = (int) $row['number'];
            }
            $csv->close();

            $index++;
            $koibFile = $this->resources->getDirectoryHelper()->getKoibFilePath($index);
        }
        return $uiksNumbersWithKoib;
    }

}
