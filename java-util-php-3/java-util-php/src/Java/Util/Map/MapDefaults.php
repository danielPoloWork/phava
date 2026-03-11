<?php

declare(strict_types=1);

namespace Java\Util\Map;

/**
 * PHP Trait providing default implementations for all Java Map "default" methods.
 *
 * In Java, Map declares these as "default" methods on the interface (Java 8+).
 * Since PHP interfaces cannot carry method bodies, this trait is consumed by
 * {@see AbstractMap} and optionally by any class implementing {@see Map} directly.
 *
 * Requirements for the consuming class:
 *   - Must implement: get(), put(), remove(), containsKey(), entrySet()
 *
 * @see Map
 * @see AbstractMap
 */
trait MapDefaults
{
    // Consuming class MUST satisfy this contract
    abstract public function get(mixed $key): mixed;
    abstract public function put(mixed $key, mixed $value): mixed;
    abstract public function remove(mixed $key): mixed;
    abstract public function containsKey(mixed $key): bool;

    /**
     * @return array<Entry>
     */
    abstract public function entrySet(): array;

    // -------------------------------------------------------------------------
    // Default method implementations
    // -------------------------------------------------------------------------

    public function getOrDefault(mixed $key, mixed $defaultValue): mixed
    {
        $v = $this->get($key);
        return ($v !== null || $this->containsKey($key)) ? $v : $defaultValue;
    }

    /**
     * @param callable(mixed, mixed): void $action
     */
    public function forEach(callable $action): void
    {
        foreach ($this->entrySet() as $entry) {
            $action($entry->getKey(), $entry->getValue());
        }
    }

    /**
     * @param callable(mixed, mixed): mixed $function
     */
    public function replaceAll(callable $function): void
    {
        foreach ($this->entrySet() as $entry) {
            $entry->setValue($function($entry->getKey(), $entry->getValue()));
        }
    }

    public function putIfAbsent(mixed $key, mixed $value): mixed
    {
        $v = $this->get($key);
        if ($v === null) {
            $v = $this->put($key, $value);
        }
        return $v;
    }

    /**
     * ⚠️  Java API difference: Java's remove(key, value) overload.
     */
    public function removeEntry(mixed $key, mixed $value): bool
    {
        $curValue = $this->get($key);
        if ($curValue !== $value && $curValue != $value) {
            return false;
        }
        if ($curValue === null && !$this->containsKey($key)) {
            return false;
        }
        $this->remove($key);
        return true;
    }

    /**
     * ⚠️  Java API difference: Java's replace(key, oldValue, newValue) overload.
     */
    public function replaceEntry(mixed $key, mixed $oldValue, mixed $newValue): bool
    {
        $curValue = $this->get($key);
        if ($curValue != $oldValue || ($curValue === null && !$this->containsKey($key))) {
            return false;
        }
        $this->put($key, $newValue);
        return true;
    }

    public function replace(mixed $key, mixed $value): mixed
    {
        $curValue = null;
        if (($curValue = $this->get($key)) !== null || $this->containsKey($key)) {
            $curValue = $this->put($key, $value);
        }
        return $curValue;
    }

    /**
     * @param callable(mixed): mixed $mappingFunction
     */
    public function computeIfAbsent(mixed $key, callable $mappingFunction): mixed
    {
        $v = $this->get($key);
        if ($v === null) {
            $newValue = $mappingFunction($key);
            if ($newValue !== null) {
                $this->put($key, $newValue);
                return $newValue;
            }
        }
        return $v;
    }

    /**
     * @param callable(mixed, mixed): mixed $remappingFunction
     */
    public function computeIfPresent(mixed $key, callable $remappingFunction): mixed
    {
        $oldValue = $this->get($key);
        if ($oldValue !== null) {
            $newValue = $remappingFunction($key, $oldValue);
            if ($newValue !== null) {
                $this->put($key, $newValue);
                return $newValue;
            } else {
                $this->remove($key);
                return null;
            }
        }
        return null;
    }

    /**
     * @param callable(mixed, mixed|null): mixed $remappingFunction
     */
    public function compute(mixed $key, callable $remappingFunction): mixed
    {
        $oldValue = $this->get($key);
        $newValue = $remappingFunction($key, $oldValue);

        if ($newValue === null) {
            if ($oldValue !== null || $this->containsKey($key)) {
                $this->remove($key);
            }
            return null;
        } else {
            $this->put($key, $newValue);
            return $newValue;
        }
    }

    /**
     * @param callable(mixed, mixed): mixed $remappingFunction
     */
    public function merge(mixed $key, mixed $value, callable $remappingFunction): mixed
    {
        if ($value === null) {
            throw new \InvalidArgumentException('merge() value must not be null.');
        }

        $oldValue = $this->get($key);
        $newValue = ($oldValue === null) ? $value : $remappingFunction($oldValue, $value);

        if ($newValue === null) {
            $this->remove($key);
        } else {
            $this->put($key, $newValue);
        }
        return $newValue;
    }
}
