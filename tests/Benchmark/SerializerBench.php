<?php

declare(strict_types=1);

namespace App\Tests\Benchmark;

use App\DataBuilder;
use App\SerializerFactory;
use PhpBench\Attributes as Bench;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Encoder\EncoderInterface;
use Symfony\Component\Encoder\StreamingEncoderInterface;

final class SerializerBench
{
    private SerializerInterface $lightSerializer;
    private SerializerInterface $heavySerializer;
    private EncoderInterface $encoder;
    private StreamingEncoderInterface $streamingEncoder;

    public function setUp(): void
    {
        $this->lightSerializer = SerializerFactory::lightSerializer();
        $this->heavySerializer = SerializerFactory::heavySerializer();
        $this->encoder = SerializerFactory::encoder();
        $this->streamingEncoder = SerializerFactory::streamingEncoder();

        DataBuilder::build();

        // warm up templates
        $this->encoder->encode(DataBuilder::$data);
        $this->streamingEncoder->encode(DataBuilder::$data);
    }

    /**
     * @return Generator<string, array{serializer: string}>
     */
    public function provideSerializer(): \Generator
    {
        yield 'json_encode' => ['serializer' => 'json_encode'];
        yield 'Json encoder' => ['serializer' => 'encoder'];
        yield 'Json streaming encoder' => ['serializer' => 'streaming_encoder'];
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

        $output = match ($serializer) {
            'json_encode' => json_encode($data),
            'encoder' => $this->encoder->encode($data),
            'streaming_encoder' => $this->streamingEncoder->encode($data),
            'light_serializer' => $this->lightSerializer->serialize($data, 'json', ['datetime_format' => 'Y-m-d']),
            'heavy_serializer' => $this->heavySerializer->serialize($data, 'json', ['datetime_format' => 'Y-m-d']),
            default => throw new \InvalidArgumentException(sprintf('Unknown "%s" serializer', $serializer)),
        };

        (string) $output;
    }
}
