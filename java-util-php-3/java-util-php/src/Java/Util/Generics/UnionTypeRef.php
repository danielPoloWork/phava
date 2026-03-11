<?php

declare(strict_types=1);

namespace Java\Util\Generics;

use Java\Lang\Type;

/**
 * Rappresenta un union type PHP 8.0+ come parametro generico runtime.
 *
 * Namespace Java\Util\Generics: condiviso tra tutte le collection tipizzate.
 *
 * Non ha equivalente diretto in Java. In Java si userebbero:
 *   - upper bounded wildcard: <? extends BaseType>   → usa TypeRef con tipo base
 *   - Object come tipo comune                        → usa Type::Mixed
 *   - overloading                                    → non applicabile a generics
 *
 * Costruito tramite Type::union():
 *   Type::union(Type::Int, Type::String)
 *   Type::union(UserId::class, AdminId::class)
 *   Type::union(Type::Int, UserId::class, Type::Null)  // int|UserId|null
 *
 * @see \Java\Lang\Type::union()
 * @see GenericType
 */
final class UnionTypeRef
{
    /** @var array<Type|TypeRef> */
    private readonly array $types;

    /**
     * @param array<Type|TypeRef> $types almeno 2 elementi
     * @throws \InvalidArgumentException se meno di 2 tipi
     */
    public function __construct(array $types)
    {
        if (count($types) < 2) {
            throw new \InvalidArgumentException(
                'UnionTypeRef requires at least 2 types.'
            );
        }
        $this->types = array_values($types);
    }

    /**
     * Valida il valore contro l'unione — OR logico.
     * Il valore è valido se soddisfa ALMENO UNO dei tipi.
     */
    public function validate(mixed $value): bool
    {
        foreach ($this->types as $type) {
            if ($type->validate($value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 'int|string', 'UserId|AdminId', 'int|UserId|null'
     */
    public function label(): string
    {
        return implode('|', array_map(
            fn (Type|TypeRef $t): string => $t->label(),
            $this->types
        ));
    }

    /** @return array<Type|TypeRef> */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function equals(self $other): bool
    {
        if (count($this->types) !== count($other->types)) {
            return false;
        }

        foreach ($this->types as $i => $type) {
            $otherType = $other->types[$i];

            $same = match (true) {
                $type instanceof Type && $otherType instanceof Type
                    => $type === $otherType,
                $type instanceof TypeRef && $otherType instanceof TypeRef
                    => $type->equals($otherType),
                default => false,
            };

            if (!$same) {
                return false;
            }
        }

        return true;
    }

    public function __toString(): string
    {
        return $this->label();
    }
}
