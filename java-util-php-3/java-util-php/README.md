# java-util-php

PHP 8.1+ port of `java.util.Map<K,V>` and related types, mirroring Java API naming and PSR-4 path conventions.

---

## Requirements

| Requirement | Version |
|---|---|
| PHP | >= 8.1 |
| PHP extensions | none (stdlib only) |
| PHPUnit | ^10.5 (dev) |
| PHPStan | ^1.10 (dev) |

---

## Installation

```bash
composer require java-util/php-collections
```

Or clone and install manually:

```bash
git clone https://github.com/your-org/java-util-php
cd java-util-php
composer install
```

---

## Project Structure (PSR-4)

```
java-util-php/
├── composer.json
├── phpunit.xml
├── phpstan.neon
├── src/
│   └── Java/
│       └── Util/
│           ├── Map.php                  # interface Java\Util\Map
│           ├── AbstractMap.php          # abstract class Java\Util\AbstractMap
│           ├── HashMap.php              # class Java\Util\HashMap
│           ├── ImmutableMap.php         # class Java\Util\ImmutableMap  (Map.of() result)
│           ├── MapDefaults.php          # trait Java\Util\MapDefaults    (default methods)
│           ├── Maps.php                 # class Java\Util\Maps           (static factories)
│           └── Map/
│               ├── Entry.php            # interface Java\Util\Map\Entry
│               ├── SimpleEntry.php      # class Java\Util\Map\SimpleEntry
│               │                        # class Java\Util\Map\SimpleImmutableEntry
│               └── EntryComparators.php # class Java\Util\Map\EntryComparators
└── tests/
    └── Java/
        └── Util/
            ├── HashMapTest.php
            └── MapsFactoryTest.php
```

**Path mapping rule:** `java.util.ClassName` → `src/Java/Util/ClassName.php`, namespace `Java\Util`.

---

## Java → PHP Calling Convention

| Java | PHP | Requires |
|---|---|---|
| `Map.Entry.comparingByKey()` | `Map\Entry::comparingByKey()` | `use Java\Util\Map;` |
| `Map.Entry.comparingByValue()` | `Map\Entry::comparingByValue()` | `use Java\Util\Map;` |
| `Map.Entry.copyOf(e)` | `Map\Entry::copyOf($e)` | `use Java\Util\Map;` |
| `Map.of()` | `Maps::of()` | `use Java\Util\Maps;` |
| `Map.entry(k, v)` | `Maps::entry($k, $v)` | `use Java\Util\Maps;` |
| `Map.copyOf(map)` | `Maps::copyOf($map)` | `use Java\Util\Maps;` |

The single difference is PHP's `\` namespace separator instead of Java's `.`.
The `Map\Entry::` form is preferred because it visually preserves the Java context.

**How it works:** `use Java\Util\Map` imports the `Map` interface *and* makes `Map` a
valid namespace prefix in the current file. `Map\Entry` then resolves to `Java\Util\Map\Entry`
— the abstract class that carries the static methods, just as Java's interface does.

```php
use Java\Util\Map\Map;use Java\Util\Map\Maps;   // imports the Map interface AND enables Map\ prefix

// ─── Java: Map.Entry.comparingByKey() ─────────────────────────────────────
$entries = $hashMap->entrySet();
usort($entries, Map\Entry::comparingByKey());   // PHP ← one char from Java

// ─── Java: Map.Entry.comparingByValue() ───────────────────────────────────
usort($entries, Map\Entry::comparingByValue());

// ─── Java: Map.Entry.comparingByKey(Comparator) ───────────────────────────
usort($entries, Map\Entry::comparingByKeyWith(
    fn(string $a, string $b) => strlen($a) <=> strlen($b)
));

// ─── Java: Map.Entry.copyOf(entry) ────────────────────────────────────────
$snapshot = Map\Entry::copyOf($entries[0]);

// ─── Java: Map.of("a", 1, "b", 2) ────────────────────────────────────────
$immutable = Maps::ofPairs('a', 1, 'b', 2);

// ─── Java: Map.entry("host", "localhost") ─────────────────────────────────
$e = Maps::entry('host', 'localhost');
```

### `java.util.Map<K,V>` → `Java\Util\Map` (interface)

