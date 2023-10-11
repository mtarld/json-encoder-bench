<?php

declare(strict_types=1);

namespace App\Tests\Benchmark;

use App\Dto\Element;
use App\Dto\Relation;
use App\SerializerFactory;
use PhpBench\Attributes as Bench;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Encoder\DecoderInterface;
use Symfony\Component\Encoder\StreamingDecoderInterface;
use Symfony\Component\Encoder\Stream\MemoryStream;
use Symfony\Component\Encoder\Stream\Stream;
use Symfony\Component\TypeInfo\Type;

final class DeserializerBench
{
    private SerializerInterface $lightSerializer;
    private SerializerInterface $heavySerializer;
    private DecoderInterface $decoder;
    private StreamingDecoderInterface $streamingDecoder;

    public function setUp(): void
    {
        $this->lightSerializer = SerializerFactory::lightSerializer();
        $this->heavySerializer = SerializerFactory::heavySerializer();
        $this->decoder = SerializerFactory::decoder();
        $this->streamingDecoder = SerializerFactory::streamingDecoder();

        // warm up templates
        $this->decoder->decode('[]', Type::list(Type::object(Element::class)));
        $this->streamingDecoder->decode(new MemoryStream('[]'), Type::iterableList(Type::object(Element::class)));
    }


    /**
     * @return \Generator<string, array{serializer: string}>
     */
    public function provideSerializer(): \Generator
    {
        yield 'json_decode' => ['serializer' => 'json_decode'];
        yield 'Json decoder' => ['serializer' => 'decoder'];
        yield 'Json streaming decoder' => ['serializer' => 'streaming_decoder'];
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

        if ('light_serializer' === $serializer) {
            $elements = $this->lightSerializer->deserialize(file_get_contents('deserialize.json'), Element::class.'[]', 'json');
            $this->read($elements);

            return;
        }

        if ('heavy_serializer' === $serializer) {
            $elements = $this->heavySerializer->deserialize(file_get_contents('deserialize.json'), Element::class.'[]', 'json');
            $this->read($elements);

            return;
        }

        if ('json_decode' === $serializer) {
            $elements = [];

            foreach (json_decode(file_get_contents('deserialize.json'), true) as $e) {
                $relation = new Relation();
                $relation->id = $e['relation']['id'];
                $relation->value = $e['relation']['value'];
                $relation->createdAt = new \DateTimeImmutable($e['relation']['createdAt']);

                $element = new Element();
                $element->id = $e['id'];
                $element->price = $e['price'];
                $element->relation = $relation;

                $elements[] = $element;
            }

            $this->read($elements);

            return;
        }

        if ('decoder' === $serializer) {
            $elements = $this->decoder->decode(file_get_contents('deserialize.json'), Type::list(Type::object(Element::class)));
            $this->read($elements);

            return;
        }

        if ('streaming_decoder' === $serializer) {
            $elements = $this->streamingDecoder->decode(new Stream('deserialize.json', 'r+'), Type::iterableList(Type::object(Element::class)));
            $this->read($elements);

            return;
        }

        throw new \InvalidArgumentException(sprintf('Unknown "%s" serializer', $serializer));
    }

    /**
     * @param iterable<Element> $elements
     */
    private function read(iterable $elements): void
    {
        foreach ($elements as $i => $c) {
            if ($i === 10000) {
                $c->id;
                break;
            }
        }
    }
}
