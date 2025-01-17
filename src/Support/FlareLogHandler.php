<?php

namespace Spatie\LaravelIgnition\Support;

use InvalidArgumentException;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Spatie\FlareClient\Flare;
use Spatie\FlareClient\Report;
use Throwable;

class FlareLogHandler extends AbstractProcessingHandler
{
    protected Flare $flare;

    protected int $minimumReportLogLevel = Logger::ERROR;

    public function __construct(Flare $flare, $level = Logger::DEBUG, $bubble = true)
    {
        $this->flare = $flare;

        parent::__construct($level, $bubble);
    }

    public function setMinimumReportLogLevel(int $level)
    {
        if (! in_array($level, Logger::getLevels())) {
            throw new InvalidArgumentException('The given minimum log level is not supported.');
        }

        $this->minimumReportLogLevel = $level;
    }

    protected function write(array $record): void
    {
        if (! $this->shouldReport($record)) {
            return;
        }

        if ($this->hasException($record)) {
            $this->flare->report($record['context']['exception']);

            return;
        }

        if (config('flare.send_logs_as_events')) {
            if ($this->hasValidLogLevel($record)) {
                $this->flare->reportMessage(
                    $record['message'],
                    'Log ' . Logger::getLevelName($record['level']),
                    function (Report $flareReport) use ($record) {
                        foreach ($record['context'] as $key => $value) {
                            $flareReport->context($key, $value);
                        }
                    }
                );
            }
        }
    }

    protected function shouldReport(array $report): bool
    {
        if (! config('flare.key')) {
            return false;
        }

        return $this->hasException($report) || $this->hasValidLogLevel($report);
    }

    protected function hasException(array $report): bool
    {
        $context = $report['context'];

        return isset($context['exception']) && $context['exception'] instanceof Throwable;
    }

    protected function hasValidLogLevel(array $report): bool
    {
        return $report['level'] >= $this->minimumReportLogLevel;
    }
}
