<?php

namespace App\Helpers;

class DirectoryHelper
{
    public function getTikListFilePath(): string
    {
        return PROJECT_ROOT_DIR . "/data/tik-list.json";
    }

    public function getUikListFilePath(): string
    {
        return PROJECT_ROOT_DIR . "/data/uik-list.json";
    }

    public function getUikDetailsFilePath(int $uikNumber): string
    {
        return PROJECT_ROOT_DIR . "/data/uik-details-{$uikNumber}.json";
    }

    public function getKoibFilePath(int $number): string
    {
        return PROJECT_ROOT_DIR . "/data/koib-{$number}.csv";
    }
}
