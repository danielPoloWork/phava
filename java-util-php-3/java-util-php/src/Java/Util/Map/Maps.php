<?php

declare(strict_types=1);

namespace Java\Util\Map;

/**
 * Static factory utility class — port of java.util.Map static factory methods.
 *
 * In Java, factory methods are static methods on the Map interface itself (Java 9+).
 * Since PHP interfaces cannot hold method implementations, these are provided here.
 *
 * Java → PHP mapping:
 *   Map.of()                 → Maps::of()
 *   Map.of(k1,v1)            → Maps::of(k1, v1)          [variadics handle all arities]
 *   Map.ofEntries(entries)   → Maps::ofEntries(...entries)
 *   Map.entry(k, v)          → Maps::entry(k, v)
 *   Map.copyOf(map)          → Maps::copyOf(map)
 *   Map.Entry.copyOf(entry)  → Maps::entryCopyOf(entry)
 *
 * @see ImmutableMap
 * @see Map\SimpleImmutableEntry
 */
final class Maps
{
    private function __construct() {}

    // -------------------------------------------------------------------------
    // Map.of() family — up to 10 key-value pairs (matches Java's API surface)
    // -------------------------------------------------------------------------

    /**
     * Returns an unmodifiable map containing zero mappings.
     *
     * @template K
     * @template V
     * @return ImmutableMap<K,V>
     */
    public static function of(): ImmutableMap
    {
        return new ImmutableMap([]);
    }

    /**
     * Returns an unmodifiable map from a flat list of key-value arguments.
     *
     * Usage mirrors Java's Map.of(k1,v1, k2,v2, ...) — pass pairs as a flat list.
     * Must supply an even number of arguments.
     *
     * Example:
     *   $map = Maps::ofPairs('a', 1, 'b', 2, 'c', 3);
     *
     * @throws \InvalidArgumentException on odd argument count, null keys/values, or duplicates
     * @return ImmutableMap<mixed, mixed>
     */
    public static function ofPairs(mixed ...$args): ImmutableMap
    {
        if (count($args) % 2 !== 0) {
            throw new \InvalidArgumentException(
                'ofPairs() requires an even number of arguments (key-value pairs).'
            );
        }

        $pairs = [];
        for ($i = 0, $max = count($args); $i < $max; $i += 2) {
            $pairs[] = [$args[$i], $args[$i + 1]];
        }

        return new ImmutableMap($pairs);
    }

    /**
     * Returns an unmodifiable map from an associative PHP array.
     * Provides a more idiomatic PHP entry-point.
     *
     * Example:
     *   $map = Maps::ofArray(['key1' => 'val1', 'key2' => 'val2']);
     *
     * @param array<mixed, mixed> $assoc
     * @throws \InvalidArgumentException on null values or duplicate keys
     * @return ImmutableMap<mixed, mixed>
     */
    public static function ofArray(array $assoc): ImmutableMap
    {
        $pairs = [];
        foreach ($assoc as $k => $v) {
            $pairs[] = [$k, $v];
        }
        return new ImmutableMap($pairs);
    }

    /**
     * Returns an unmodifiable map from an array of Entry objects.
     * Port of java.util.Map.ofEntries(Entry<K,V>... entries).
     *
     * Example:
     *   $map = Maps::ofEntries(
     *       Maps::entry('a', 1),
     *       Maps::entry('b', 2),
     *   );
     *
     * @template K
     * @template V
     * @param Entry<K,V> ...$entries
     * @throws \InvalidArgumentException on null keys/values or duplicates
     * @return ImmutableMap<K,V>
     */
    public static function ofEntries(Entry ...$entries): ImmutableMap
    {
        $pairs = [];
        foreach ($entries as $entry) {
            $pairs[] = [$entry->getKey(), $entry->getValue()];
        }
        return new ImmutableMap($pairs);
    }

    /**
     * Returns an unmodifiable Entry containing the given key and value.
     * Port of java.util.Map.entry(K k, V v) — Java 9+.
     *
     * @template K
     * @template V
     * @param K $key
     * @param V $value
     * @throws \InvalidArgumentException if key or value is null
     * @return SimpleImmutableEntry<K,V>
     */
    public static function entry(mixed $key, mixed $value): SimpleImmutableEntry
    {
        return new SimpleImmutableEntry($key, $value);
    }

    /**
     * Returns an unmodifiable Map containing the entries of the given Map.
     * Port of java.util.Map.copyOf(Map<K,V> map) — Java 10+.
     *
     * If the given map is already an ImmutableMap, it is returned as-is
     * (mirrors Java's @implNote: "will generally not create a copy").
     *
     * @template K
     * @template V
     * @param Map<K,V> $map source map, must not contain null keys or values
     * @throws \InvalidArgumentException if map contains null keys or values
     * @return ImmutableMap<K,V>
     */
    public static function copyOf(Map $map): ImmutableMap
    {
        if ($map instanceof ImmutableMap) {
            return $map; // already immutable
        }

        $pairs = [];
        foreach ($map->entrySet() as $entry) {
            $pairs[] = [$entry->getKey(), $entry->getValue()];
        }

        return new ImmutableMap($pairs);
    }

    /**
     * Returns an immutable copy of the given Entry.
     *
     * Delegates to Map\Entry::copyOf() — provided here as a convenience alias
     * for code that imports Maps rather than Entry.
     *
     * Java: Map.Entry.copyOf(entry)   →   PHP: Map\Entry::copyOf($entry)
     *                                   or PHP: Maps::entryCopyOf($entry)
     *
     * @template CK
     * @template CV
     * @param  Entry<CK,CV>               $entry
     * @return SimpleImmutableEntry<CK,CV>
     * @throws \InvalidArgumentException if key or value is null
     * @see \Java\Util\Map\Entry::copyOf()
     */
    public static function entryCopyOf(Entry $entry): SimpleImmutableEntry
    {
        return Entry::copyOf($entry); // delegate to canonical location
    }
}
