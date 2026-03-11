<?php

declare(strict_types=1);

namespace Java\Util\Map;

use Java\Lang\Type;
use Java\Util\Generics\GenericType;
use Java\Util\Generics\TypeRef;
use Java\Util\Generics\UnionTypeRef;

/**
 * Base class per collection tipizzate con runtime generic type enforcement.
 *
 * Estesa da HashMap, e in futuro da TypedList, TypedSet, ecc.
 * Il sistema di tipi risiede in Java\Util\Generics — condiviso da tutte.
 *
 * ─── Le 4 forme di specifica del tipo ────────────────────────────────────────
 *
 *   1. Enum scalare:
 *        new HashMap(Type::String, Type::Int)
 *        new HashMap(Type::Null,   Type::Bool)
 *
 *   2. FQCN stringa — forma più diretta per classi/interfacce:
 *        new HashMap(UserId::class, UserProfile::class)
 *
 *   3. TypeRef esplicito:
 *        new HashMap(Type::of(UserId::class), Type::Mixed)
 *
 *   4. Union type PHP 8.0+:
 *        new HashMap(Type::union(Type::Int, Type::String), Type::Mixed)
 *        new HashMap(Type::union(UserId::class, AdminId::class), Type::Mixed)
 *
 * ─────────────────────────────────────────────────────────────────────────────
 */
abstract class TypedMap implements Map
{
    use MapDefaults;

    private readonly GenericType $keyType;
    private readonly GenericType $valueType;

    public function __construct(
        Type|TypeRef|UnionTypeRef|string $keyType   = Type::Mixed,
        Type|TypeRef|UnionTypeRef|string $valueType = Type::Mixed,
    ) {
        $this->keyType   = Type::resolve($keyType);
        $this->valueType = Type::resolve($valueType);
    }

    // ─── Enforcement ─────────────────────────────────────────────────────────

    final protected function assertKeyType(mixed $key): void
    {
        if (!$this->keyType->validate($key)) {
            throw new \TypeError(sprintf(
                'Key must be of type %s, got %s.',
                $this->keyType->label(),
                get_debug_type($key)
            ));
        }
    }

    final protected function assertValueType(mixed $value): void
    {
        if (!$this->valueType->validate($value)) {
            throw new \TypeError(sprintf(
                'Value must be of type %s, got %s.',
                $this->valueType->label(),
                get_debug_type($value)
            ));
        }
    }

    final public function getKeyType(): GenericType   { return $this->keyType; }
    final public function getValueType(): GenericType { return $this->valueType; }
}
