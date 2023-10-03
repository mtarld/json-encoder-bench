<?php

declare(strict_types=1);

namespace App\Tests\Benchmark;

use App\DataBuilder;
use App\SerializerFactory;
use PhpBench\Attributes as Bench;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\JsonMarshaller\MarshallerInterface;

final class SerializerBench
{
    private SerializerInterface $lightSerializer;
    private SerializerInterface $heavySerializer;
    private MarshallerInterface $marshaller;

    public function setUp(): void
    {
        $this->lightSerializer = SerializerFactory::lightSerializer();
        $this->heavySerializer = SerializerFactory::heavySerializer();
        $this->marshaller = SerializerFactory::marshaller();

        DataBuilder::build();

        // warm up templates
        $this->marshaller->marshal(DataBuilder::$data);
        $this->marshaller->marshal(DataBuilder::$data, output: fopen('php://memory', 'w+'));
    }

    /**
     * @return Generator<string, array{serializer: string}>
     */
    public function provideSerializer(): \Generator
    {
        yield 'json_encode' => ['serializer' => 'json_encode'];
        yield 'Marshaller (eager)' => ['serializer' => 'eager_marshaller'];
        yield 'Marshaller (lazy)' => ['serializer' => 'lazy_marshaller'];
        yield 'Serializer (light)' => ['serializer' => 'light_serializer'];
        yield 'Serializer (heavy)' => ['serializer' => 'heavy_serializer'];
    }

    /**
     * @param array{serializer: string} $params
     */
    #[Bench\BeforeMethods('setUp')]
    #[Bench\ParamProviders(['provideSerializer'])]
    #[Bench\Iterations(10)]
    public function bench($params): void
    {
        $serializer = $params['serializer'];
        $data = DataBuilder::$data;

        match ($serializer) {
            'json_encode' => json_encode($data),
            'eager_marshaller' => $this->marshaller->marshal($data),
            'lazy_marshaller' => $this->marshaller->marshal($data, output: fopen('php://memory', 'w+')),
            'light_serializer' => $this->lightSerializer->serialize($data, 'json', ['datetime_format' => 'Y-m-d']),
            'heavy_serializer' => $this->heavySerializer->serialize($data, 'json', ['datetime_format' => 'Y-m-d']),
            default => throw new \InvalidArgumentException(sprintf('Unknown "%s" serializer', $serializer)),
        };
    }
}
