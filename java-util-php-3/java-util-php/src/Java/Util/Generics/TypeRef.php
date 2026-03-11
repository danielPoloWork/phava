<?php

declare(strict_types=1);

namespace Java\Util\Generics;

/**
 * Rappresenta un riferimento a una classe o interfaccia specifica
 * come parametro generico runtime.
 *
 * Namespace Java\Util\Generics: infrastruttura shared tra tutte le
 * collection tipizzate (Map, List, Set, Queue...).
 *
 * Non istanziare direttamente nei client — usare:
 *   Type::of(UserId::class)         → TypeRef
 *   new HashMap(UserId::class, ...) → risolto automaticamente via Type::resolve()
 *
 * COVARIANZA: validate() usa instanceof, quindi accetta automaticamente:
 *   - sottoclassi  (AdminUser extends User)
 *   - implementazioni di interfaccia (ProductId implements Identifiable)
 *
 * @see \Java\Lang\Type::of()
 * @see GenericType
 */
final class TypeRef
{
    public function __construct(
        private readonly string $fqcn
    ) {}

    /**
     * Valida il valore contro questo tipo via instanceof.
     * Accetta sottoclassi e implementazioni di interfaccia (covarianza).
     */
    public function validate(mixed $value): bool
    {
        return $value instanceof $this->fqcn;
    }

    /**
     * Nome breve della classe per messaggi di errore leggibili.
     * 'App\Domain\Model\UserId' → 'UserId'
     */
    public function label(): string
    {
        $parts = explode('\\', $this->fqcn);
        return end($parts);
    }

    public function getFqcn(): string
    {
        return $this->fqcn;
    }

    public function equals(self $other): bool
    {
        return $this->fqcn === $other->fqcn;
    }

    public function __toString(): string
    {
        return $this->label();
    }
}
