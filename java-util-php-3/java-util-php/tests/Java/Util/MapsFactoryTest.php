<?php

declare(strict_types=1);

namespace Tests\Java\Util;

use Java\Util\Map\Entry;
use Java\Util\Map\HashMap;
use Java\Util\Map\ImmutableMap;
use Java\Util\Map\Map;
use Java\Util\Map\Maps;
use Java\Util\Map\SimpleEntry;
use Java\Util\Map\SimpleImmutableEntry;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;

// ← `use Java\Util\Map` makes Map\Entry:: resolve correctly
// ← direct import; Entry:: also works

#[CoversClass(Maps::class)]
#[CoversClass(ImmutableMap::class)]
#[CoversClass(SimpleImmutableEntry::class)]
#[CoversClass(SimpleEntry::class)]
#[CoversClass(Entry::class)]
final class MapsFactoryTest extends TestCase
{
    // =========================================================================
    // Maps::of() / Maps::ofPairs()
    // =========================================================================

    #[Test]
    public function ofReturnsEmptyImmutableMap(): void
    {
        $map = Maps::of();
        self::assertInstanceOf(ImmutableMap::class, $map);
        self::assertTrue($map->isEmpty());
    }

    #[Test]
    public function ofPairsCreatesTwoEntryMap(): void
    {
        $map = Maps::ofPairs('k1', 'v1', 'k2', 'v2');
        self::assertSame(2, $map->size());
        self::assertSame('v1', $map->get('k1'));
    }

