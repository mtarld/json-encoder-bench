<?php

namespace App;

use App\Dto\Element;
use App\Dto\Relation;

class DataBuilder
{
    public static array $data;
    public static int $num = 10000;

    public static function build(): void
    {
        $relations = [];
        $now = new \DateTimeImmutable();

        for ($i = 0; $i < self::$num / 10; $i++) {
            $relation = new Relation();
            $relation->id = $i;
            $relation->value = bin2hex(random_bytes(10));
            $relation->createdAt = $now;

            $relations[] = $relation;
        }

        $elements = [];
        for ($i = 0; $i < self::$num; $i++) {
            $element = new Element();
            $element->id = $i;
            $element->price = (float) sprintf('%s.%s', random_int(1, 100), random_int(1, 10));
            $element->relation = $relations[array_rand($relations)];

            $elements[] = $element;
        }

        static::$data = $elements;
    }
}
