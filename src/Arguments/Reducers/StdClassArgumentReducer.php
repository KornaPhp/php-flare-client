<?php

namespace Spatie\FlareClient\Arguments\Reducers;

use Spatie\FlareClient\Arguments\ArgumentReducers;
use Spatie\FlareClient\Arguments\ReduceArgumentPayloadAction;
use Spatie\FlareClient\Arguments\ReducedArgument\ReducedArgument;
use Spatie\FlareClient\Arguments\ReducedArgument\ReducedArgumentContract;
use Spatie\FlareClient\Arguments\ReducedArgument\TruncatedReducedArgument;
use Spatie\FlareClient\Arguments\ReducedArgument\UnReducedArgument;
use stdClass;

class StdClassArgumentReducer
{
    private ArrayArgumentReducer $arrayArgumentReducer;

    public function __construct()
    {
        $this->arrayArgumentReducer = new ArrayArgumentReducer();
    }

    public function execute(mixed $argument): ReducedArgumentContract
    {
        if (! $argument instanceof stdClass) {
            return new UnReducedArgument();
        }

        /** @var ReducedArgument $reducedArray */
        $reducedArray = $this->arrayArgumentReducer->execute((array) $argument);

        return new ReducedArgument(
            $reducedArray->value,
            stdClass::class
        );
    }
}
