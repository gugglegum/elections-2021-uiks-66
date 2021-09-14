<?php

declare(strict_types = 1);

namespace App;

use App\Helpers\DirectoryHelper;
use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;

class ResourceManager
{
    /**
     * @return DirectoryHelper
     */
    public function getDirectoryHelper(): DirectoryHelper
    {
        static $directoryHelper;
        if (!isset($directoryHelper)) {
            $directoryHelper = new DirectoryHelper();
        }
        return $directoryHelper;
    }

    /**
     * @return \Phpfastcache\Drivers\Files\Driver
     * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverCheckException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverNotFoundException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheLogicException
     * @throws \ReflectionException
     */
    public function getCache(): \Phpfastcache\Drivers\Files\Driver
    {
        $cachePath = PROJECT_ROOT_DIR . '/cache';
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0777);
        }
        /** @var \Phpfastcache\Drivers\Files\Driver $cache */
        $cache = CacheManager::getInstance('files', new ConfigurationOption([
            'path' => $cachePath,
        ]));
        return $cache;
    }
}
