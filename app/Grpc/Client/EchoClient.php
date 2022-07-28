<?php

declare(strict_types=1);

namespace App\Grpc\Client;

use App\Grpc\Gen\Service\Request;
use Grpc\BaseStub;
use Grpc\UnaryCall;

class EchoClient extends BaseStub
{
    public function Ping(Request $message, $metadata = [], $options = []): UnaryCall
    {
        return $this->_simpleRequest(
            '/service.Echo/Ping',
            $message,
            ['\Service\Message', 'decode'],
            $metadata,
            $options
        );
    }
}
