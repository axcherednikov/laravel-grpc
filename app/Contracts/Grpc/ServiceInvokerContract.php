<?php

declare(strict_types=1);

namespace App\Contracts\Grpc;

use Illuminate\Contracts\Foundation\Application;
use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\Method;

interface ServiceInvokerContract
{
    /**
     * Invoke service.
     *
     * @param  string            $interface
     * @param  Method            $method
     * @param  ContextInterface  $context
     * @param  string            $input
     *
     * @return  string
     */
    public function invoke(
        string $interface,
        Method $method,
        ContextInterface $context,
        ?string $input
    ): string;

    /**
     * Get the Laravel application instance.
     *
     * @return Application
     */
    public function getApplication(): Application;
}
