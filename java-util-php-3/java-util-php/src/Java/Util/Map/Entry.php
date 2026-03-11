<?php

declare(strict_types=1);

namespace Java\Util\Map;

/**
 * Port of java.util.Map.Entry<K,V>
 *
 * A map entry (key-value pair).
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * JAVA → PHP CALLING CONVENTION
 * ─────────────────────────────────────────────────────────────────────────────
 *
 *   Java:  Map.Entry.comparingByKey()
 *   PHP:   Map\Entry::comparingByKey()          ← with: use Java\Util\Map;
 *   PHP:   Entry::comparingByKey()              ← with: use Java\Util\Map\Entry;
 *
 * The single difference is `\` instead of `.` — PHP's namespace separator.
 * `Map\Entry::` is preferred because it mirrors the Java context visually.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * DESIGN: WHY abstract class, not interface?
 * ─────────────────────────────────────────────────────────────────────────────
 *
 *   Java's Map.Entry is an interface that carries static method *bodies* (Java 8+).
 *   PHP interfaces cannot hold method implementations.
 *   Solution: Entry is an abstract class, giving us:
 *     • Abstract instance methods  → same contract as a Java interface
 *     • Static methods with bodies → comparingByKey(), copyOf(), etc.
 *
 *   ⚠️ Consequence: concrete entries must extend Entry (not implement it).
 *
 * @template K
 * @template V
 */
abstract class Entry
{
    // -------------------------------------------------------------------------
    // Abstract instance contract
    // -------------------------------------------------------------------------

    /** @return K */
    abstract public function getKey(): mixed;

    /** @return V */
    abstract public function getValue(): mixed;

    /**
     * @param  V $value
     * @return V old value
     * @throws \RuntimeException if entry is immutable
     */
    abstract public function setValue(mixed $value): mixed;

    abstract public function equals(mixed $o): bool;

    abstract public function hashCode(): int;

    // -------------------------------------------------------------------------
    // Static factory / comparator methods
    // Direct port of java.util.Map.Entry static interface methods (Java 8+, 17+)
    //
    // Usage (mirrors Java exactly, one char different):
    //
    //   use Java\Util\Map;
    //
    //   Map\Entry::comparingByKey()      // Java: Map.Entry.comparingByKey()
    //   Map\Entry::comparingByValue()    // Java: Map.Entry.comparingByValue()
    //   Map\Entry::copyOf($entry)        // Java: Map.Entry.copyOf(entry)
    // -------------------------------------------------------------------------

    /**
     * Returns a comparator that compares Entry objects in natural order on key.
     *
     *   Java: Map.Entry.comparingByKey()
     *   PHP:  Map\Entry::comparingByKey()
     *
     * @return callable(self, self): int
     */
    public static function comparingByKey(): callable
    {
        return static fn (self $e1, self $e2): int => $e1->getKey() <=> $e2->getKey();
    }

    /**
     * Returns a comparator that compares Entry objects in natural order on value.
     *
     *   Java: Map.Entry.comparingByValue()
     *   PHP:  Map\Entry::comparingByValue()
     *
     * @return callable(self, self): int
     */
    public static function comparingByValue(): callable
    {
        return static fn (self $e1, self $e2): int => $e1->getValue() <=> $e2->getValue();
    }

    /**
     * Returns a comparator that compares Entry objects by key using the given comparator.
     *
     *   Java: Map.Entry.comparingByKey(Comparator<? super K> cmp)
     *   PHP:  Map\Entry::comparingByKey(callable $cmp)
     *
     * ⚠️  PHP naming: Java uses one overloaded method; PHP needs a distinct name.
     *
     * @param  callable(mixed, mixed): int $cmp
     * @return callable(self, self): int
     */
    public static function comparingByKeyWith(callable $cmp): callable
    {
        return static fn (self $e1, self $e2): int => $cmp($e1->getKey(), $e2->getKey());
    }

    /**
     * Returns a comparator that compares Entry objects by value using the given comparator.
     *
     *   Java: Map.Entry.comparingByValue(Comparator<? super V> cmp)
     *   PHP:  Map\Entry::comparingByValueWith(callable $cmp)
     *
     * @param  callable(mixed, mixed): int $cmp
     * @return callable(self, self): int
     */
    public static function comparingByValueWith(callable $cmp): callable
    {
        return static fn (self $e1, self $e2): int => $cmp($e1->getValue(), $e2->getValue());
    }

    /**
     * Returns an immutable copy of the given Entry.
     *
     *   Java: Map.Entry.copyOf(Map.Entry<? extends K, ? extends V> e)  [Java 17+]
     *   PHP:  Map\Entry::copyOf($entry)
     *
     * If the entry is already a SimpleImmutableEntry it is returned as-is.
     *
     * @template CK
     * @template CV
     * @param  Entry<CK,CV>               $e
     * @return SimpleImmutableEntry<CK,CV>
     * @throws \InvalidArgumentException  if key or value is null
     */
    public static function copyOf(self $e): SimpleImmutableEntry
    {
        if ($e instanceof SimpleImmutableEntry) {
            return $e;
        }
        return new SimpleImmutableEntry($e->getKey(), $e->getValue());
    }

    // -------------------------------------------------------------------------
    // Shared utilities for concrete subclasses
    // -------------------------------------------------------------------------

    final protected static function nullSafeEquals(mixed $a, mixed $b): bool
    {
        return $a === null ? $b === null : $a == $b;
    }

    final protected static function valueHash(mixed $v): int
    {
        return $v === null ? 0 : crc32(serialize($v));
    }
}