| Java Method | PHP Method | File | Notes |
|---|---|---|---|
| `size()` | `size(): int` | `Map.php` | Identical |
| `isEmpty()` | `isEmpty(): bool` | `Map.php` | Identical |
| `containsKey(Object)` | `containsKey(mixed): bool` | `Map.php` | No generics; uses `==` equality |
| `containsValue(Object)` | `containsValue(mixed): bool` | `Map.php` | Identical semantics |
| `get(Object)` | `get(mixed): mixed` | `Map.php` | Returns `null` on miss |
| `put(K, V)` | `put(mixed, mixed): mixed` | `Map.php` | Returns prev value or `null` |
| `remove(Object)` | `remove(mixed): mixed` | `Map.php` | Returns prev value or `null` |
| `remove(Object, Object)` | `removeEntry(mixed, mixed): bool` | `Map.php` | ⚠️ **Name change** — PHP can't overload |
| `putAll(Map)` | `putAll(Map): void` | `Map.php` | Identical |
| `clear()` | `clear(): void` | `Map.php` | Identical |
| `keySet()` | `keySet(): array` | `Map.php` | ⚠️ Returns `array`, not live `Set<K>` |
| `values()` | `values(): array` | `Map.php` | ⚠️ Returns `array`, not live `Collection<V>` |
| `entrySet()` | `entrySet(): array` | `Map.php` | ⚠️ Returns `array<Entry>` snapshot, not live `Set` |
| `equals(Object)` | `equals(mixed): bool` | `Map.php` | Does NOT override PHP `==` |
| `hashCode()` | `hashCode(): int` | `Map.php` | Uses `crc32(serialize())` |
| `getOrDefault(K, V)` | `getOrDefault(mixed, mixed): mixed` | `MapDefaults.php` | Default method via trait |
| `forEach(BiConsumer)` | `forEach(callable): void` | `MapDefaults.php` | `callable($key, $value): void` |
| `replaceAll(BiFunction)` | `replaceAll(callable): void` | `MapDefaults.php` | `callable($key, $value): mixed` |
| `putIfAbsent(K, V)` | `putIfAbsent(mixed, mixed): mixed` | `MapDefaults.php` | Identical |
| `replace(K, V)` | `replace(mixed, mixed): mixed` | `MapDefaults.php` | Returns prev value |
| `replace(K, V, V)` | `replaceEntry(mixed, mixed, mixed): bool` | `MapDefaults.php` | ⚠️ **Name change** |
| `computeIfAbsent(K, Function)` | `computeIfAbsent(mixed, callable): mixed` | `MapDefaults.php` | `callable($key): mixed` |
| `computeIfPresent(K, BiFunction)` | `computeIfPresent(mixed, callable): mixed` | `MapDefaults.php` | `callable($key, $value): mixed` |
| `compute(K, BiFunction)` | `compute(mixed, callable): mixed` | `MapDefaults.php` | `callable($key, $value\|null): mixed` |
| `merge(K, V, BiFunction)` | `merge(mixed, mixed, callable): mixed` | `MapDefaults.php` | `callable($old, $new): mixed` |
| `Map.of()` | `Maps::of()` | `Maps.php` | ⚠️ **Static factory on `Maps` class** |
| `Map.of(k1,v1,...)` | `Maps::ofPairs(k1,v1,...)` | `Maps.php` | ⚠️ **Variadics, flat pairs** |
| `Map.ofEntries(entries)` | `Maps::ofEntries(...$entries)` | `Maps.php` | Identical semantics |
| `Map.entry(k, v)` | `Maps::entry(k, v)` | `Maps.php` | Returns `SimpleImmutableEntry` |
| `Map.copyOf(map)` | `Maps::copyOf(map)` | `Maps.php` | Returns `ImmutableMap` |

### `java.util.Map.Entry<K,V>` → `Java\Util\Map\Entry` (interface)

