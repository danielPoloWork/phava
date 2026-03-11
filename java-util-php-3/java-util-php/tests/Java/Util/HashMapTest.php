<?php

declare(strict_types=1);

namespace Tests\Java\Util;

use Java\Util\Map\HashMap;
use Java\Util\Map\SimpleEntry;
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider, Group, Test};
use PHPUnit\Framework\TestCase;

#[CoversClass(HashMap::class)]
final class HashMapTest extends TestCase
{
    // =========================================================================
    // Construction
    // =========================================================================

    #[Test]
    public function constructsEmptyMapByDefault(): void
    {
        $map = new HashMap();

        self::assertTrue($map->isEmpty());
        self::assertSame(0, $map->size());
    }

    #[Test]
    public function constructsFromAssociativeArray(): void
    {
        $map = new HashMap(['a' => 1, 'b' => 2, 'c' => 3]);

        self::assertSame(3, $map->size());
        self::assertSame(1, $map->get('a'));
        self::assertSame(3, $map->get('c'));
    }

    #[Test]
    public function constructsFromAnotherMap(): void
    {
        $source = new HashMap(['x' => 'hello', 'y' => 'world']);
        $copy   = new HashMap($source);

        self::assertTrue($copy->equals($source));
        self::assertNotSame($copy, $source); // distinct instances
    }

    // =========================================================================
    // put / get
    // =========================================================================

    #[Test]
    public function putReturnsPreviousValue(): void
    {
        $map = new HashMap();

        self::assertNull($map->put('k', 'first'));
        self::assertSame('first', $map->put('k', 'second'));
        self::assertSame('second', $map->get('k'));
    }

    #[Test]
    public function getReturnsNullForMissingKey(): void
    {
        $map = new HashMap();
        self::assertNull($map->get('missing'));
    }

    #[Test]
    public function supportsNullKey(): void
    {
        $map = new HashMap();
        $map->put(null, 'null-value');

        self::assertTrue($map->containsKey(null));
        self::assertSame('null-value', $map->get(null));
    }

    #[Test]
    public function supportsNullValue(): void
    {
        $map = new HashMap();
        $map->put('k', null);

        self::assertTrue($map->containsKey('k'));
        self::assertNull($map->get('k'));
    }

    #[Test]
    public function supportsIntegerKeys(): void
    {
        $map = new HashMap();
        $map->put(0, 'zero');
        $map->put(42, 'forty-two');

        self::assertSame('zero', $map->get(0));
        self::assertSame('forty-two', $map->get(42));
    }

    // =========================================================================
    // containsKey / containsValue
    // =========================================================================

    #[Test]
    public function containsKeyReturnsTrueForExistingKey(): void
    {
        $map = new HashMap(['exists' => true]);

        self::assertTrue($map->containsKey('exists'));
        self::assertFalse($map->containsKey('absent'));
    }

    #[Test]
    public function containsValueReturnsTrueForExistingValue(): void
    {
        $map = new HashMap(['a' => 42, 'b' => 99]);

        self::assertTrue($map->containsValue(42));
        self::assertFalse($map->containsValue(0));
    }

    // =========================================================================
    // remove
    // =========================================================================

    #[Test]
    public function removeReturnsPreviousValue(): void
    {
        $map = new HashMap(['k' => 'v']);

        self::assertSame('v', $map->remove('k'));
        self::assertFalse($map->containsKey('k'));
        self::assertSame(0, $map->size());
    }

    #[Test]
    public function removeReturnNullForMissingKey(): void
    {
        $map = new HashMap();
        self::assertNull($map->remove('missing'));
    }

    // =========================================================================
    // putAll / clear
    // =========================================================================

    #[Test]
    public function putAllMergesEntries(): void
    {
        $map    = new HashMap(['a' => 1]);
        $source = new HashMap(['b' => 2, 'c' => 3]);
        $map->putAll($source);

        self::assertSame(3, $map->size());
        self::assertSame(2, $map->get('b'));
    }

    #[Test]
    public function clearEmptiesTheMap(): void
    {
        $map = new HashMap(['a' => 1, 'b' => 2]);
        $map->clear();

        self::assertTrue($map->isEmpty());
        self::assertSame(0, $map->size());
    }

    // =========================================================================
    // Views: keySet / values / entrySet
    // =========================================================================

