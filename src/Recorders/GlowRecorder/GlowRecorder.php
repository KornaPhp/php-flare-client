<?php

namespace Spatie\FlareClient\Recorders\GlowRecorder;

use Spatie\FlareClient\Concerns\Recorders\RecordsSpanEvents;
use Spatie\FlareClient\Contracts\Recorders\SpanEventsRecorder;
use Spatie\FlareClient\Enums\MessageLevels;
use Spatie\FlareClient\Enums\RecorderType;
use Spatie\FlareClient\Enums\SpanEventType;
use Spatie\FlareClient\Recorders\Recorder;
use Spatie\FlareClient\Spans\SpanEvent;

class GlowRecorder extends Recorder implements SpanEventsRecorder
{
    /**  @use RecordsSpanEvents<SpanEvent> */
    use RecordsSpanEvents;

    public static function type(): string|RecorderType
    {
        return RecorderType::Glow;
    }

    public function record(
        string $name,
        MessageLevels $level = MessageLevels::Info,
        array $context = [],
        array $attributes = [],
    ): ?SpanEvent {
        return $this->spanEvent(
            name: "Glow - {$name}",
            attributes: fn () => [
                'flare.span_event_type' => SpanEventType::Glow,
                'glow.name' => $name,
                'glow.level' => $level->value,
                'glow.context' => $context,
                ...$attributes,
            ],
        );
    }
}
