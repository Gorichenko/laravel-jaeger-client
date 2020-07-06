<?php

namespace NixEnterprise\JaegerClient\LogCleaner;

/**
 * Class LogCleaner
 * @package NixEnterprise\JaegerClient\LogCleaner
 */
class LogCleaner
{

    /**
     * @var int
     */
    protected $maxLength = 300;

    /**
     * @var string
     */
    protected $cutoffIndicator = '...';

    /**
     * @var array
     */
    protected $logs = [];

    /**
     * @return LogCleaner
     */
    public function clean(): LogCleaner
    {
        $this->trimToMaxLength();

        return $this;
    }

    protected function trimToMaxLength()
    {
        $this->trimToMaxLengthRecursive($this->logs);
    }

    /**
     * @param $logs
     */
    protected function trimToMaxLengthRecursive(&$logs)
    {
        foreach ($logs as &$value) {
            if (is_array($value)) {
                $this->trimToMaxLengthRecursive($value);
                continue;
            }

            if (!is_string($value)) {
                continue;
            }

            $isLonger = strlen($value) > $this->maxLength;
            if (!$isLonger)
                continue;

            $value = substr($value, 0, $this->maxLength);
            $value .= $this->cutoffIndicator;
        }
    }

    /**
     * @param int $maxLength
     * @return LogCleaner
     */
    public function setMaxLength(int $maxLength): LogCleaner
    {
        $this->maxLength = $maxLength;
        return $this;
    }

    /**
     * @param array $logs
     * @return LogCleaner
     */
    public function setLogs(array $logs): LogCleaner
    {
        $this->logs = $logs;
        return $this;
    }

    /**
     * @param string $cutoffIndicator
     * @return LogCleaner
     */
    public function setCutoffIndicator(string $cutoffIndicator): LogCleaner
    {
        $this->cutoffIndicator = $cutoffIndicator;
        return $this;
    }

    /**
     * @return array
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

}
