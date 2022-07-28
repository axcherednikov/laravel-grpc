<?php

declare(strict_types=1);

namespace App\Grpc\Services;

use App\Grpc\Gen\Service\EchoInterface;
use App\Grpc\Gen\Service\Request;
use App\Grpc\Gen\Service\Response;
use Spiral\RoadRunner\GRPC\ContextInterface;

class EchoService implements EchoInterface
{
    public function Ping(ContextInterface $ctx, Request $in): Response
    {
        $out = new Response();

        return $out->setTest(date('Y-m-d H:i:s') . ': PONG');
    }
}
