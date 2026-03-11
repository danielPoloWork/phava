<?php

declare(strict_types=1);

namespace Java\Util\Generics;

use Java\Lang\Type;

/**
 * Token unificato che rappresenta un parametro generico runtime.
 *
 * Incapsula Type | TypeRef | UnionTypeRef in un unico oggetto
 * con interfaccia uniforme (validate + label).
 *
 * È il valore che TypedMap/TypedList memorizzano internamente.
 * I client non lo costruiscono mai direttamente — viene creato da
 * Type::resolve() nel costruttore delle collection tipizzate.
 *
 * ┌───────────────────────────────────────────────────────────────────┐
 * │ Input nel costruttore           → GenericType                     │
 * │───────────────────────────────────────────────────────────────────│
 * │ Type::String                    → of(Type::String)    scalar      │
 * │ Type::Null                      → of(Type::Null)      scalar      │
 * │ UserId::class  (stringa FQCN)   → ofClass(TypeRef)    class       │
 * │ Type::of(UserId::class)         → ofClass(TypeRef)    class       │
 * │ new TypeRef(UserId::class)      → ofClass(TypeRef)    class       │
 * │ Type::union(Int, String)        → ofUnion(UnionTypeRef)  union     │
 * └───────────────────────────────────────────────────────────────────┘
 *
 * @see \Java\Lang\Type::resolve()
 * @see \Java\Util\Map\TypedMap
 */
final class GenericType
{
    private readonly Type|TypeRef|UnionTypeRef $inner;

    private function __construct(Type|TypeRef|UnionTypeRef $inner)
    {
        $this->inner = $inner;
    }

    // ── Factory ───────────────────────────────────────────────────────────────

    public static function of(Type $type): self
    {
        return new self($type);
    }

    public static function ofClass(TypeRef $ref): self
    {
        return new self($ref);
    }

    public static function ofUnion(UnionTypeRef $union): self
    {
        return new self($union);
    }

    /** Normalizza qualsiasi input — chiamato da Type::resolve(). */
    public static function wrap(Type|TypeRef|UnionTypeRef $typeOrRef): self
    {
        return new self($typeOrRef);
    }

    // ── Core ──────────────────────────────────────────────────────────────────

    public function validate(mixed $value): bool
    {
        return $this->inner->validate($value);
    }

    public function label(): string
    {
        return $this->inner->label();
    }

    // ── Introspection ─────────────────────────────────────────────────────────

    public function isScalar(): bool
    {
        return $this->inner instanceof Type;
    }

    public function isClass(): bool
    {
        return $this->inner instanceof TypeRef;
    }

    public function isUnion(): bool
    {
        return $this->inner instanceof UnionTypeRef;
    }

    /** FQCN se il tipo è una classe, null altrimenti. */
    public function getFqcn(): ?string
    {
        return $this->inner instanceof TypeRef
            ? $this->inner->getFqcn()
            : null;
    }

    /** Tipi componenti se è una union, array vuoto altrimenti. */
    public function getUnionTypes(): array
    {
        return $this->inner instanceof UnionTypeRef
            ? $this->inner->getTypes()
            : [];
    }

    /** Il Type enum caso se è scalare, null altrimenti. */
    public function getScalarType(): ?Type
    {
        return $this->inner instanceof Type ? $this->inner : null;
    }

    public function equals(self $other): bool
    {
        return match (true) {
            $this->inner instanceof Type && $other->inner instanceof Type
                => $this->inner === $other->inner,
            $this->inner instanceof TypeRef && $other->inner instanceof TypeRef
                => $this->inner->equals($other->inner),
            $this->inner instanceof UnionTypeRef && $other->inner instanceof UnionTypeRef
                => $this->inner->equals($other->inner),
            default => false,
        };
    }

    public function __toString(): string
    {
        return $this->label();
    }
}
