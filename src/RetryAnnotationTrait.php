<?php

namespace PHPUnitRetry;

use InvalidArgumentException;
use PHPUnit\Util\Test;
use PHPUnitRetry\Util\Config;
use PHPUnitRetry\Util\ConfigFile;
use function array_slice;
use function explode;
use function is_callable;
use function is_numeric;
use function sprintf;
use function var_export;

/**
 * Trait for validating @retry annotations.
 */
trait RetryAnnotationTrait
{
    private function getRetryAttemptsAnnotation(): int
    {
        $annotations = Test::parseTestMethodAnnotations(get_class($this), $this->getName());

        $config = Config::getInstance(ConfigFile::getConfigFilename());
        $retries = $config->getRetryCount();

        if (isset($annotations['method']['retryAttempts'][0])) {
            $retries = $annotations['method']['retryAttempts'][0];
        } elseif (isset($annotations['class']['retryAttempts'][0])) {
            $retries = $annotations['class']['retryAttempts'][0];
        }

        return $this->parseRetryAttemptsAnnotation($retries);
    }

    /** @param string|int $retries */
    private function parseRetryAttemptsAnnotation($retries): int
    {
        if ('' === $retries) {
            throw new InvalidArgumentException(
                'The @retryAttempts annotation requires an integer as an argument'
            );
        }
        if (false === is_numeric($retries)) {
            throw new InvalidArgumentException(sprintf(
                'The @retryAttempts annotation must be an integer but got "%s"',
                var_export($retries, true)
            ));
        }
        if ((float) $retries !== (float) (int) $retries) {
            throw new InvalidArgumentException(sprintf(
                'The @retryAttempts annotation must be an integer but got "%s"',
                (float) $retries
            ));
        }
        $retries = (int) $retries;
        if ($retries < 0) {
            throw new InvalidArgumentException(sprintf(
                'The @retryAttempts annotation must be 0 or greater but got "%s".',
                $retries
            ));
        }
        return $retries;
    }

    private function getRetryDelaySecondsAnnotation(): int
    {
        $annotations = Test::parseTestMethodAnnotations(get_class($this), $this->getName());
        $retryDelaySeconds = 0;

        if (isset($annotations['method']['retryDelaySeconds'][0])) {
            $retryDelaySeconds = $annotations['method']['retryDelaySeconds'][0];
        } elseif (isset($annotations['class']['retryDelaySeconds'][0])) {
            $retryDelaySeconds = $annotations['class']['retryDelaySeconds'][0];
        }

        return $this->parseRetryDelaySecondsAnnotation($retryDelaySeconds);
    }

    /** @param string|int $retryDelaySeconds */
    private function parseRetryDelaySecondsAnnotation($retryDelaySeconds): int
    {
        if ('' === $retryDelaySeconds) {
            throw new InvalidArgumentException(
                'The @retryDelaySeconds annotation requires an integer as an argument'
            );
        }
        if (false === is_numeric($retryDelaySeconds)) {
            throw new InvalidArgumentException(sprintf(
                'The @retryDelaySeconds annotation must be an integer but got "%s"',
                var_export($retryDelaySeconds, true)
            ));
        }
        if ((float) $retryDelaySeconds !== (float) (int) $retryDelaySeconds) {
            throw new InvalidArgumentException(sprintf(
                'The @retryDelaySeconds annotation must be an integer but got "%s"',
                floatval($retryDelaySeconds)
            ));
        }
        $retryDelaySeconds = (int) $retryDelaySeconds;
        if ($retryDelaySeconds < 0) {
            throw new InvalidArgumentException(sprintf(
                'The @retryDelaySeconds annotation must be 0 or greater but got "%s".',
                $retryDelaySeconds
            ));
        }
        return $retryDelaySeconds;
    }

    private function getRetryDelayMethodAnnotation(): ?array
    {
        $annotations = Test::parseTestMethodAnnotations(get_class($this), $this->getName());

        if (isset($annotations['method']['retryDelayMethod'][0])) {
            $delayAnnotation = $annotations['method']['retryDelayMethod'];
        } elseif (isset($annotations['class']['retryDelayMethod'][0])) {
            $delayAnnotation = $annotations['class']['retryDelayMethod'];
        } else {
            return null;
        }

        $delayAnnotations = explode(' ', $delayAnnotation[0]);
        $delayMethod = $delayAnnotations[0];
        $delayMethodArgs = array_slice($delayAnnotations, 1);

        return [
            $this->parseRetryDelayMethodAnnotation($delayMethod),
            $delayMethodArgs,
        ];
    }