    #[Test]
    public function keySetReturnsAllKeys(): void
    {
        $map  = new HashMap(['x' => 1, 'y' => 2, 'z' => 3]);
        $keys = $map->keySet();

        self::assertCount(3, $keys);
        self::assertContains('x', $keys);
        self::assertContains('y', $keys);
        self::assertContains('z', $keys);
    }

    #[Test]
    public function valuesReturnsAllValues(): void
    {
        $map    = new HashMap(['a' => 10, 'b' => 20]);
        $values = $map->values();

        self::assertCount(2, $values);
        self::assertContains(10, $values);
        self::assertContains(20, $values);
    }

    #[Test]
    public function entrySetReturnsSimpleEntries(): void
    {
        $map     = new HashMap(['k' => 'v']);
        $entries = $map->entrySet();

        self::assertCount(1, $entries);
        self::assertInstanceOf(SimpleEntry::class, $entries[0]);
        self::assertSame('k', $entries[0]->getKey());
        self::assertSame('v', $entries[0]->getValue());
    }

    // =========================================================================
    // Default methods (via MapDefaults trait)
    // =========================================================================

    #[Test]
    public function getOrDefaultReturnsDefaultWhenKeyAbsent(): void
    {
        $map = new HashMap(['a' => 1]);

        self::assertSame(1, $map->getOrDefault('a', 99));
        self::assertSame(99, $map->getOrDefault('missing', 99));
    }

    #[Test]
    public function forEachIteratesAllEntries(): void
    {
        $map       = new HashMap(['a' => 1, 'b' => 2]);
        $collected = [];

        $map->forEach(function (mixed $k, mixed $v) use (&$collected): void {
            $collected[$k] = $v;
        });

        self::assertSame(['a' => 1, 'b' => 2], $collected);
    }

    #[Test]
    public function replaceAllTransformsValues(): void
    {
        $map = new HashMap(['a' => 1, 'b' => 2, 'c' => 3]);
        $map->replaceAll(fn ($k, $v) => $v * 10);

        self::assertSame(10, $map->get('a'));
        self::assertSame(20, $map->get('b'));
        self::assertSame(30, $map->get('c'));
    }

    #[Test]
    public function putIfAbsentDoesNotOverwriteExistingValue(): void
    {
        $map = new HashMap(['k' => 'original']);
        $map->putIfAbsent('k', 'replacement');

        self::assertSame('original', $map->get('k'));
    }

    #[Test]
    public function putIfAbsentInsertsWhenKeyAbsent(): void
    {
        $map = new HashMap();
        $map->putIfAbsent('k', 'inserted');

        self::assertSame('inserted', $map->get('k'));
    }

    #[Test]
    public function removeEntryRemovesOnlyWhenValueMatches(): void
    {
        $map = new HashMap(['k' => 'v']);

        self::assertFalse($map->removeEntry('k', 'wrong'));
        self::assertTrue($map->containsKey('k'));

        self::assertTrue($map->removeEntry('k', 'v'));
        self::assertFalse($map->containsKey('k'));
    }

    #[Test]
    public function replaceEntryReplacesOnlyWhenOldValueMatches(): void
    {
        $map = new HashMap(['k' => 'old']);

        self::assertFalse($map->replaceEntry('k', 'wrong', 'new'));
        self::assertSame('old', $map->get('k'));

        self::assertTrue($map->replaceEntry('k', 'old', 'new'));
        self::assertSame('new', $map->get('k'));
    }

    #[Test]
    public function replaceUpdatesExistingKey(): void
    {
        $map = new HashMap(['k' => 'old']);

        $previous = $map->replace('k', 'new');
        self::assertSame('old', $previous);
        self::assertSame('new', $map->get('k'));
    }

    #[Test]
    public function replaceReturnsNullForMissingKey(): void
    {
        $map = new HashMap();
        self::assertNull($map->replace('missing', 'value'));
    }

    #[Test]
    public function computeIfAbsentComputesAndStoresNewValue(): void
    {
        $map = new HashMap();

        $result = $map->computeIfAbsent('k', fn ($key) => strtoupper($key));

        self::assertSame('K', $result);
        self::assertSame('K', $map->get('k'));
    }

