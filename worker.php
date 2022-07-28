<?php

declare(strict_types=1);

use App\Contracts\Grpc\GrpcKernelContract;
use App\Contracts\Grpc\ServiceInvokerContract;
use App\Grpc\GrpcKernel;
use App\Grpc\LaravelServiceInvoker;
use App\Grpc\Services\EchoService;
use Grpc\Service\Test\TestInterface;
use Illuminate\Foundation\Application;

ini_set('display_errors', 'stderr');

require __DIR__ . '/vendor/autoload.php';

/**
 * @var Application $app
 */
$app = require_once __DIR__ . '/bootstrap/app.php';

$app->singleton(GrpcKernelContract::class, GrpcKernel::class);
$app->singleton(ServiceInvokerContract::class, LaravelServiceInvoker::class);
$app->singleton(TestInterface::class, EchoService::class);

$kernel = $app->make(GrpcKernel::class);

$kernel->registerService(TestInterface::class, new EchoService());

$kernel->serve();
