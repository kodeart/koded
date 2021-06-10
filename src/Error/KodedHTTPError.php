<?php declare(strict_types=1);

namespace Koded\Framework\Error;

interface KodedHTTPError
{
    public function getStatusCode(): int;

    public function getTitle(): string;

    public function getType(): string;

    public function getDetail(): string;

    public function getInstance(): string;

    public function getHeaders(): iterable;

    public function setMember(string $name, mixed $value): static;

    public function toJson(): string;

    public function toXml(): string;

    public function toArray(): array;
}
