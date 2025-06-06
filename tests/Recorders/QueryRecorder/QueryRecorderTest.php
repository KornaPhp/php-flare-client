<?php

use Spatie\FlareClient\Enums\SpanType;
use Spatie\FlareClient\Recorders\QueryRecorder\QueryRecorder;
use Spatie\FlareClient\Spans\Span;
use Spatie\FlareClient\Tests\Shared\FakeTime;
use Spatie\FlareClient\Time\TimeHelper;

beforeEach(function () {
    FakeTime::setup('2019-01-01 12:34:56');
});

it('can trace queries', function () {
    $flare = setupFlare(alwaysSampleTraces: true);

    $recorder = new QueryRecorder(
        tracer: $flare->tracer,
        backTracer: $flare->backTracer,
        config: [
            'with_traces' => true,
            'with_errors' => false,
            'max_items_with_errors' => null,
            'include_bindings' => true,
            'find_origin' => false,
            'find_origin_threshold' => null,
        ]
    );

    $flare->tracer->startTrace();

    $recorder->record(
        'select * from users where id = ?',
        TimeHelper::milliseconds(300),
        ['id' => 1],
        'users',
        'mysql'
    );

    expect($flare->tracer->currentTrace())->toHaveCount(1);

    $span = array_values($flare->tracer->currentTrace())[0];

    expect($span)
        ->toBeInstanceOf(Span::class)
        ->spanId->not()->toBeNull()
        ->traceId->toBe($flare->tracer->currentTraceId())
        ->start->toBe(1546346096000000000 - TimeHelper::milliseconds(300))
        ->end->toBe(1546346096000000000)
        ->name->toBe('Query - select * from users where id = ?');

    expect($span->attributes)
        ->toHaveCount(5)
        ->toHaveKey('db.system', 'mysql')
        ->toHaveKey('db.name', 'users')
        ->toHaveKey('db.statement', 'select * from users where id = ?')
        ->toHaveKey('db.sql.bindings', ['id' => 1])
        ->toHaveKey('flare.span_type', SpanType::Query);
});

it('can report queries without tracing', function () {
    $flare = setupFlare(alwaysSampleTraces: true);

    $flare->tracer->startTrace();

    $recorder = new QueryRecorder(
        $flare->tracer,
        $flare->backTracer,
        config: [
            'with_traces' => false,
            'with_errors' => true,
            'max_items_with_errors' => null,
            'include_bindings' => true,
            'find_origin' => false,
            'find_origin_threshold' => null,
        ]
    );

    $recorder->record(
        'select * from users where id = ?',
        TimeHelper::milliseconds(300),
        ['id' => 1],
        'users',
        'mysql'
    );

    expect($recorder->getSpans())->toHaveCount(1);
    expect($flare->tracer->currentTrace())->toHaveCount(0);

    $span = $recorder->getSpans()[0];

    expect($span)
        ->toBeInstanceOf(Span::class)
        ->spanId->not()->toBeNull()
        ->traceId->not()->toBeNull()
        ->start->toBe(1546346096000000000 - TimeHelper::milliseconds(300))
        ->end->toBe(1546346096000000000)
        ->name->toBe('Query - select * from users where id = ?');

    expect($span->attributes)
        ->toHaveCount(5)
        ->toHaveKey('db.system', 'mysql')
        ->toHaveKey('db.name', 'users')
        ->toHaveKey('db.statement', 'select * from users where id = ?')
        ->toHaveKey('db.sql.bindings', ['id' => 1])
        ->toHaveKey('flare.span_type', SpanType::Query);
});

it('can disable the inclusion of bindings', function () {
    $flare = setupFlare();

    $recorder = new QueryRecorder(
        $flare->tracer,
        $flare->backTracer,
        config: [
            'with_traces' => false,
            'with_errors' => true,
            'max_items_with_errors' => null,
            'include_bindings' => false,
            'find_origin' => false,
            'find_origin_threshold' => null,
        ]
    );

    $recorder->record('select * from users where id = ?', TimeHelper::milliseconds(300), ['id' => 1], 'users', 'mysql');

    expect($recorder->getSpans()[0]->attributes)->not()->toHaveKey('db.sql.bindings');
});

it('can find the origin of a query when tracing and a threshold is met', function () {
    $flare = setupFlare(alwaysSampleTraces: true);

    $recorder = new QueryRecorder(
        $flare->tracer,
        $flare->backTracer,
        config: [
            'with_traces' => true,
            'with_errors' => false,
            'max_items_with_errors' => null,
            'include_bindings' => true,
            'find_origin' => true,
            'find_origin_threshold' => TimeHelper::milliseconds(300),
        ]
    );

    $flare->tracer->startTrace();

    $recorder->record('select * from users where id = ?', TimeHelper::milliseconds(300), ['id' => 1], 'users', 'mysql');

    expect($flare->tracer->currentTrace())->toHaveCount(1);

    $span = array_values($flare->tracer->currentTrace())[0];

    expect($span->attributes)->toHaveKeys([
        'code.filepath',
        'code.lineno',
        'code.function',
    ]);
});

it('can find the origin of a query when tracing no threshold is set', function () {
    $flare = setupFlare(alwaysSampleTraces: true);

    $recorder = new QueryRecorder(
        $flare->tracer,
        $flare->backTracer,
        config: [
            'with_traces' => true,
            'with_errors' => false,
            'max_items_with_errors' => null,
            'include_bindings' => true,
            'find_origin' => true,
            'find_origin_threshold' => null,
        ]
    );

    $flare->tracer->startTrace();

    $recorder->record('select * from users where id = ?', TimeHelper::milliseconds(300), ['id' => 1], 'users', 'mysql');

    expect($flare->tracer->currentTrace())->toHaveCount(1);

    $span = array_values($flare->tracer->currentTrace())[0];

    expect($span->attributes)->toHaveKeys([
        'code.filepath',
        'code.lineno',
        'code.function',
    ]);
});

it('will not find the origin of a query when tracing and a threshold is not met', function () {
    $flare = setupFlare(alwaysSampleTraces: true);

    $recorder = new QueryRecorder(
        $flare->tracer,
        $flare->backTracer,
        config: [
            'with_traces' => true,
            'with_errors' => false,
            'max_items_with_errors' => null,
            'include_bindings' => true,
            'find_origin' => true,
            'find_origin_threshold' => TimeHelper::milliseconds(300),
        ]
    );

    $flare->tracer->startTrace();

    $recorder->record('select * from users where id = ?', 99, ['id' => 1], 'users', 'mysql');

    expect($flare->tracer->currentTrace())->toHaveCount(1);

    $span = array_values($flare->tracer->currentTrace())[0];

    expect($span->attributes)->not()->toHaveKeys([
        'code.filepath',
        'code.lineno',
        'code.function',
    ]);
});

it('will not find the origin of a query when only reporting', function () {
    $flare = setupFlare(alwaysSampleTraces: true);

    $recorder = new QueryRecorder(
        $flare->tracer,
        $flare->backTracer,
        config: [
            'with_traces' => false,
            'with_errors' => true,
            'max_items_with_errors' => null,
            'include_bindings' => true,
            'find_origin' => true,
            'find_origin_threshold' => TimeHelper::milliseconds(300),
        ]
    );

    $recorder->record('select * from users where id = ?', TimeHelper::milliseconds(299), ['id' => 1], 'users', 'mysql');

    expect($recorder->getSpans())->toHaveCount(1);

    $span = $recorder->getSpans()[0];

    expect($span->attributes)->not()->toHaveKeys([
        'code.filepath',
        'code.lineno',
        'code.function',
    ]);
});