    #[Test]
    public function ofPairsThrowsOnOddArgumentCount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Maps::ofPairs('k1', 'v1', 'orphan');
    }

    #[Test]
    public function ofPairsThrowsOnDuplicateKeys(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Maps::ofPairs('dup', 1, 'dup', 2);
    }

    #[Test]
    public function ofPairsThrowsOnNullKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Maps::ofPairs(null, 'value');
    }

    #[Test]
    public function ofArrayCreatesMapFromAssociativeArray(): void
    {
        $map = Maps::ofArray(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertSame(3, $map->size());
        self::assertSame(2, $map->get('b'));
    }

    #[Test]
    public function ofEntriesCreatesMapFromEntryObjects(): void
    {
        $map = Maps::ofEntries(
            Maps::entry('x', 10),
            Maps::entry('y', 20),
        );
        self::assertSame(2, $map->size());
        self::assertSame(10, $map->get('x'));
    }

    // =========================================================================
    // ImmutableMap — mutating operations must throw
    // =========================================================================

    #[Test]
    public function immutableMapThrowsOnPut(): void
    {
        $this->expectException(\RuntimeException::class);
        Maps::ofPairs('k', 'v')->put('new', 'val');
    }

    #[Test]
    public function immutableMapThrowsOnRemove(): void
    {
        $this->expectException(\RuntimeException::class);
        Maps::ofPairs('k', 'v')->remove('k');
    }

    #[Test]
    public function immutableMapThrowsOnClear(): void
    {
        $this->expectException(\RuntimeException::class);
        Maps::ofPairs('k', 'v')->clear();
    }

    // =========================================================================
    // Maps::copyOf()
    // =========================================================================

    #[Test]
    public function copyOfProducesImmutableSnapshot(): void
    {
        $source = new HashMap(['a' => 1, 'b' => 2]);
        $copy   = Maps::copyOf($source);

        self::assertInstanceOf(ImmutableMap::class, $copy);
        $source->put('c', 3);
        self::assertFalse($copy->containsKey('c')); // snapshot not affected
    }

    #[Test]
    public function copyOfImmutableMapReturnsSameInstance(): void
    {
        $original = Maps::ofPairs('k', 'v');
        self::assertSame($original, Maps::copyOf($original));
    }

    // =========================================================================
    // ★ CORE CONVENTION TESTS ★
    //
    //   Java API:   Map.Entry.comparingByKey()
    //   PHP equiv:  Map\Entry::comparingByKey()    ← `use Java\Util\Map` in scope
    //
    // PHP's `\` namespace separator replaces Java's `.` — one char difference.
    // =========================================================================

    #[Test]
    public function mapEntryComparingByKeyMirrorsJavaApi(): void
    {
        /**
         * Java:   entries.sort(Map.Entry.comparingByKey())
         * PHP:    usort($entries, Map\Entry::comparingByKey())
         *
         * `Map` here is the alias from `use Java\Util\Map` (line 12).
         * `Map\Entry` then resolves to `Java\Util\Map\Entry` — the abstract class
         * that carries the static method, exactly as Java's interface does.
         */
        $entries = [
            new SimpleEntry('c', 3),
            new SimpleEntry('a', 1),
            new SimpleEntry('b', 2),
        ];

        usort($entries, Map\Entry::comparingByKey()); // ← the key line

        self::assertSame('a', $entries[0]->getKey());
        self::assertSame('b', $entries[1]->getKey());
        self::assertSame('c', $entries[2]->getKey());
    }

    #[Test]
    public function mapEntryComparingByValueMirrorsJavaApi(): void
    {
        /**
         * Java:   entries.sort(Map.Entry.comparingByValue())
         * PHP:    usort($entries, Map\Entry::comparingByValue())
         */
        $entries = [
            new SimpleEntry('x', 30),
            new SimpleEntry('y', 10),
            new SimpleEntry('z', 20),
        ];

        usort($entries, Map\Entry::comparingByValue());

        self::assertSame(10, $entries[0]->getValue());
        self::assertSame(20, $entries[1]->getValue());
        self::assertSame(30, $entries[2]->getValue());
    }

    #[Test]
    public function mapEntryComparingByKeyWithCustomComparator(): void
    {
        /**
         * Java:   entries.sort(Map.Entry.comparingByKey(Comparator.comparingInt(String::length)))
         * PHP:    usort($entries, Map\Entry::comparingByKeyWith(fn($a,$b) => strlen($a)<=>strlen($b)))
         */
        $entries = [
            new SimpleEntry('banana', 1),
            new SimpleEntry('kiwi', 3),
            new SimpleEntry('apple', 2),
        ];

        usort($entries, Map\Entry::comparingByKeyWith(
            fn (string $a, string $b): int => strlen($a) <=> strlen($b)
        ));

        self::assertSame('kiwi', $entries[0]->getKey());   // 4 chars
        self::assertSame('apple', $entries[1]->getKey());  // 5 chars
        self::assertSame('banana', $entries[2]->getKey()); // 6 chars
    }

    #[Test]
    public function mapEntryCopyOfMirrorsJavaApi(): void
    {
        /**
         * Java:   Map.Entry.copyOf(mutableEntry)
         * PHP:    Map\Entry::copyOf($mutableEntry)
         */
        $mutable = new SimpleEntry('k', 'v');
        $copy    = Map\Entry::copyOf($mutable); // ← Map\Entry:: mirrors Map.Entry.

        self::assertInstanceOf(SimpleImmutableEntry::class, $copy);
        self::assertSame('k', $copy->getKey());
        self::assertSame('v', $copy->getValue());
    }

    #[Test]
    public function mapEntryCopyOfReturnsImmutableInstanceUnchanged(): void
    {
        /**
         * Java @implNote: "If the given entry was obtained from a call to copyOf
         *   or Map::entry, calling copyOf will generally not create another copy."
         * PHP mirrors this: returns same instance if already SimpleImmutableEntry.
         */
        $immutable = new SimpleImmutableEntry('k', 'v');
        $copy      = Map\Entry::copyOf($immutable);

        self::assertSame($immutable, $copy);
    }

    #[Test]
    public function directEntryImportAlsoWorks(): void
    {
        /**
         * Alternative: `use Java\Util\Map\Entry` → Entry::comparingByKey()
         * Equivalent to Map\Entry:: but without Map context prefix.
         * Useful when Map interface is not imported.
         */
        $entries = [
            new SimpleEntry('z', 1),
            new SimpleEntry('a', 2),
        ];

        usort($entries, Entry::comparingByKey()); // direct import form

        self::assertSame('a', $entries[0]->getKey());
    }

    #[Test]
    public function mapsEntryCopyOfIsAliasForMapEntryStaticMethod(): void
    {
        $mutable  = new SimpleEntry('a', 'b');
        $viaEntry = Map\Entry::copyOf($mutable);   // canonical Java-style call
        $viaMaps  = Maps::entryCopyOf($mutable);   // convenience alias on Maps

        self::assertTrue($viaEntry->equals($viaMaps));
    }

    // =========================================================================
    // Maps::entry() — Maps::entry() vs Map\Entry construction
    // =========================================================================

    #[Test]
    public function mapsEntryCreatesImmutableEntry(): void
    {
        $e = Maps::entry('key', 'value');

        self::assertInstanceOf(SimpleImmutableEntry::class, $e);
        self::assertSame('key', $e->getKey());
        self::assertSame('value', $e->getValue());
    }

    #[Test]
    public function mapsEntrySetValueThrowsRuntimeException(): void
    {
        $this->expectException(\RuntimeException::class);
        Maps::entry('k', 'v')->setValue('new');
    }

    // =========================================================================
    // Entry equality / hashCode
    // =========================================================================

    #[Test]
    public function simpleEntryEqualityFollowsJavaContract(): void
    {
        $e1 = new SimpleEntry('k', 'v');
        $e2 = new SimpleEntry('k', 'v');
        $e3 = new SimpleEntry('k', 'different');

        self::assertTrue($e1->equals($e2));
        self::assertFalse($e1->equals($e3));
    }

    #[Test]
    public function equalEntriesHaveSameHashCode(): void
    {
        $e1 = new SimpleEntry('k', 'v');
        $e2 = new SimpleEntry('k', 'v');

        self::assertSame($e1->hashCode(), $e2->hashCode());
    }
}
