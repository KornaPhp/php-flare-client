<?php

namespace Spatie\FlareClient\Recorders\LogRecorder;

use Spatie\FlareClient\Concerns\Recorders\RecordsSpanEvents;
use Spatie\FlareClient\Contracts\Recorders\SpanEventsRecorder;
use Spatie\FlareClient\Enums\MessageLevels;
use Spatie\FlareClient\Enums\RecorderType;
use Spatie\FlareClient\Enums\SpanEventType;
use Spatie\FlareClient\Recorders\Recorder;
use Spatie\FlareClient\Spans\SpanEvent;

class LogRecorder extends Recorder implements SpanEventsRecorder
{
    /** @use RecordsSpanEvents<SpanEvent> */
    use RecordsSpanEvents;

    const DEFAULT_MINIMAL_LEVEL = MessageLevels::Debug;

    protected MessageLevels $minimalLevel;

    protected function configure(array $config): void
    {
        $this->minimalLevel = $config['minimal_level'] ?? self::DEFAULT_MINIMAL_LEVEL;

        $this->configureRecorder($config);
    }

    public static function type(): string|RecorderType
    {
        return RecorderType::Log;
    }

    public function record(
        ?string $message,
        MessageLevels $level = MessageLevels::Info,
        array $context = [],
        array $attributes = [],
    ): ?SpanEvent {
        if ($level->getOrder() > $this->minimalLevel->getOrder()) {
            return null;
        }

        return $this->spanEvent(
            name: "Log entry",
            attributes: fn () => [
                'flare.span_event_type' => SpanEventType::Log,
                'log.message' => $message,
                'log.level' => $level->value,
                'log.context' => $context,
                ...$attributes,
            ],
        );
    }
}
