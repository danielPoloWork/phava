<?php

declare(strict_types=1);

namespace Java\Lang;

use Java\Util\Generics\GenericType;
use Java\Util\Generics\TypeRef;
use Java\Util\Generics\UnionTypeRef;

/**
 * Enum di tutti i tipi del sistema PHP, usabile come parametro generico runtime.
 *
 * Namespace Java\Lang rispecchia java.lang di JDK 25:
 * il package fondamentale contenente i tipi di base del linguaggio.
 * In Java è importato implicitamente; in PHP si importa con:
 *   use Java\Lang\Type;
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * CATEGORIZZAZIONE (specchiata su PHP 8.3+ type system)
 * ─────────────────────────────────────────────────────────────────────────────
 *
 *  SCALARI          int, float, string, bool
 *  COMPOUND         array, object, iterable, callable
 *  SPECIALI         null, resource
 *  PSEUDO-TIPI      mixed, void, never    ← solo per documentazione/validazione
 *  LITERAL TYPES    true, false           ← PHP 8.2+ standalone types
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * UTILIZZO come parametro generico
 * ─────────────────────────────────────────────────────────────────────────────
 *
 *   // Scalari
 *   new HashMap(Type::String, Type::Int)
 *
 *   // Classe di dominio (via factory)
 *   new HashMap(Type::of(UserId::class), Type::of(UserProfile::class))
 *   new HashMap(UserId::class, UserProfile::class)  ← forma diretta equivalente
 *
 *   // Union type PHP 8.0+
 *   new HashMap(Type::union(Type::Int, Type::String), Type::Mixed)
 *
 * @see \Java\Util\Generics\TypeRef       per classi/interfacce specifiche
 * @see \Java\Util\Generics\UnionTypeRef  per union types
 * @see \Java\Util\Generics\GenericType   token unificato
 */
enum Type: string
{
    // ── Scalari ──────────────────────────────────────────────────────────────

    /** Intero nativo PHP. Java: int / Integer */
    case Int      = 'int';

    /** Floating point. Java: float / double / Float / Double */
    case Float    = 'float';

    /** Stringa. Java: String */
    case String   = 'string';

    /** Booleano. Java: boolean / Boolean */
    case Bool     = 'bool';

    // ── Compound ─────────────────────────────────────────────────────────────

    /**
     * Array nativo PHP.
     * Non ha equivalente diretto in Java (più vicino a array[] o List<T>).
     */
    case Array    = 'array';

    /**
     * Qualsiasi oggetto senza vincolo di classe.
     * Java: Object
     */
    case Object   = 'object';

    /**
     * Qualsiasi array o oggetto che implementa \Traversable.
     * Java: Iterable<T>
     * ⚠️ Non usare come tipo generico di chiave (non è hashable uniformemente).
     */
    case Iterable = 'iterable';

    /**
     * Callable: funzione, closure, array [object, method], stringa 'functionName'.
     * Java: più vicino a java.util.function.Function<T,R> o Runnable.
     * ⚠️ La validazione usa is_callable() che esegue lookup nel runtime.
     */
    case Callable = 'callable';

    // ── Speciali ─────────────────────────────────────────────────────────────

    /**
     * Valore null.
     * Java: null reference (non è un tipo distinto nel type system Java).
     * ⚠️ Come tipo generico accetta SOLO null. Usare Type::Mixed per nullable.
     */
    case Null     = 'null';

    /**
     * Risorsa PHP (file handle, connessione DB, ecc.).
     * Java: non ha equivalente diretto (usa AutoCloseable / InputStream / ecc.).
     * ⚠️ Le risorse non sono serializzabili né hashable in modo stabile.
     */
    case Resource = 'resource';

    // ── Pseudo-tipi ──────────────────────────────────────────────────────────

    /**
     * Nessun vincolo di tipo. Wildcard universale.
     * Java: Object / <?> wildcard
     * Come parametro generico: accetta qualsiasi valore incluso null.
     */
    case Mixed    = 'mixed';

    /**
     * Assenza di valore di ritorno.
     * Java: void
     * ⚠️ NON usare come tipo di chiave o valore in una Map —
     *    semanticamente privo di senso. Incluso per completezza del type system.
     *    validate() lancia UnsupportedTypeException.
     */
    case Void     = 'void';

    /**
     * Funzione che non ritorna mai (lancia sempre eccezione o loop infinito).
     * Java: non ha equivalente (Void wrapper class è diverso).
     * ⚠️ NON usare come tipo generico in Map/List.
     *    validate() lancia UnsupportedTypeException.
     */
    case Never    = 'never';

    // ── Literal types (PHP 8.2+) ─────────────────────────────────────────────

    /**
     * Literal true — accetta SOLO il valore true (non qualsiasi bool).
     * Java: non ha equivalente (i literal types non esistono in Java).
     * PHP 8.2+ standalone type.
     */
    case True     = 'true';

    /**
     * Literal false — accetta SOLO il valore false (non qualsiasi bool).
     * Java: non ha equivalente.
     * PHP 8.2+ standalone type.
     */
    case False    = 'false';

