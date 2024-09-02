<?php

namespace Spatie\FlareClient\FlareMiddleware;

use Closure;
use Psr\Container\ContainerInterface;
use Spatie\FlareClient\AttributesProviders\RequestAttributesProvider;
use Spatie\FlareClient\ReportFactory;
use Spatie\FlareClient\Support\Redactor;
use Spatie\FlareClient\Support\Runtime;
use Symfony\Component\HttpFoundation\Request;

class AddRequestInformation implements FlareMiddleware
{

    public static function register(ContainerInterface $container, array $config): Closure
    {
        return fn () => new self(
            new RequestAttributesProvider($container->get(Redactor::class)),
            $config
        );
    }

    public function __construct(
        protected RequestAttributesProvider $attributesProvider,
        array $config
    ) {
    }

    public function handle(ReportFactory $report, Closure $next)
    {
        if ($this->isRunningInConsole()) {
            return $next($report);
        }
        
        $report->addAttributes(
            $this->getAttributes()
        );

        return $next($report);
    }

    protected function isRunningInConsole(): bool
    {
        return Runtime::runningInConsole();
    }

    protected function getAttributes(): array
    {
        $request = Request::createFromGlobals();


        return $this->attributesProvider->toArray($request);
    }
}
