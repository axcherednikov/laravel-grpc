<?php

declare(strict_types=1);

namespace App\Grpc;

use App\Contracts\Grpc\ServiceInvokerContract;
use App\Contracts\Grpc\ServiceWrapperContract;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\Exception\NotFoundException;
use Spiral\RoadRunner\GRPC\Exception\ServiceException;
use Spiral\RoadRunner\GRPC\Method;
use Spiral\RoadRunner\GRPC\ServiceInterface;
use Spiral\RoadRunner\GRPC\StatusCode;

class ReflectionServiceWrapper implements ServiceWrapperContract
{
    protected string $name;

    protected array $methods = [];

    private ServiceInterface $service;

    public function __construct(
        protected ServiceInvokerContract $invoker,
        protected string $interface,
        ServiceInterface $service
    ) {
        $this->configure($interface, $service);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function invoke(string $method, ContextInterface $context, ?string $input): string
    {
        if (! isset($this->methods[$method])) {
            throw new NotFoundException(sprintf('Method `%s` not found in service `%s`.', $method, $this->name));
        }

        return $this->invoker->invoke($this->interface, $this->methods[$method], $context, $input);
    }

    protected function configure(string $interface, ServiceInterface $service)
    {
        try {
            $reflection = new \ReflectionClass($interface);

            if (! $reflection->hasConstant('NAME')) {
                $message = "Invalid service interface `{$interface}`, constant `NAME` not found.";
                throw ServiceException::create($message);
            }

            /** @var non-empty-string $name */
            $name = $reflection->getConstant('NAME');

            if (! is_string($name)) {
                $message = "Constant `NAME` of service interface `{$interface}` must be a type of string";
                throw ServiceException::create($message);
            }

            $this->name = $name;
        } catch (ReflectionException $e) {
            $message = "Invalid service interface `{$interface}`.";
            throw ServiceException::create($message, StatusCode::INTERNAL, $e);
        }

        if (! $service instanceof $interface) {
            throw ServiceException::create("Service handler does not implement `{$interface}`.");
        }

        $this->service = $service;

        // list of all available methods and their object types
        $this->methods = $this->fetchMethods($service);
    }

    protected function fetchMethods(ServiceInterface $interface): array
    {
        $reflection = new ReflectionClass($interface);

        $methods = [];
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (Method::match($method)) {
                $methods[$method->getName()] = Method::parse($method);
            }
        }

        return $methods;
    }
}