| Java Method | PHP Method | File | Notes |
|---|---|---|---|
| `getKey()` | `getKey(): mixed` | `Map/Entry.php` | Identical |
| `getValue()` | `getValue(): mixed` | `Map/Entry.php` | Identical |
| `setValue(V)` | `setValue(mixed): mixed` | `Map/Entry.php` | Throws on immutable entries |
| `equals(Object)` | `equals(mixed): bool` | `Map/Entry.php` | Identical contract |
| `hashCode()` | `hashCode(): int` | `Map/Entry.php` | `crc32(serialize(key)) ^ crc32(serialize(value))` |
| `Map.Entry.comparingByKey()` | `EntryComparators::comparingByKey()` | `Map/EntryComparators.php` | ⚠️ Moved to utility class |
| `Map.Entry.comparingByValue()` | `EntryComparators::comparingByValue()` | `Map/EntryComparators.php` | ⚠️ Moved to utility class |
| `Map.Entry.comparingByKey(cmp)` | `EntryComparators::comparingByKeyWith(callable)` | `Map/EntryComparators.php` | ⚠️ Name change for clarity |
| `Map.Entry.comparingByValue(cmp)` | `EntryComparators::comparingByValueWith(callable)` | `Map/EntryComparators.php` | ⚠️ Name change for clarity |
| `Map.Entry.copyOf(entry)` | `Maps::entryCopyOf(entry)` | `Maps.php` | ⚠️ Moved to `Maps` factory |

### Concrete Implementations

| Java Class | PHP Class | File | Notes |
|---|---|---|---|
| `java.util.HashMap` | `Java\Util\HashMap` | `HashMap.php` | Backed by PHP native array |
| `java.util.AbstractMap` | `Java\Util\AbstractMap` | `AbstractMap.php` | Uses `MapDefaults` trait |
| `java.util.AbstractMap.SimpleEntry` | `Java\Util\Map\SimpleEntry` | `Map/SimpleEntry.php` | Mutable entry |
| `java.util.AbstractMap.SimpleImmutableEntry` | `Java\Util\Map\SimpleImmutableEntry` | `Map/SimpleEntry.php` | PHP 8.1 `readonly` props |
| `java.util.ImmutableCollections$MapN` | `Java\Util\ImmutableMap` | `ImmutableMap.php` | Result of `Maps::of*()` |

---

## Quick Start

```php
<?php

use Java\Util\Map\EntryComparators;use Java\Util\Map\HashMap;use Java\Util\Map\Maps;

// --- Mutable HashMap ---
$map = new HashMap();
$map->put('language', 'PHP');
$map->put('version', '8.1');

echo $map->get('language');          // PHP
echo $map->size();                   // 2
echo $map->containsKey('version');   // true

// Default methods
$map->forEach(fn($k, $v) => print "$k=$v\n");

$map->merge('score', 1, fn($old, $new) => $old + $new); // 1
$map->merge('score', 1, fn($old, $new) => $old + $new); // 2

$upper = new HashMap();
$map->forEach(fn($k, $v) => $upper->put($k, strtoupper((string)$v)));

// --- Immutable Maps (Map.of() equivalent) ---
$immutable = Maps::ofPairs('a', 1, 'b', 2, 'c', 3);
echo $immutable->get('b');   // 2
$immutable->put('d', 4);     // throws RuntimeException

// --- ofEntries pattern ---
$map2 = Maps::ofEntries(
    Maps::entry('host', 'localhost'),
    Maps::entry('port', 5432),
);

// --- Copy of mutable map ---
$snapshot = Maps::copyOf($map);

// --- Sorting entries by key ---

$entries = $map->entrySet();
usort($entries, EntryComparators::comparingByKey());

// --- computeIfAbsent for multi-value map ---
$graph = new HashMap();
$addEdge = fn(string $from, string $to) =>
    $graph->computeIfAbsent($from, fn($k) => []) &&
    $graph->put($from, [...$graph->get($from), $to]);

$addEdge('A', 'B');
$addEdge('A', 'C');
// $graph->get('A') === ['B', 'C']
```

---

## Running Tests & QA

```bash
# Unit tests
composer test

# PHPStan static analysis (level 8)
composer analyse

# Code style check (PSR-12)
composer cs-check

# Fix code style
composer cs-fix

# All QA checks
composer qa
```

---

## Design Decisions

### 1. `Entry` is an abstract class, not an interface
Java's `Map.Entry` is an interface with static method *bodies* (Java 8+).
PHP interfaces cannot carry implementations.
Solution: `Entry` is an `abstract class` — it declares the same abstract instance methods
(same contract) and holds the static factory methods directly.
Consequence: concrete entries `extend Entry` instead of `implement Entry`.
This is the minimal change that enables `Map\Entry::comparingByKey()`.

