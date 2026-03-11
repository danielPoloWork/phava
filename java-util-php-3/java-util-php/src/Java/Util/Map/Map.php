<?php

declare(strict_types=1);

namespace Java\Util\Map;

// abstract class (not interface) — PHP limitation workaround

/**
 * Port of java.util.Map<K,V>
 *
 * An object that maps keys to values. A map cannot contain duplicate keys;
 * each key can map to at most one value.
 *
 * ⚠️  PHP PORTING NOTES:
 *  - Generics <K,V> are not available in PHP; use @template PHPDoc for IDE/static-analysis.
 *  - Java overloaded remove(key) / remove(key,value) split into remove() / removeEntry().
 *  - Java overloaded replace(key,value) / replace(key,old,new) split into replace() / replaceEntry().
 *  - keySet() / values() / entrySet() return arrays instead of live-backed Set/Collection views.
 *    Mutating the returned array does NOT affect the map (no backed-view semantics).
 *  - No thread-safety / ConcurrentModificationException detection.
 *  - Static factory methods (of, ofEntries, entry, copyOf) live in {@see Maps} utility class.
 *  - equals() / hashCode() are idiomatic PHP but do NOT participate in PHP's == / === operators.
 *
 * @template K
 * @template V
 *
 * @see \Java\Util\Map\AbstractMap
 * @see \Java\Util\Map\HashMap
 * @see \Java\Util\Map\Maps
 */
interface Map {
    public const Entry = \Java\Util\Map\ProxyEntry::class;
    // -------------------------------------------------------------------------
    // Query Operations
    // -------------------------------------------------------------------------

    /**
     * Returns the number of key-value mappings in this map.
     *
     * @return int<0, max>
     */
    public function size(): int;

    /**
     * Returns true if this map contains no key-value mappings.
     */
    public function isEmpty(): bool;

    /**
     * Returns true if this map contains a mapping for the specified key.
     *
     * @param mixed $key key whose presence in this map is to be tested
     */
    public function containsKey(mixed $key): bool;

    /**
     * Returns true if this map maps one or more keys to the specified value.
     * Linear time O(n) for most implementations.
     *
     * @param mixed $value value whose presence in this map is to be tested
     */
    public function containsValue(mixed $value): bool;

    /**
     * Returns the value to which the specified key is mapped,
     * or null if this map contains no mapping for the key.
     *
     * @param mixed $key the key whose associated value is to be returned
     * @return V|null
     */
    public function get(mixed $key): mixed;

    // -------------------------------------------------------------------------
    // Modification Operations
    // -------------------------------------------------------------------------

    /**
     * Associates the specified value with the specified key in this map.
     * If the map previously contained a mapping for the key, the old value is replaced.
     *
     * @param K $key
     * @param V $value
     * @return V|null the previous value, or null if there was no mapping
     * @throws \RuntimeException if this map does not support put
     */
    public function put(mixed $key, mixed $value): mixed;

    /**
     * Removes the mapping for a key from this map if it is present.
     *
     * ⚠️  Java API difference: Java's remove(key, value) overload is exposed
     *     as {@see removeEntry()} in this PHP port.
     *
     * @param mixed $key key whose mapping is to be removed
     * @return V|null the previous value, or null if there was no mapping
     * @throws \RuntimeException if this map does not support remove
     */
    public function remove(mixed $key): mixed;

    // -------------------------------------------------------------------------
    // Bulk Operations
    // -------------------------------------------------------------------------

    /**
     * Copies all of the mappings from the specified map to this map.
     *
     * @param Map<K,V> $m mappings to be stored in this map
     * @throws \RuntimeException if this map does not support putAll
     */
    public function putAll(Map $m): void;

    /**
     * Removes all of the mappings from this map.
     *
     * @throws \RuntimeException if this map does not support clear
     */
    public function clear(): void;

    // -------------------------------------------------------------------------
    // Views
    // -------------------------------------------------------------------------

    /**
     * Returns an array of the keys contained in this map.
     *
     * ⚠️  Java API difference: Java returns a live Set<K> backed by the map.
     *     PHP returns a plain array snapshot — mutations do not propagate.
     *
     * @return array<K>
     */
    public function keySet(): array;

    /**
     * Returns an array of the values contained in this map.
     *
     * ⚠️  Java API difference: Java returns a live Collection<V> backed by the map.
     *     PHP returns a plain array snapshot.
     *
     * @return array<V>
     */
    public function values(): array;

