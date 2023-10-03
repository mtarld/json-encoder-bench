<?php

namespace App\Dto;

#[Marshallable]
class Element
{
    public ?int $id = null;
    public ?float $price = null;
    /** @var Relation|null */
    public mixed $relation = null;
}
