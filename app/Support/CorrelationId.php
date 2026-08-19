<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;
use Stringable;

final class CorrelationId implements Stringable
{
    public const HEADER = 'X-Request-Id';

    public const CONTEXT_KEY = 'correlation_id';

    private static ?string $current = null;

    private function __construct(
        private readonly string $value,
    ) {}

    public function __toString(): string
    {
        return $this->value;
    }

    public static function fromHeaderOrGenerate(?string $headerValue = null): self
    {
        if ($headerValue !== null && trim($headerValue) !== '') {
            $value = substr(preg_replace('/[^a-zA-Z0-9\-_]/', '', $headerValue) ?? '', 0, 255);

            if ($value !== '') {
                return new self($value);
            }
        }

        return self::generate();
    }

    public static function generate(): self
    {
        return new self((string) Str::uuid());
    }

    public static function setCurrent(self|string|null $id): void
    {
        self::$current = $id instanceof self ? $id->value : ($id !== null && trim($id) !== '' ? (string) $id : null);
    }

    public static function getCurrent(): ?string
    {
        return self::$current;
    }

    public static function getCurrentOrDefault(): string
    {
        if (self::$current !== null) {
            return self::$current;
        }

        $value = self::generate()->value;
        self::setCurrent($value);

        return $value;
    }

    public function asContext(): array
    {
        return [self::CONTEXT_KEY => $this->value];
    }

    public function toString(): string
    {
        return $this->value;
    }
}
