<?php

declare(strict_types=1);

namespace App\Grpc\Services;

use Grpc\Service\Test\Request;
use Grpc\Service\Test\Response;
use Grpc\Service\Test\TestInterface;
use Spiral\RoadRunner\GRPC\ContextInterface;

class EchoService implements TestInterface
{
    public function Ping(ContextInterface $ctx, Request $in): Response
    {
        $out = new Response();

        return $out->setTest(date('Y-m-d H:i:s') . ': PONG');
    }
}
