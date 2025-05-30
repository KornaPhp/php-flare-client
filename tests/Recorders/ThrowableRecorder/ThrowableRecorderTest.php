<?php

use Spatie\FlareClient\Enums\SpanEventType;
use Spatie\FlareClient\FlareConfig;
use Spatie\FlareClient\Tests\Shared\ExpectSpan;
use Spatie\FlareClient\Tests\Shared\ExpectSpanEvent;
use Spatie\FlareClient\Tests\Shared\ExpectTrace;
use Spatie\FlareClient\Tests\Shared\ExpectTracer;
use Spatie\FlareClient\Tests\TestClasses\ExceptionWithContext;

it('can trace throwables', function () {
    $flare = setupFlare(
        fn (FlareConfig $config) => $config->collectErrorsWithTraces()->collectCommands()->trace()->alwaysSampleTraces()
    );

    $flare->tracer->clearTracesAfterExport = false;

    $flare->command()->recordStart('command', []);

    $flare->report(new ExceptionWithContext('We failed'));

    $flare->command()->recordEnd(1);

    ExpectTracer::create($flare)
        ->trace(
            fn (ExpectTrace $trace) => $trace
            ->span(
                fn (ExpectSpan $span) => $span
                ->hasSpanEventCount(1)
                ->spanEvent(
                    fn (ExpectSpanEvent $spanEvent) => $spanEvent
                    ->hasName('Exception - Spatie\FlareClient\Tests\TestClasses\ExceptionWithContext')
                    ->hasType(SpanEventType::Exception)
                    ->hasAttributeCount(5)
                    ->hasAttribute('exception.message', 'We failed')
                    ->hasAttribute('exception.type', 'Spatie\FlareClient\Tests\TestClasses\ExceptionWithContext')
                    ->hasAttribute('exception.handled', false)
                    ->hasAttribute('exception.id')
                )
            )
        );
});
