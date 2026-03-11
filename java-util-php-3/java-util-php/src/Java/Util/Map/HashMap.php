<?php

declare(strict_types=1);

namespace Java\Util\Map;

use Java\Lang\Type;
use Java\Util\Generics\TypeRef;
use Java\Util\Generics\UnionTypeRef;

/**
 * Port of java.util.HashMap<K,V>
 *
 * Hash table based implementation of the Map interface.
 * This implementation provides O(1) amortized performance for get and put,
 * backed by PHP's native associative array.
 *
 * ⚠️  PHP PORTING NOTES:
 *  - PHP arrays use string/int keys natively. Non-scalar keys are serialized to
 *    a string representation for internal storage. This means object identity is
 *    NOT used for key lookup (unlike Java's HashMap which uses hashCode+equals).
 *    Use scalar keys or ensure objects serialize deterministically.
 *  - Null keys are supported (stored under the internal key '__null__').
 *  - Iteration order reflects insertion order (PHP array behavior).
 *    Java's HashMap makes NO iteration order guarantees — this is safe.
 *  - No concurrent-modification detection.
 *  - No load factor / rehashing — PHP handles this internally.
 *  - initialCapacity and loadFactor constructor parameters are accepted but ignored
 *    (no-op) since PHP manages array memory automatically.
 *
 * @template K
 * @template V
 * @extends TypedMap<K,V>
 */
abstract class HashMap extends TypedMap {
    /** @var array<string, mixed> internal key→value storage */
    private array $data = [];

    /**
     * Maps internal string keys back to the original K key objects.
     * Required to reconstruct Entry objects with original keys.
     *
     * @var array<string, mixed>
     */
    private array $keyIndex = [];

    private const NULL_KEY = "\x00__null__\x00";

    /**
     * @param Type|TypeRef|UnionTypeRef|string $keyType   tipo delle chiavi
     * @param Type|TypeRef|UnionTypeRef|string $valueType tipo dei valori
     * @param Map<K,V>|array<K,V>|null         $source    entries iniziali
     *
     * Esempi:
     *   new HashMap()
     *   new HashMap(Type::String, Type::Int)
     *   new HashMap(UserId::class, UserProfile::class)         ← forma diretta
     *   new HashMap(Type::of(UserId::class), Type::Mixed)
     *   new HashMap(Type::union(Type::Int, Type::String), Type::Mixed)
     */
    public function __construct(
        Type|TypeRef|UnionTypeRef|string $keyType   = Type::Mixed,
        Type|TypeRef|UnionTypeRef|string $valueType = Type::Mixed,
        Map|array|null                   $source    = null,
    ) {
        parent::__construct($keyType, $valueType);

        if ($source instanceof Map) {
            $this->putAll($source);
        } elseif (is_array($source)) {
            foreach ($source as $k => $v) {
                $this->put($k, $v);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Core operations
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
        $this->assertKeyType($key);
        return array_key_exists($this->internalKey($key), $this->data);
    }

    public function containsValue(mixed $value): bool
    {
        $this->assertValueType($value);
        return in_array($value, $this->data, strict: true);
    }

    public function get(mixed $key): mixed
    {
        $this->assertKeyType($key);
        $ik = $this->internalKey($key);
        return $this->data[$ik] ?? null;
    }

    public function put(mixed $key, mixed $value): mixed
    {
        $this->assertKeyType($key);
        $this->assertValueType($value);

        $ik  = $this->internalKey($key);
        $old = $this->data[$ik] ?? null;

        $this->data[$ik]     = $value;
        $this->keyIndex[$ik] = $key;

        return $old;
    }

    public function remove(mixed $key): mixed
    {
        $this->assertKeyType($key);
        $ik = $this->internalKey($key);

        if (!array_key_exists($ik, $this->data)) {
            return null;
        }

        $old = $this->data[$ik];
        unset($this->data[$ik], $this->keyIndex[$ik]);

        return $old;
    }

    public function putAll(Map $m): void
    {
        foreach ($m->entrySet() as $entry) {
            $this->put($entry->getKey(), $entry->getValue());
        }
    }

    public function clear(): void
    {
        $this->data     = [];
        $this->keyIndex = [];
    }

    // -------------------------------------------------------------------------
    // Views
    // -------------------------------------------------------------------------

    public function keySet(): array
    {
        return array_values($this->keyIndex);
    }

    public function values(): array
    {
        return array_values($this->data);
    }

    /**
     * Returns an array of SimpleEntry objects.
     * Each entry is a snapshot — mutating the entry does NOT affect the map.
     *
     * ⚠️  Java API difference: Java's entrySet() returns a live backed Set.
     *     Here entries are detached snapshots. Use put() to propagate changes.
     *
     * @return SimpleEntry
     */
    public function entrySet(): array
    {
        $entries = [];
        foreach ($this->data as $ik => $value) {
            $entries[] = new SimpleEntry($this->keyIndex[$ik], $value);
        }
        return $entries;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Converts an arbitrary PHP key into a string suitable for array indexing.
     *
     * ⚠️  Object key semantics: PHP cannot use objects as array keys.
     *     We serialize the object to derive a stable string key.
     *     This means two EQUAL (==) objects will share the same internal key,
     *     but two identical references are also equal — matching Java's equals()-based lookup.
     *     However, mutable objects used as keys whose state changes mid-lifecycle
     *     will break key lookup, mirroring Java's documented caveat.
     */
    private function internalKey(mixed $key): string
    {
        if ($key === null) {
            return self::NULL_KEY;
        }

        if (is_int($key) || is_float($key)) {
            return 'n:' . $key;
        }

        if (is_string($key)) {
            return 's:' . $key;
        }

        if (is_bool($key)) {
            return 'b:' . (int) $key;
        }

        // For arrays and objects: use serialized representation
        // This is semantically closest to Java's hashCode()+equals() lookup
        return 'o:' . md5(serialize($key));
    }
}
