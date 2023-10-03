<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Mapping\Factory\CacheClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\JsonMarshaller\JsonUnmarshaller;
use Symfony\Component\JsonMarshaller\UnmarshallerInterface;
use Symfony\Component\JsonMarshaller\Unmarshal\DataModel\DataModelBuilder as UnmarshalDataModelBuilder ;
use Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\EagerInstantiator;
use Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\LazyInstantiator;
use Symfony\Component\JsonMarshaller\Unmarshal\Mapping\PropertyMetadataLoader as UnmarshalPropertyMetadataLoader;
use Symfony\Component\JsonMarshaller\Unmarshal\Template\Template as UnmarshalTemplate;
use Symfony\Component\JsonMarshaller\Type\PhpstanTypeExtractor;
use Symfony\Component\JsonMarshaller\Type\ReflectionTypeExtractor;
use Symfony\Component\JsonMarshaller\JsonMarshaller;
use Symfony\Component\JsonMarshaller\MarshallerInterface;
use Symfony\Component\JsonMarshaller\Marshal\DataModel\DataModelBuilder as MarshalDataModelBuilder;
use Symfony\Component\JsonMarshaller\Marshal\Mapping\PropertyMetadataLoader as MarshalPropertyMetadataLoader;
use Symfony\Component\JsonMarshaller\Marshal\Template\Template as MarshalTemplate;

final class SerializerFactory
{
    public static function marshaller(): MarshallerInterface
    {
        $cacheDir = sprintf('%s/symfony_json_marshaller_template', sys_get_temp_dir());

        return new JsonMarshaller(
            new MarshalTemplate(
                new MarshalDataModelBuilder(new MarshalPropertyMetadataLoader(new PhpstanTypeExtractor(new ReflectionTypeExtractor()))),
                $cacheDir,
            ),
            $cacheDir,
        );
    }

    public static function eagerUnmarshaller(): UnmarshallerInterface
    {
        $cacheDir = sprintf('%s/symfony_json_marshaller_template', sys_get_temp_dir());

        return new JsonUnmarshaller(
            new UnmarshalTemplate(
                new UnmarshalDataModelBuilder(new UnmarshalPropertyMetadataLoader(new PhpstanTypeExtractor(new ReflectionTypeExtractor()))),
                $cacheDir,
            ),
            new EagerInstantiator(),
            $cacheDir,
            lazy: false,
        );
    }

    public static function lazyUnmarshaller(): UnmarshallerInterface
    {
        $cacheDir = sprintf('%s/symfony_json_marshaller_template', sys_get_temp_dir());

        return new JsonUnmarshaller(
            new UnmarshalTemplate(
                new UnmarshalDataModelBuilder(new UnmarshalPropertyMetadataLoader(new PhpstanTypeExtractor(new ReflectionTypeExtractor()))),
                $cacheDir,
            ),
            new LazyInstantiator(sprintf('%s/symfony_json_marshaller_lazy_object', sys_get_temp_dir())),
            $cacheDir,
            lazy: true,
        );
    }

    public static function lightSerializer(): SerializerInterface
    {
        return new Serializer(
            [new ArrayDenormalizer(), new DateTimeNormalizer(), new ObjectNormalizer()],
            [new JsonEncoder()],
        );
    }

    public static function heavySerializer(): SerializerInterface
    {
        $classMetadataFactory = new CacheClassMetadataFactory(new ClassMetadataFactory(new AnnotationLoader()), new ArrayAdapter());

        return new Serializer([
            new ArrayDenormalizer(),
            new DateTimeNormalizer(),
            new ObjectNormalizer(
                $classMetadataFactory,
                new MetadataAwareNameConverter($classMetadataFactory),
                new PropertyAccessor(),
                new PropertyInfoExtractor([], [new PhpDocExtractor(), new ReflectionExtractor()]),
                new ClassDiscriminatorFromClassMetadata($classMetadataFactory),
            ),
        ], [new JsonEncoder()]);
    }
}
