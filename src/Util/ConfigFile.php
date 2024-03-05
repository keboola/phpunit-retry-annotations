<?php

namespace PHPUnitRetry\Util;

final class ConfigFile
{
    public static function getConfigFilename(): string
    {
        $reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);
        $fileName = $reflection->getFileName();
        assert(is_string($fileName), 'ReflectionClass::getFileName() returns string');
        $vendorDir = dirname(dirname($fileName));

        $projectRoot = dirname($vendorDir);
        if (file_exists($projectRoot . \DIRECTORY_SEPARATOR . 'phpunit-retry.xml')) {
            $configFilename = $projectRoot . \DIRECTORY_SEPARATOR . 'phpunit-retry.xml';
        } else {
            return dirname(__FILE__) . \DIRECTORY_SEPARATOR . 'phpunit-retry.xml';
        }

        return $configFilename;
    }
}
