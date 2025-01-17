<?php

declare(strict_types=1);

namespace PHPUnitRetry\Util;

use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Exception;
use PHPUnit\Util\Xml;

final class Config
{
    const DEFAULT_RETRY_COUNT = 3;

    private DOMDocument $document;

    public static function getInstance(string $filename): self
    {
        $realPath = \realpath($filename);

        if ($realPath === false) {
            throw new Exception(
                \sprintf(
                    'Could not read "%s".',
                    $filename
                )
            );
        }

        return new self($realPath);
    }

    private function __construct(string $filename)
    {
        $this->document = (new Xml\Loader)->loadFile($filename, false, true, true);
    }

    public function getRetryCount(): int
    {
        /** @var DOMElement $root */
        $root = $this->document->documentElement;

        if ($root->hasAttribute('baseRetryCount')) {
            return $this->getInteger(
                (string) $root->getAttribute('baseRetryCount')
            );
        }

        return self::DEFAULT_RETRY_COUNT;
    }

    private function getInteger(string $value): int
    {
        if (\is_numeric($value)) {
            return (int) $value;
        }

        return self::DEFAULT_RETRY_COUNT;
    }
}
