<?php

declare(strict_types=1);

namespace App\Contracts\Grpc;

use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\Exception\InvokeException;
use Spiral\RoadRunner\GRPC\Exception\NotFoundException;

interface ServiceWrapperContract
{
    /**
     * Retrieve service name.
     *
     * @return  string
     */
    public function getName(): string;

    /**
     * Retrieve public methods.
     *
     * @return  array
     */
    public function getMethods(): array;

    /**
     * Invoke service.
     *
     * @param  string $method
     * @param  ContextInterface $context
     * @param  string|null $input
     * @return string
     *
     * @throws NotFoundException
     * @throws InvokeException
     */
    public function invoke(string $method, ContextInterface $context, ?string $input): string;
}
