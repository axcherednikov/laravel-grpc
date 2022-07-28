<?php

declare(strict_types=1);

namespace App\Grpc;

use App\Contracts\Grpc\GrpcKernelContract;
use App\Contracts\Grpc\ServiceInvokerContract;
use Google\Protobuf\Any;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Bootstrap\RegisterFacades;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Foundation\Bootstrap\SetRequestForConsole;
use Spiral\RoadRunner\GRPC\Context;
use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\Exception\GRPCException;
use Spiral\RoadRunner\GRPC\Exception\GRPCExceptionInterface;
use Spiral\RoadRunner\GRPC\Exception\NotFoundException;
use Spiral\RoadRunner\GRPC\Internal\Json;
use Spiral\RoadRunner\GRPC\ResponseHeaders;
use Spiral\RoadRunner\GRPC\ServiceInterface;
use Spiral\RoadRunner\GRPC\StatusCode;
use Spiral\RoadRunner\Payload;
use Spiral\RoadRunner\Worker;
use Throwable;

class GrpcKernel implements GrpcKernelContract
{
    protected Application $app;

    protected ServiceInvokerContract $invoker;

    protected array $services = [];

    protected array $bootstrappers = [
        LoadEnvironmentVariables::class,
        LoadConfiguration::class,
        HandleExceptions::class,
        RegisterFacades::class,
        SetRequestForConsole::class,
        RegisterProviders::class,
        BootProviders::class,
    ];

    public function __construct(Application $app, ServiceInvokerContract $invoker)
    {
        $this->app = $app;
        $this->invoker = $invoker;
    }

    public function bootstrap(): void
    {
        if (! $this->app->hasBeenBootstrapped()) {
            $this->app->bootstrapWith($this->bootstrappers());
        }
    }

    public function registerService(string $interface, ServiceInterface $service): self
    {
        $service = new ReflectionServiceWrapper($this->invoker, $interface, $service);

        $this->services[$service->getName()] = $service;

        return $this;
    }

    private function tick(string $body, array $data): array
    {
        $context = (new Context($data['context']))
            ->withValue(ResponseHeaders::class, new ResponseHeaders());

        $response = $this->invoke($data['service'], $data['method'], $context, $body);

        $responseHeaders = $context->getValue(ResponseHeaders::class);
        $responseHeadersString = $responseHeaders ? $responseHeaders->packHeaders() : '{}';

        return [$response, $responseHeadersString];
    }

    private function workerSend(Worker $worker, string $body, string $headers): void
    {
        $worker->respond(new Payload($body, $headers));
    }

    private function workerError(Worker $worker, string $message): void
    {
        $worker->error($message);
    }

    public function serve(Worker $worker = null, callable $finalize = null): void
    {
        $this->bootstrap();

        $worker ??= Worker::create();

        while (true) {
            $request = $worker->waitPayload();

            if ($request === null) {
                return;
            }

            try {
                $context = Json::decode($request->header);

                [$answerBody, $answerHeaders] = $this->tick($request->body, $context);

                $this->workerSend($worker, $answerBody, $answerHeaders);
            } catch (GRPCExceptionInterface $e) {
                $this->workerError($worker, $this->packError($e));
            } catch (Throwable $e) {
                $this->workerError($worker, $this->isDebugMode() ? (string) $e : $e->getMessage());
            } finally {
                if ($finalize !== null) {
                    isset($e) ? $finalize($e) : $finalize();
                }
            }
        }
    }

    public function getApplication(): Application
    {
        return $this->app;
    }

    protected function invoke(string $service, string $method, ContextInterface $context, string $body): string
    {
        if (! isset($this->services[$service])) {
            throw NotFoundException::create(sprintf('Service `%s` not found.', $service), StatusCode::NOT_FOUND);
        }

        return $this->services[$service]->invoke($method, $context, $body);
    }

    protected function bootstrappers(): array
    {
        return $this->bootstrappers;
    }

    protected function packError(GRPCException $e): string
    {
        $data = [$e->getCode(), $e->getMessage()];

        foreach ($e->getDetails() as $detail) {
            $anyMessage = new Any();

            $anyMessage->pack($detail);

            $data[] = $anyMessage->serializeToString();
        }

        return implode('|:|', $data);
    }

    private function isDebugMode(): bool
    {
        $debug = false;

        if (isset($this->options['debug'])) {
            $debug = filter_var($this->options['debug'], \FILTER_VALIDATE_BOOLEAN);
        }

        return $debug;
    }
}