    #[Test]
    public function computeIfAbsentDoesNotOverwriteExisting(): void
    {
        $map = new HashMap(['k' => 'existing']);

        $result = $map->computeIfAbsent('k', fn ($key) => 'computed');

        self::assertSame('existing', $result);
        self::assertSame('existing', $map->get('k'));
    }

    #[Test]
    public function computeIfPresentUpdatesExistingKey(): void
    {
        $map = new HashMap(['k' => 'hello']);

        $result = $map->computeIfPresent('k', fn ($k, $v) => $v . ' world');

        self::assertSame('hello world', $result);
        self::assertSame('hello world', $map->get('k'));
    }

    #[Test]
    public function computeIfPresentRemovesWhenFunctionReturnsNull(): void
    {
        $map = new HashMap(['k' => 'value']);

        $result = $map->computeIfPresent('k', fn ($k, $v) => null);

        self::assertNull($result);
        self::assertFalse($map->containsKey('k'));
    }

    #[Test]
    public function computeWorksForNewAndExistingKeys(): void
    {
        $map = new HashMap();

        // Insert new key
        $map->compute('counter', fn ($k, $v) => ($v ?? 0) + 1);
        self::assertSame(1, $map->get('counter'));

        // Increment existing
        $map->compute('counter', fn ($k, $v) => ($v ?? 0) + 1);
        self::assertSame(2, $map->get('counter'));
    }

    #[Test]
    public function mergeCreatesNewEntryWhenAbsent(): void
    {
        $map = new HashMap();

        $result = $map->merge('k', 'hello', fn ($old, $new) => $old . $new);

        self::assertSame('hello', $result);
        self::assertSame('hello', $map->get('k'));
    }

    #[Test]
    public function mergeAppliesRemappingFunctionWhenPresent(): void
    {
        $map = new HashMap(['k' => 'hello']);

        $result = $map->merge('k', ' world', fn ($old, $new) => $old . $new);

        self::assertSame('hello world', $result);
    }

    #[Test]
    public function mergeRemovesEntryWhenFunctionReturnsNull(): void
    {
        $map = new HashMap(['k' => 'v']);

        $map->merge('k', 'anything', fn ($old, $new) => null);

        self::assertFalse($map->containsKey('k'));
    }

    // =========================================================================
    // equals / hashCode
    // =========================================================================

    #[Test]
    public function equalsMapsHaveSameHashCode(): void
    {
        $m1 = new HashMap(['a' => 1, 'b' => 2]);
        $m2 = new HashMap(['b' => 2, 'a' => 1]);

        self::assertTrue($m1->equals($m2));
        self::assertSame($m1->hashCode(), $m2->hashCode());
    }

    #[Test]
    public function equalsReturnsFalseForDifferentSizes(): void
    {
        $m1 = new HashMap(['a' => 1]);
        $m2 = new HashMap(['a' => 1, 'b' => 2]);

        self::assertFalse($m1->equals($m2));
    }

    #[Test]
    public function equalsReturnsFalseForNonMap(): void
    {
        $map = new HashMap(['a' => 1]);
        self::assertFalse($map->equals(['a' => 1]));
    }

    // =========================================================================
    // Multi-value / compute patterns (real-world use cases)
    // =========================================================================

    #[Test]
    public function multiValueMapPattern(): void
    {
        // Equivalent of: Map<String, List<String>>
        $multiMap = new HashMap();

        $addToList = function (string $key, string $value) use ($multiMap): void {
            $multiMap->computeIfAbsent($key, fn ($k) => []);
            $current   = $multiMap->get($key);
            $current[] = $value;
            $multiMap->put($key, $current);
        };

        $addToList('fruit', 'apple');
        $addToList('fruit', 'banana');
        $addToList('vegetable', 'carrot');

        self::assertCount(2, $multiMap->get('fruit'));
        self::assertContains('apple', $multiMap->get('fruit'));
    }

    #[Test]
    public function wordFrequencyCountPattern(): void
    {
        // Map<String, Integer> word frequency — idiomatic merge() usage
        $words = ['foo', 'bar', 'foo', 'baz', 'foo', 'bar'];
        $freq  = new HashMap();

        foreach ($words as $word) {
            $freq->merge($word, 1, fn ($old, $new) => $old + $new);
        }

        self::assertSame(3, $freq->get('foo'));
        self::assertSame(2, $freq->get('bar'));
        self::assertSame(1, $freq->get('baz'));
    }
}
