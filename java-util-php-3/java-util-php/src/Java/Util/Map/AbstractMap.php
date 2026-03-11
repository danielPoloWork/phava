<?php

declare(strict_types=1);

namespace Java\Util\Map;

/**
 * Port of java.util.AbstractMap<K,V>
 *
 * Skeletal implementation of the Map interface.
 * Provides default implementations for all non-abstract methods using the
 * {@see MapDefaults} trait.
 *
 * To implement an unmodifiable map, the programmer needs only to extend this
 * class and provide an implementation for the entrySet() method.
 *
 * To implement a modifiable map, the programmer must additionally override
 * the put() method (which otherwise throws \RuntimeException) and the
 * iterator returned by entrySet() must additionally implement setValue().
 *
 * @template K
 * @template V
 * @implements Map<K,V>
 */
abstract class AbstractMap implements Map
{
    use MapDefaults;

    public function size(): int
    {
        return count($this->entrySet());
    }

    public function isEmpty(): bool
    {
        return $this->size() === 0;
    }

    public function containsValue(mixed $value): bool
    {
        foreach ($this->entrySet() as $entry) {
            if ($value === null ? $entry->getValue() === null : $value == $entry->getValue()) {
                return true;
            }
        }
        return false;
    }

    public function containsKey(mixed $key): bool
    {
        foreach ($this->entrySet() as $entry) {
            if ($key === null ? $entry->getKey() === null : $key == $entry->getKey()) {
                return true;
            }
        }
        return false;
    }

    public function get(mixed $key): mixed
    {
        foreach ($this->entrySet() as $entry) {
            if ($key === null ? $entry->getKey() === null : $key == $entry->getKey()) {
                return $entry->getValue();
            }
        }
        return null;
    }

    /**
     * @throws \RuntimeException always — override in mutable subclasses
     */
    public function put(mixed $key, mixed $value): mixed
    {
        throw new \RuntimeException(
            'put() is not supported by this map (UnsupportedOperationException).'
        );
    }

    /**
     * @throws \RuntimeException always — override in mutable subclasses
     */
    public function remove(mixed $key): mixed
    {
        throw new \RuntimeException(
            'remove() is not supported by this map (UnsupportedOperationException).'
        );
    }

    public function putAll(Map $m): void
    {
        foreach ($m->entrySet() as $entry) {
            $this->put($entry->getKey(), $entry->getValue());
        }
    }

    public function clear(): void
    {
        foreach ($this->keySet() as $key) {
            $this->remove($key);
        }
    }

    public function keySet(): array
    {
        return array_map(
            static fn (Entry $e): mixed => $e->getKey(),
            $this->entrySet()
        );
    }

    public function values(): array
    {
        return array_map(
            static fn (Entry $e): mixed => $e->getValue(),
            $this->entrySet()
        );
    }

    public function equals(mixed $o): bool
    {
        if ($o === $this) {
            return true;
        }
        if (!$o instanceof Map) {
            return false;
        }
        if ($o->size() !== $this->size()) {
            return false;
        }

        foreach ($this->entrySet() as $entry) {
            $key   = $entry->getKey();
            $value = $entry->getValue();

            if ($value === null) {
                if ($o->get($key) !== null || !$o->containsKey($key)) {
                    return false;
                }
            } else {
                if ($value != $o->get($key)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function hashCode(): int
    {
        $h = 0;
        foreach ($this->entrySet() as $entry) {
            $h += $entry->hashCode();
        }
        return $h;
    }

    public function __toString(): string
    {
        if ($this->isEmpty()) {
            return '{}';
        }

        $parts = [];
        foreach ($this->entrySet() as $entry) {
            $key   = $entry->getKey();
            $value = $entry->getValue();
            $parts[] = ($key === $this ? '(this Map)' : $key)
                . '='
                . ($value === $this ? '(this Map)' : $value);
        }

        return '{' . implode(', ', $parts) . '}';
    }
}
