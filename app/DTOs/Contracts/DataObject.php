<?php

namespace App\DTOs\Contracts;

interface DataObject
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
