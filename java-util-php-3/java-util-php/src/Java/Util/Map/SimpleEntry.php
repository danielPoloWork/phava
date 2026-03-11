<?php

declare(strict_types=1);

namespace Java\Util\Map;

/**
 * Port of java.util.AbstractMap.SimpleEntry<K,V>
 *
 * A mutable Entry. Not connected to any backing map.
 *
 * Extends {@see Entry} (abstract class) rather than implementing an interface,
 * inheriting the static factory methods (comparingByKey, comparingByValue, copyOf).
 *
 * @template K
 * @template V
 * @extends Entry<K,V>
 */
class SimpleEntry extends Entry
{
    /**
     * @param K $key
     * @param V $value
     */
    public function __construct(
        private mixed $key,
        private mixed $value,
    ) {}

    /** @return K */
    public function getKey(): mixed
    {
        return $this->key;
    }

    /** @return V */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @param  V $value
     * @return V the previous value
     */
    public function setValue(mixed $value): mixed
    {
        $old         = $this->value;
        $this->value = $value;
        return $old;
    }

    public function equals(mixed $o): bool
    {
        if (!$o instanceof Entry) {
            return false;
        }
        return self::nullSafeEquals($this->key, $o->getKey())
            && self::nullSafeEquals($this->value, $o->getValue());
    }

    public function hashCode(): int
    {
        // Java contract: hash(key) XOR hash(value)
        return self::valueHash($this->key) ^ self::valueHash($this->value);
    }

    public function __toString(): string
    {
        return $this->key . '=' . $this->value;
    }
}

/**
 * Port of java.util.AbstractMap.SimpleImmutableEntry<K,V>
 *
 * An unmodifiable Entry. PHP 8.1 `readonly` enforces immutability at language level.
 * setValue() always throws \RuntimeException (UnsupportedOperationException).
 *
 * @template K
 * @template V
 * @extends Entry<K,V>
 */
final class SimpleImmutableEntry extends Entry
{
    /**
     * @param K $key
     * @param V $value
     * @throws \InvalidArgumentException if key or value is null (mirrors Map.entry() contract)
     */
    public function __construct(
        private readonly mixed $key,
        private readonly mixed $value,
    ) {
        if ($this->key === null || $this->value === null) {
            throw new \InvalidArgumentException(
                'SimpleImmutableEntry does not permit null keys or values.'
            );
        }
    }

    /** @return K */
    public function getKey(): mixed
    {
        return $this->key;
    }

    /** @return V */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /** @throws \RuntimeException always */
    public function setValue(mixed $value): mixed
    {
        throw new \RuntimeException(
            'setValue() is not supported on an immutable entry (UnsupportedOperationException).'
        );
    }

    public function equals(mixed $o): bool
    {
        if (!$o instanceof Entry) {
            return false;
        }
        return self::nullSafeEquals($this->key, $o->getKey())
            && self::nullSafeEquals($this->value, $o->getValue());
    }

    public function hashCode(): int
    {
        return self::valueHash($this->key) ^ self::valueHash($this->value);
    }

    public function __toString(): string
    {
        return $this->key . '=' . $this->value;
    }
}