    /**
     * Returns an array of {@see Entry} objects representing the mappings in this map.
     *
     * ⚠️  Java API difference: Java returns a live Set<Map.Entry<K,V>>.
     *     PHP returns a plain array snapshot of Entry objects.
     *
     * @return Entry
     */
    public function entrySet(): array;

    // -------------------------------------------------------------------------
    // Comparison and hashing
    // -------------------------------------------------------------------------

    /**
     * Compares the specified object with this map for equality.
     * Two maps are equal if they have identical entrySet() contents.
     *
     * ⚠️  PHP note: this does NOT override PHP's == / === operators.
     *     Use this method explicitly for semantic map equality.
     */
    public function equals(mixed $o): bool;

    /**
     * Returns the hash code value for this map (sum of entry hash codes).
     */
    public function hashCode(): int;

    // -------------------------------------------------------------------------
    // Default methods (implemented via MapDefaults trait in AbstractMap)
    // -------------------------------------------------------------------------

    /**
     * Returns the value to which the specified key is mapped,
     * or $defaultValue if this map contains no mapping for the key.
     *
     * @param mixed $key
     * @param V     $defaultValue
     * @return V
     */
    public function getOrDefault(mixed $key, mixed $defaultValue): mixed;

    /**
     * Performs the given action for each entry in this map.
     * Action signature: function(mixed $key, mixed $value): void
     *
     * @param callable(K, V): void $action
     */
    public function forEach(callable $action): void;

    /**
     * Replaces each entry's value with the result of invoking the given function.
     * Function signature: function(mixed $key, mixed $value): mixed
     *
     * @param callable(K, V): V $function
     * @throws \RuntimeException if this map does not support put
     */
    public function replaceAll(callable $function): void;

    /**
     * If the specified key is not already associated with a value (or is mapped to null),
     * associates it with the given value and returns null, else returns the current value.
     *
     * @param K $key
     * @param V $value
     * @return V|null
     */
    public function putIfAbsent(mixed $key, mixed $value): mixed;

    /**
     * Removes the entry for the specified key only if it is currently mapped to the specified value.
     *
     * ⚠️  Java API difference: In Java this is an overload of remove(key, value).
     *     In PHP it is a distinct method to avoid signature conflicts.
     *
     * @param mixed $key
     * @param mixed $value
     * @return bool true if the value was removed
     */
    public function removeEntry(mixed $key, mixed $value): bool;

    /**
     * Replaces the entry for the specified key only if currently mapped to the specified value.
     *
     * ⚠️  Java API difference: In Java this is an overload of replace(key, oldValue, newValue).
     *     In PHP it is a distinct method.
     *
     * @param K $key
     * @param V $oldValue
     * @param V $newValue
     * @return bool true if the value was replaced
     */
    public function replaceEntry(mixed $key, mixed $oldValue, mixed $newValue): bool;

    /**
     * Replaces the entry for the specified key only if it is currently mapped to some value.
     *
     * @param K $key
     * @param V $value
     * @return V|null the previous value, or null if there was no mapping
     */
    public function replace(mixed $key, mixed $value): mixed;

    /**
     * If the specified key is not already associated with a value (or is mapped to null),
     * attempts to compute its value using the given mapping function.
     * Mapping function signature: function(mixed $key): mixed
     *
     * @param K               $key
     * @param callable(K): V  $mappingFunction
     * @return V|null
     */
    public function computeIfAbsent(mixed $key, callable $mappingFunction): mixed;

    /**
     * If the value for the specified key is present and non-null,
     * attempts to compute a new mapping given the key and its current mapped value.
     * Remapping function signature: function(mixed $key, mixed $value): mixed
     *
     * @param K                    $key
     * @param callable(K, V): V    $remappingFunction
     * @return V|null
     */
    public function computeIfPresent(mixed $key, callable $remappingFunction): mixed;

    /**
     * Attempts to compute a mapping for the specified key and its current mapped value.
     * Remapping function signature: function(mixed $key, mixed|null $value): mixed
     *
     * @param K                       $key
     * @param callable(K, V|null): V  $remappingFunction
     * @return V|null
     */
    public function compute(mixed $key, callable $remappingFunction): mixed;

    /**
     * If the specified key is not already associated with a value or is associated with null,
     * associates it with the given non-null value. Otherwise, replaces the associated value
     * with the results of the given remapping function, or removes if the result is null.
     * Remapping function signature: function(mixed $oldValue, mixed $value): mixed
     *
     * @param K                    $key
     * @param V                    $value
     * @param callable(V, V): V    $remappingFunction
     * @return V|null
     */
    public function merge(mixed $key, mixed $value, callable $remappingFunction): mixed;
}