    private function parseRetryDelayMethodAnnotation(string $delayMethod): string
    {
        if ('' === $delayMethod) {
            throw new InvalidArgumentException(
                'The @retryDelayMethod annotation requires a callable as an argument'
            );
        }
        if (false === is_callable([$this, $delayMethod])) {
            throw new InvalidArgumentException(sprintf(
                'The @retryDelayMethod annotation must be a method in your test class but got "%s"',
                $delayMethod
            ));
        }
        return $delayMethod;
    }

    private function getRetryForSecondsAnnotation(): ?int
    {
        $annotations = Test::parseTestMethodAnnotations(get_class($this), $this->getName());

        if (isset($annotations['method']['retryForSeconds'][0])) {
            $retryForSeconds = $annotations['method']['retryForSeconds'][0];
        } elseif (isset($annotations['class']['retryForSeconds'][0])) {
            $retryForSeconds = $annotations['class']['retryForSeconds'][0];
        } else {
            return null;
        }

        return $this->parseRetryForSecondsAnnotation($retryForSeconds);
    }

    /** @param string|int|float $retryForSeconds */
    private function parseRetryForSecondsAnnotation($retryForSeconds): int
    {
        if ('' === $retryForSeconds) {
            throw new InvalidArgumentException(
                'The @retryForSeconds annotation requires an integer as an argument'
            );
        }
        if (false === is_numeric($retryForSeconds)) {
            throw new InvalidArgumentException(sprintf(
                'The @retryForSeconds annotation must be an integer but got "%s"',
                var_export($retryForSeconds, true)
            ));
        }
        if ((float) $retryForSeconds !== (float)(int) $retryForSeconds) {
            throw new InvalidArgumentException(sprintf(
                'The @retryForSeconds annotation must be an integer but got "%s"',
                (float) $retryForSeconds
            ));
        }
        $retryForSeconds = (int) $retryForSeconds;
        if ($retryForSeconds < 0) {
            throw new InvalidArgumentException(sprintf(
                'The @retryForSeconds annotation must be 0 or greater but got "%s".',
                $retryForSeconds
            ));
        }
        return $retryForSeconds;
    }

    private function getRetryIfExceptionAnnotations(): ?array
    {
        $annotations = Test::parseTestMethodAnnotations(get_class($this), $this->getName());

        if (isset($annotations['method']['retryIfException'][0])) {
            $retryIfExceptions = [];
            foreach ($annotations['method']['retryIfException'] as $retryIfException) {
                $this->validateRetryIfExceptionAnnotation($retryIfException);
                $retryIfExceptions[] = $retryIfException;
            }
            return $retryIfExceptions;
        }

        return null;
    }

    private function validateRetryIfExceptionAnnotation(string $retryIfException): void
    {
        if ('' === $retryIfException) {
            throw new InvalidArgumentException(
                'The @retryIfException annotation requires a class name as an argument'
            );
        }

        if (!class_exists($retryIfException)) {
            throw new InvalidArgumentException(sprintf(
                'The @retryIfException annotation must be an instance of Exception but got "%s"',
                $retryIfException
            ));
        }
    }

    private function getRetryIfMethodAnnotation(): ?array
    {
        $annotations = Test::parseTestMethodAnnotations(get_class($this), $this->getName());

        if (!isset($annotations['method']['retryIfMethod'][0])) {
            return null;
        }

        $retryIfMethodAnnotation = explode(' ', $annotations['method']['retryIfMethod'][0]);
        $retryIfMethod = $retryIfMethodAnnotation[0];
        $retryIfMethodArgs = array_slice($retryIfMethodAnnotation, 1);

        $this->validateRetryIfMethodAnnotation($retryIfMethod);

        return [
            $retryIfMethod,
            $retryIfMethodArgs,
        ];
    }

    private function validateRetryIfMethodAnnotation(string $retryIfMethod): void
    {
        if ('' === $retryIfMethod) {
            throw new InvalidArgumentException(
                'The @retryIfMethod annotation requires a callable as an argument'
            );
        }
        if (false === is_callable([$this, $retryIfMethod])) {
            throw new InvalidArgumentException(sprintf(
                'The @retryIfMethod annotation must be a method in your test class but got "%s"',
                $retryIfMethod
            ));
        }
    }
}