Java interfaces allow `default` method bodies (Java 8+). PHP interfaces do not.
Solution: `MapDefaults` trait holds all default implementations; `AbstractMap` consumes it via `use MapDefaults`. Any class can directly `use MapDefaults` too.

### 2. Method Overloading → Distinct Names
Java's `remove(key)` and `remove(key, value)` are two overloaded signatures.
PHP has no overloading. Resolution:
- `remove($key)` → same as Java's `remove(Object key)`
- `removeEntry($key, $value)` → same as Java's `remove(Object key, Object value)`
- Same pattern for `replace` / `replaceEntry`

### 3. Static Interface Methods → `Maps` Utility Class
Java's `Map.of()`, `Map.entry()`, `Map.copyOf()` are static methods on the interface.
PHP interfaces cannot provide implementations.
Resolution: `Maps` utility class with all static factories.

### 4. No Generics → `mixed` + PHPDoc `@template`
PHP has no runtime generics. All `<K,V>` become `mixed`. IDE and PHPStan support
is provided via `@template K`, `@template V` and `@param K`, `@return V` PHPDoc.

### 5. No Live-Backed Views
Java's `keySet()`, `values()`, `entrySet()` return views backed by the map — mutations
to the view propagate to the map and vice versa.
PHP cannot provide this natively without deep proxy machinery.
All three methods return **array snapshots**. Document and test this boundary.

### 6. Object Keys → Serialized Hash
Java `HashMap` uses `hashCode()` + `equals()` for key lookup.
PHP arrays support only `string|int` keys.
Resolution: non-scalar keys are mapped to `md5(serialize($key))`. This means:
- Two `==`-equal objects share the same internal key ✅
- Mutable objects used as keys that change after insertion break lookup ⚠️ (same as Java's documented caveat)

### 7. `hashCode()` → `crc32(serialize())`
Java's `Object.hashCode()` is JVM-internal. PHP replacement: `crc32(serialize())`.
This is portable and deterministic for the same process, but NOT stable across:
- PHP version changes
- Object property reordering
- Different `serialize()` implementations
For production hash-map use, override `hashCode()` in your own types.

---

## Limitations and Alternatives

| Java Feature | PHP Limitation | Practical Alternative |
|---|---|---|
| Threading / `ConcurrentHashMap` | PHP is single-threaded per request | Use Redis, APCu, or `Swoole\Table` for shared state |
| JVM `hashCode()` identity | No object identity hash in PHP | Override `hashCode()` in domain objects |
| Live-backed Set/Collection views | Not natively possible | Manually sync or use event-driven wrappers |
| Weak references in `WeakHashMap` | PHP 8.0+ has `WeakMap` natively | Use `\WeakMap` directly |
| `TreeMap` (sorted) | PHP `usort` on entrySet | Implement `SortedMap` with sorted-array backing |
| `LinkedHashMap` (insertion order) | PHP arrays preserve insertion order | `HashMap` already has this behavior for free |
| Serialization | `serialize()` / `unserialize()` | Direct PHP serialization works |

---

## Synchronization Checklist (keeping in sync with Java source)

- [ ] When new default methods are added to `java.util.Map`, add signatures to `Map.php` and implementations to `MapDefaults.php`
- [ ] When `Map.Entry` gains new static methods, add them to `EntryComparators.php`
- [ ] New static factory arities (e.g., `Map.of()` up to 10) → add variants in `Maps::ofPairs()`
- [ ] Track Java version: `since 1.8` → already ported; `since 9/10/17` → check `Maps.php` coverage
- [ ] Run `composer qa` on every Java source change review
- [ ] PHPStan level 8 must stay green

---

## Publishing to Packagist

1. Push to GitHub: `git push origin main`
2. Go to [packagist.org](https://packagist.org) → Submit
3. Enter your GitHub repository URL
4. Add a webhook for auto-updates (Packagist dashboard → Profile → API Token)

Tag releases following SemVer:
```bash
git tag v1.0.0
git push origin v1.0.0
```

---

## License

GPL-2.0-only — same as the original Java source (OpenJDK).
