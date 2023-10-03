<?php

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\SerializeFormatter;
use Symfony\Component\Serializer\Attribute\DeserializeFormatter;

class Relation
{
    public ?int $id;
    public ?\DateTimeImmutable $createdAt;

    #[SerializeFormatter('strtolower')]
    #[DeserializeFormatter('strtoupper')]
    public ?string $value;
}
