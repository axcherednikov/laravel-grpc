<?php

declare(strict_types=1);

namespace App\Grpc;

use App\Contracts\Grpc\ServiceInvokerContract;
use Google\Protobuf\Internal\Message;
use Illuminate\Contracts\Foundation\Application;
use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\Exception\InvokeException;
use Spiral\RoadRunner\GRPC\Method;
use Spiral\RoadRunner\GRPC\StatusCode;
use Throwable;

class LaravelServiceInvoker implements ServiceInvokerContract
{
    public function __construct(protected Application $app)
    {
    }

    public function invoke(string $interface, Method $method, ContextInterface $context, ?string $input): string
    {
        $instance = $this->getApplication()->make($interface);

        $out = $instance->{$method->getName()}($context, $this->makeInput($method, $input));

        try {
            return $out->serializeToString();
        } catch (Throwable $e) {
            throw new InvokeException($e->getMessage(), StatusCode::INTERNAL, $e);
        }
    }

    public function getApplication(): Application
    {
        return $this->app;
    }

    private function makeInput(Method $method, ?string $body): Message
    {
        try {
            $class = $method->getInputType();

            /** @var Message $in */
            $in = new $class();
            $in->mergeFromString($body);

            return $in;
        } catch (Throwable $e) {
            throw new InvokeException($e->getMessage(), StatusCode::INTERNAL);
        }
    }
}
