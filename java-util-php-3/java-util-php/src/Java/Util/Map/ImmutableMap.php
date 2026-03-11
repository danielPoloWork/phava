<?php

declare(strict_types=1);

namespace Java\Util\Map;

/**
 * Port of the unmodifiable map instances returned by java.util.Map.of() / Map.copyOf().
 *
 * Characteristics (identical to Java spec):
 *  - Disallows null keys and values.
 *  - Rejects duplicate keys at construction time (\InvalidArgumentException).
 *  - All mutating operations throw \RuntimeException (UnsupportedOperationException).
 *  - Iteration order is unspecified.
 *
 * This class is not meant to be instantiated directly.
 * Use the {@see Maps} factory class instead.
 *
 * @template K
 * @template V
 * @extends AbstractMap<K,V>
 *
 * @internal
 */
final class ImmutableMap extends AbstractMap
{
    /** @var array<string, mixed> */
    private readonly array $data;

    /** @var array<string, mixed> */
    private readonly array $keyIndex;

    /**
     * @param array<array{0: mixed, 1: mixed}> $pairs array of [key, value] tuples
     * @throws \InvalidArgumentException on null key/value or duplicate key
     */
    public function __construct(array $pairs)
    {
        $data     = [];
        $keyIndex = [];

        foreach ($pairs as [$k, $v]) {
            if ($k === null) {
                throw new \InvalidArgumentException(
                    'ImmutableMap does not permit null keys.'
                );
            }
            if ($v === null) {
                throw new \InvalidArgumentException(
                    'ImmutableMap does not permit null values.'
                );
            }

            $ik = $this->deriveKey($k);

            if (array_key_exists($ik, $data)) {
                throw new \InvalidArgumentException(
                    sprintf('Duplicate key: %s', is_scalar($k) ? $k : serialize($k))
                );
            }

            $data[$ik]     = $v;
            $keyIndex[$ik] = $k;
        }

        $this->data     = $data;
        $this->keyIndex = $keyIndex;
    }

    // -------------------------------------------------------------------------
    // Core read operations
    // -------------------------------------------------------------------------

    public function size(): int
    {
        return count($this->data);
    }

    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    public function containsKey(mixed $key): bool
    {
        if ($key === null) {
            return false; // null keys not permitted
        }
        return array_key_exists($this->deriveKey($key), $this->data);
    }

    public function containsValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }
        return in_array($value, $this->data, strict: true);
    }

    public function get(mixed $key): mixed
    {
        if ($key === null) {
            return null;
        }
        return $this->data[$this->deriveKey($key)] ?? null;
    }

    public function keySet(): array
    {
        return array_values($this->keyIndex);
    }

    public function values(): array
    {
        return array_values($this->data);
    }

    /**
     * @return SimpleImmutableEntry
     */
    public function entrySet(): array
    {
        $entries = [];
        foreach ($this->data as $ik => $value) {
            $entries[] = new SimpleImmutableEntry($this->keyIndex[$ik], $value);
        }
        return $entries;
    }

    // -------------------------------------------------------------------------
    // Mutating operations — all prohibited
    // -------------------------------------------------------------------------

    public function put(mixed $key, mixed $value): mixed
    {
        $this->throwUnsupported('put');
    }

    public function remove(mixed $key): mixed
    {
        $this->throwUnsupported('remove');
    }

    public function putAll(Map $m): void
    {
        $this->throwUnsupported('putAll');
    }

    public function clear(): void
    {
        $this->throwUnsupported('clear');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function deriveKey(mixed $key): string
    {
        if (is_int($key) || is_float($key)) return 'n:' . $key;
        if (is_string($key))                 return 's:' . $key;
        if (is_bool($key))                   return 'b:' . (int) $key;
        return 'o:' . md5(serialize($key));
    }

    /** @return never */
    private function throwUnsupported(string $method): mixed
    {
        throw new \RuntimeException(
            sprintf('%s() is not supported on an unmodifiable map.', $method)
        );
    }
}