    // =========================================================================
    // Factory methods — entry-point per classi, interfacce e union types
    // =========================================================================

    /**
     * Normalizza qualsiasi forma di tipo in un GenericType uniforme.
     * Chiamato automaticamente dal costruttore di TypedMap e TypedList.
     *
     * @param Type|TypeRef|UnionTypeRef|string $type
     *   - Type         → tipo scalare/pseudo
     *   - string       → FQCN classe o interfaccia (UserId::class)
     *   - TypeRef      → già un class reference
     *   - UnionTypeRef → già una union
     *
     * @throws \InvalidArgumentException se la stringa non è un FQCN valido
     */
    public static function resolve(
        self|TypeRef|UnionTypeRef|string $type
    ): GenericType {
        return match (true) {
            $type instanceof self         => GenericType::of($type),
            $type instanceof TypeRef      => GenericType::ofClass($type),
            $type instanceof UnionTypeRef => GenericType::ofUnion($type),
            is_string($type)              => GenericType::ofClass(self::classRef($type)),
        };
    }

    /**
     * Crea un TypeRef per una classe o interfaccia specifica.
     *
     *   Java:  Map<UserId, UserProfile>
     *   PHP:   new HashMap(Type::of(UserId::class), Type::of(UserProfile::class))
     *       o: new HashMap(UserId::class, UserProfile::class)  ← forma diretta
     *
     * @throws \InvalidArgumentException se il FQCN non esiste
     */
    public static function of(string $fqcn): TypeRef
    {
        return self::classRef($fqcn);
    }

    /**
     * Crea un UnionTypeRef per union types PHP 8.0+.
     *
     *   Type::union(Type::Int, Type::String)
     *   Type::union(UserId::class, AdminId::class)
     *   Type::union(Type::Int, UserId::class, Type::Null)  // nullable union
     *
     * Java non ha equivalente diretto. Il più vicino è:
     *   - bounded wildcards: <? extends Number>  → usa TypeRef con classe base comune
     *   - Object come upper bound                → usa Type::Mixed
     *
     * @param Type|TypeRef|string ...$types  almeno 2 tipi
     * @throws \InvalidArgumentException se meno di 2 tipi o FQCN non valido
     */
    public static function union(self|TypeRef|string ...$types): UnionTypeRef
    {
        if (count($types) < 2) {
            throw new \InvalidArgumentException(
                'Type::union() requires at least 2 types.'
            );
        }

        $resolved = array_map(
            fn (self|TypeRef|string $t): self|TypeRef => match (true) {
                $t instanceof self    => $t,
                $t instanceof TypeRef => $t,
                is_string($t)         => self::classRef($t),
            },
            $types
        );

        return new UnionTypeRef($resolved);
    }

    // =========================================================================
    // Validazione
    // =========================================================================

    /**
     * Valida un valore contro questo tipo.
     *
     * @throws \LogicException per Void e Never (non usabili come tipi di dato)
     */
    public function validate(mixed $value): bool
    {
        return match ($this) {
            // Scalari
            self::Int      => is_int($value),
            self::Float    => is_float($value) || is_int($value), // coercion numerica
            self::String   => is_string($value),
            self::Bool     => is_bool($value),

            // Compound
            self::Array    => is_array($value),
            self::Object   => is_object($value),
            self::Iterable => is_array($value) || $value instanceof \Traversable,
            self::Callable => is_callable($value),

            // Speciali
            self::Null     => $value === null,
            self::Resource => is_resource($value),

            // Pseudo
            self::Mixed    => true,                    // accetta tutto
            self::Void,
            self::Never    => throw new \LogicException(
                sprintf(
                    'Type::%s cannot be used as a data type for map keys or values. '
                    . 'It is a return-type-only pseudo-type.',
                    $this->name
                )
            ),

            // Literal
            self::True     => $value === true,         // strict: solo true
            self::False    => $value === false,        // strict: solo false
        };
    }

    /**
     * Etichetta leggibile per messaggi di errore.
     * Restituisce il nome del tipo PHP (es. 'int', 'string', 'iterable').
     */
    public function label(): string
    {
        return $this->value;
    }

    /**
     * Indica se questo tipo può essere usato come chiave/valore in una Map.
     * Void e Never non sono tipi di dato — sono tipi di ritorno.
     */
    public function isUsableAsDataType(): bool
    {
        return !in_array($this, [self::Void, self::Never], true);
    }

    /**
     * Indica se questo tipo è un tipo scalare PHP.
     */
    public function isScalar(): bool
    {
        return in_array($this, [self::Int, self::Float, self::String, self::Bool], true);
    }

    // =========================================================================
    // Helpers privati
    // =========================================================================

    private static function classRef(string $fqcn): TypeRef
    {
        if (!class_exists($fqcn) && !interface_exists($fqcn)) {
            throw new \InvalidArgumentException(
                sprintf('"%s" is not a known class or interface.', $fqcn)
            );
        }
        return new TypeRef($fqcn);
    }
}
