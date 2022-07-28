<?php

declare(strict_types=1);

namespace App\Contracts\Grpc;

use Illuminate\Contracts\Foundation\Application;
use Spiral\RoadRunner\GRPC\ServiceInterface;
use Spiral\RoadRunner\Worker;

interface GrpcKernelContract
{
    /**
     * Bootstrap the application for GRPC requests.
     *
     * @return void
     */
    public function bootstrap(): void;

    /**
     * Register available services.
     *
     * @param  string            $interface
     * @param  ServiceInterface  $service
     * @return self
     */
    public function registerService(string $interface, ServiceInterface $service): self;

    /**
     * Serve GRPC server.
     *
     * @param  callable|null $finalize
     * @param  Worker $worker
     * @return  void
     */
    public function serve(Worker $worker, callable $finalize = null): void;

    /**
     * Get the Laravel application instance.
     *
     * @return Application
     */
    public function getApplication(): Application;
}
