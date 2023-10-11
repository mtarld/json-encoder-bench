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
use Symfony\Component\Serializer\Encoder\JsonEncoder as SerializerJsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

use Symfony\Component\TypeInfo\Resolver\ChainTypeResolver;
use Symfony\Component\TypeInfo\Resolver\ReflectionParameterResolver;
use Symfony\Component\TypeInfo\Resolver\ReflectionPropertyResolver;
use Symfony\Component\TypeInfo\Resolver\ReflectionReturnResolver;
use Symfony\Component\TypeInfo\Resolver\ReflectionTypeResolver;

use Symfony\Component\Encoder\DataModel\Encode\DataModelBuilder as EncodeDataModelBuilder;
use Symfony\Component\Encoder\Mapping\Encode\AttributePropertyMetadataLoader as EncodeAttributePropertyMetadataLoader;
use Symfony\Component\Encoder\Mapping\Encode\DateTimeTypePropertyMetadataLoader as EncodeDateTimeTypePropertyMetadataLoader;
use Symfony\Component\Encoder\DataModel\Decode\DataModelBuilder as DecodeDataModelBuilder;
use Symfony\Component\Encoder\Mapping\Decode\AttributePropertyMetadataLoader as DecodeAttributePropertyMetadataLoader;
use Symfony\Component\Encoder\Mapping\Decode\DateTimeTypePropertyMetadataLoader as DecodeDateTimeTypePropertyMetadataLoader;
use Symfony\Component\Encoder\Mapping\GenericTypePropertyMetadataLoader;
use Symfony\Component\Encoder\Mapping\PropertyMetadataLoader;
use Symfony\Component\Json\Template\Encode\Template as EncodeTemplate;
use Symfony\Component\Json\Template\Decode\Template as DecodeTemplate;

use Symfony\Component\Json\JsonEncoder;
use Symfony\Component\Json\JsonStreamingEncoder;
use Symfony\Component\Json\JsonDecoder;
use Symfony\Component\Json\JsonStreamingDecoder;

use Symfony\Component\Encoder\EncoderInterface;
use Symfony\Component\Encoder\StreamingEncoderInterface;
use Symfony\Component\Encoder\DecoderInterface;
use Symfony\Component\Encoder\StreamingDecoderInterface;

use Symfony\Component\Encoder\Instantiator\EagerInstantiator;
use Symfony\Component\Encoder\Instantiator\LazyInstantiator;

final class SerializerFactory
{
    public static function encoder(): EncoderInterface
    {
        $cacheDir = sprintf('%s/symfony_json_template', sys_get_temp_dir());

        $typeResolver = new ChainTypeResolver([
            new ReflectionPropertyResolver(new ReflectionTypeResolver()),
            new ReflectionParameterResolver(new ReflectionTypeResolver()),
            new ReflectionReturnResolver(new ReflectionTypeResolver()),
            new ReflectionTypeResolver(),
        ]);

        $propertyMetadataLoader = new GenericTypePropertyMetadataLoader(
            new EncodeDateTimeTypePropertyMetadataLoader(new EncodeAttributePropertyMetadataLoader(
                new PropertyMetadataLoader($typeResolver),
                $typeResolver,
            )),
            $typeResolver,
        );

        $dataModeBuilder = new EncodeDataModelBuilder($propertyMetadataLoader);
        $template = new EncodeTemplate($dataModeBuilder, $cacheDir);

        return new JsonEncoder($template, $cacheDir);
    }

    public static function streamingEncoder(): StreamingEncoderInterface
    {
        $cacheDir = sprintf('%s/symfony_json_template', sys_get_temp_dir());

        $typeResolver = new ChainTypeResolver([
            new ReflectionPropertyResolver(new ReflectionTypeResolver()),
            new ReflectionParameterResolver(new ReflectionTypeResolver()),
            new ReflectionReturnResolver(new ReflectionTypeResolver()),
            new ReflectionTypeResolver(),
        ]);

        $propertyMetadataLoader = new GenericTypePropertyMetadataLoader(
            new EncodeDateTimeTypePropertyMetadataLoader(new EncodeAttributePropertyMetadataLoader(
                new PropertyMetadataLoader($typeResolver),
                $typeResolver,
            )),
            $typeResolver,
        );

        $dataModeBuilder = new EncodeDataModelBuilder($propertyMetadataLoader);
        $template = new EncodeTemplate($dataModeBuilder, $cacheDir);

        return new JsonStreamingEncoder($template, $cacheDir);
    }

    public static function decoder(): DecoderInterface
    {
        $templateCacheDir = sprintf('%s/symfony_json_template', sys_get_temp_dir());

        $typeResolver = new ChainTypeResolver([
            new ReflectionPropertyResolver(new ReflectionTypeResolver()),
            new ReflectionParameterResolver(new ReflectionTypeResolver()),
            new ReflectionReturnResolver(new ReflectionTypeResolver()),
            new ReflectionTypeResolver(),
        ]);

        $propertyMetadataLoader = new GenericTypePropertyMetadataLoader(
            new DecodeDateTimeTypePropertyMetadataLoader(new DecodeAttributePropertyMetadataLoader(
                new PropertyMetadataLoader($typeResolver),
                $typeResolver,
            )),
            $typeResolver,
        );

        $dataModeBuilder = new DecodeDataModelBuilder($propertyMetadataLoader);
        $template = new DecodeTemplate($dataModeBuilder, $templateCacheDir);

        return new JsonDecoder($template, new EagerInstantiator(), $templateCacheDir);
    }

    public static function streamingDecoder(): StreamingDecoderInterface
    {
        $templateCacheDir = sprintf('%s/symfony_json_template', sys_get_temp_dir());
        $lazyObjectCacheDir = sprintf('%s/symfony_encoder_lazy_ghost', sys_get_temp_dir());

        $typeResolver = new ChainTypeResolver([
            new ReflectionPropertyResolver(new ReflectionTypeResolver()),
            new ReflectionParameterResolver(new ReflectionTypeResolver()),
            new ReflectionReturnResolver(new ReflectionTypeResolver()),
            new ReflectionTypeResolver(),
        ]);

        $propertyMetadataLoader = new GenericTypePropertyMetadataLoader(
            new DecodeDateTimeTypePropertyMetadataLoader(new DecodeAttributePropertyMetadataLoader(
                new PropertyMetadataLoader($typeResolver),
                $typeResolver,
            )),
            $typeResolver,
        );

        $dataModeBuilder = new DecodeDataModelBuilder($propertyMetadataLoader);
        $template = new DecodeTemplate($dataModeBuilder, $templateCacheDir);

        return new JsonStreamingDecoder($template, new LazyInstantiator($lazyObjectCacheDir), $templateCacheDir);
    }

    public static function lightSerializer(): SerializerInterface
    {
        return new Serializer(
            [new ArrayDenormalizer(), new DateTimeNormalizer(), new ObjectNormalizer()],
            [new SerializerJsonEncoder()],
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
        ], [new SerializerJsonEncoder()]);
    }
}
