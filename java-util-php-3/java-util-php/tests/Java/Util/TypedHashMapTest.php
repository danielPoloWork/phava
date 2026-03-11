<?php

declare(strict_types=1);

namespace Tests\Java\Util;

use Java\Lang\Type;use Java\Util\Generics\GenericType;use Java\Util\Generics\TypeRef;use Java\Util\Generics\UnionTypeRef;use Java\Util\Map\HashMap;use PHPUnit\Framework\Attributes\{CoversClass,DataProvider,Test};use PHPUnit\Framework\TestCase;

// ── Classi di dominio per i test ─────────────────────────────────────────────

class User      { public function __construct(public readonly string $name) {} }
class AdminUser extends User {}   // sottoclasse — testa covarianza
class UserId    { public function __construct(public readonly int $id) {} }
class OrderId   { public function __construct(public readonly int $id) {} }

interface Identifiable { public function getId(): int; }
class ProductId implements Identifiable
{
    public function __construct(private int $id) {}
    public function getId(): int { return $this->id; }
}

// ─────────────────────────────────────────────────────────────────────────────

#[CoversClass(HashMap::class)]
#[CoversClass(Type::class)]
#[CoversClass(TypeRef::class)]
#[CoversClass(UnionTypeRef::class)]
#[CoversClass(GenericType::class)]
{
    // =========================================================================
    // FORMA 1 — Enum scalare: Type::String, Type::Int, …
    // =========================================================================

    #[Test]
    public function form1_enumScalar_acceptsCorrectTypes(): void
    {
        $map = new HashMap(Type::String, Type::Int);

        $map->put('alice', 100);
        $map->put('bob',   200);

        self::assertSame(100, $map->get('alice'));
    }

    #[Test]
    public function form1_enumScalar_rejectsWrongKeyType(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Key must be of type string, got int');

        (new HashMap(Type::String, Type::Int))->put(42, 100);
    }

    #[Test]
    public function form1_enumScalar_rejectsWrongValueType(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Value must be of type int, got string');

        (new HashMap(Type::String, Type::Int))->put('k', 'not-int');
    }

    // =========================================================================
    // FORMA 2 — FQCN stringa diretta: UserId::class
    //           La forma più idiomatica per classi di dominio
    // =========================================================================

    #[Test]
    public function form2_fqcnString_acceptsMatchingInstances(): void
    {
        // Forma diretta — nessun Type::of() necessario
        $map = new HashMap(UserId::class, User::class);

        $map->put(new UserId(1), new User('Alice'));
        $map->put(new UserId(2), new User('Bob'));

        self::assertSame('Alice', $map->get(new UserId(1))->name);
    }

    #[Test]
    public function form2_fqcnString_rejectsWrongClass(): void
    {
        $map = new HashMap(UserId::class, User::class);

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Key must be of type UserId');

        $map->put(new OrderId(99), new User('X')); // OrderId ≠ UserId
    }

    #[Test]
    public function form2_fqcnString_acceptsSubclass_covariance(): void
    {
        // Map<User, ?> deve accettare AdminUser (sottoclasse di User)
        // Esattamente come in Java con ereditarietà
        $map = new HashMap(User::class, Type::String);

        $map->put(new User('Alice'),      'regular');
        $map->put(new AdminUser('Admin'), 'admin');   // ← sottoclasse: OK

        self::assertSame(2, $map->size());
    }

    #[Test]
    public function form2_fqcnString_acceptsInterfaceImplementors(): void
    {
        // Map<Identifiable, ?> accetta qualsiasi classe che implementa Identifiable
        $map = new HashMap(Identifiable::class, Type::String);

        $map->put(new ProductId(1), 'product'); // ProductId implements Identifiable

        self::assertSame('product', $map->get(new ProductId(1)));
    }

    #[Test]
    public function form2_fqcnString_throwsOnUnknownClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"App\NonExistent" is not a known class or interface');

        new HashMap('App\NonExistent', Type::Mixed); // errore nel costruttore
    }

    // =========================================================================
    // FORMA 3 — TypeRef esplicito: Type::of(FQCN) o new TypeRef(FQCN)
    // =========================================================================

    #[Test]
    public function form3_typeRef_viaTypeOf(): void
    {
        // Equivalente a forma 2 ma esplicito
        $map = new HashMap(
            Type::of(UserId::class),   // = TypeRef
            Type::of(User::class)
        );

        $map->put(new UserId(1), new User('Alice'));
        self::assertSame('Alice', $map->get(new UserId(1))->name);
    }

    #[Test]
    public function form3_typeRef_viaDirect(): void
    {
        $map = new HashMap(
            new TypeRef(UserId::class),
            new TypeRef(User::class)
        );

        $map->put(new UserId(3), new User('Charlie'));
        self::assertSame('Charlie', $map->get(new UserId(3))->name);
    }

    #[Test]
    public function form3_mixed_scalar_and_class(): void
    {
        // Chiave: stringa scalare; valore: classe di dominio
        $map = new HashMap(Type::String, Type::of(User::class));

        $map->put('alice', new User('Alice'));

        self::assertInstanceOf(User::class, $map->get('alice'));
    }

    // =========================================================================
    // FORMA 4 — Union type: Type::union(...)
    //           Specifico di PHP 8.0+, non esiste in Java
    // =========================================================================

    #[Test]
    public function form4_union_scalarTypes_acceptsBothTypes(): void
    {
        // Chiave: int|string — accetta entrambi
        $map = new HashMap(Type::union(Type::Int, Type::String), Type::Mixed);

        $map->put(1,       'int-key');
        $map->put('hello', 'string-key');

        self::assertSame(2, $map->size());
    }

    #[Test]
    public function form4_union_scalarTypes_rejectsOtherType(): void
    {
        $map = new HashMap(Type::union(Type::Int, Type::String), Type::Mixed);

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Key must be of type int|string');

        $map->put(3.14, 'float-not-allowed'); // float non è in int|string
    }

    #[Test]
    public function form4_union_classTypes_acceptsBothClasses(): void
    {
        // Chiave: UserId|OrderId — accetta entrambi
        $map = new HashMap(
            Type::union(UserId::class, OrderId::class),
            Type::String
        );

        $map->put(new UserId(1),  'user');
        $map->put(new OrderId(2), 'order');

        self::assertSame(2, $map->size());
    }

    #[Test]
    public function form4_union_classTypes_rejectsThirdClass(): void
    {
        $map = new HashMap(
            Type::union(UserId::class, OrderId::class),
            Type::Mixed
        );

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Key must be of type UserId|OrderId');

        $map->put(new ProductId(99), 'not-allowed');
    }

    #[Test]
    public function form4_union_requiresAtLeastTwoTypes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('requires at least 2 types');

        Type::union(Type::Int); // solo un tipo — non è una union
    }

    // =========================================================================
    // Default: nessun tipo → Type::Mixed su entrambi
    // =========================================================================

    #[Test]
    public function noType_mixedAcceptsAnything(): void
    {
        $map = new HashMap(); // nessun vincolo

        $map->put('string',       42);
        $map->put(99,             'hello');
        $map->put(new UserId(1),  new User('X'));
        $map->put(true,           [1, 2, 3]);

        self::assertSame(4, $map->size());
    }

    // =========================================================================
    // Label e messaggi di errore
    // =========================================================================

    #[Test]
    public function labelIsReadableForAllForms(): void
    {
        $cases = [
            [new HashMap(Type::String, Type::Int),                       'string', 'int'],
            [new HashMap(UserId::class, User::class),                    'UserId', 'User'],
            [new HashMap(Type::union(Type::Int, Type::String), Type::Mixed), 'int|string', 'mixed'],
        ];

        foreach ($cases as [$map, $expectedKey, $expectedVal]) {
            self::assertSame($expectedKey, $map->getKeyType()->label(),   "key label");
            self::assertSame($expectedVal, $map->getValueType()->label(), "value label");
        }
    }

    // =========================================================================
    // GenericType::equals — consistenza tra forme equivalenti
    // =========================================================================

    #[Test]
    public function form2and3_areEquivalent(): void
    {
        $viaString = (new HashMap(UserId::class, Type::Mixed))->getKeyType();
        $viaTypeOf = (new HashMap(Type::of(UserId::class), Type::Mixed))->getKeyType();
        $viaDirect = (new HashMap(new TypeRef(UserId::class), Type::Mixed))->getKeyType();

        self::assertTrue($viaString->equals($viaTypeOf));
        self::assertTrue($viaString->equals($viaDirect));
    }

    #[Test]
    public function unionTypeEquality(): void
    {
        $u1 = (new HashMap(Type::union(Type::Int, Type::String), Type::Mixed))->getKeyType();
        $u2 = (new HashMap(Type::union(Type::Int, Type::String), Type::Mixed))->getKeyType();
        $u3 = (new HashMap(Type::union(Type::Bool, Type::String), Type::Mixed))->getKeyType();

        self::assertTrue($u1->equals($u2));
        self::assertFalse($u1->equals($u3));
    }

    // =========================================================================
    // GenericType introspection
    // =========================================================================
    // Nuovi casi Type enum — tutti i tipi PHP
    // =========================================================================

    #[Test]
    public function typeNull_acceptsOnlyNull(): void
    {
        $map = new HashMap(Type::String, Type::Null);
        $map->put('deleted', null);  // ✅ solo null come valore

        self::assertNull($map->get('deleted'));
    }

    #[Test]
    public function typeNull_rejectsNonNull(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Value must be of type null');

        (new HashMap(Type::String, Type::Null))->put('k', 0);
    }

    #[Test]
    public function typeResource_acceptsResource(): void
    {
        $map = new HashMap(Type::String, Type::Resource);
        $fh  = fopen('php://memory', 'r');

        $map->put('handle', $fh);
        self::assertIsResource($map->get('handle'));

        fclose($fh);
    }

    #[Test]
    public function typeResource_rejectsNonResource(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Value must be of type resource');

        (new HashMap(Type::String, Type::Resource))->put('k', 'not-a-resource');
    }

    #[Test]
    public function typeCallable_acceptsCallables(): void
    {
        $map = new HashMap(Type::String, Type::Callable);

        $map->put('closure',  fn() => 42);
        $map->put('function', 'strlen');
        $map->put('static',   [self::class, 'someStaticHelper']);

        self::assertSame(3, $map->size());
    }

    public static function someStaticHelper(): void {}  // usato nel test sopra

    #[Test]
    public function typeCallable_rejectsNonCallable(): void
    {
        $this->expectException(\TypeError::class);

        (new HashMap(Type::String, Type::Callable))->put('k', 'notAFunctionName%%');
    }

    #[Test]
    public function typeIterable_acceptsArrayAndTraversable(): void
    {
        $map = new HashMap(Type::String, Type::Iterable);

        $map->put('array',       [1, 2, 3]);
        $map->put('generator',   (fn() => yield 1)());
        $map->put('arraObj',     new \ArrayObject([4, 5]));

        self::assertSame(3, $map->size());
    }

    #[Test]
    public function typeIterable_rejectsScalar(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Value must be of type iterable');

        (new HashMap(Type::String, Type::Iterable))->put('k', 42);
    }

    #[Test]
    public function typeLiteralTrue_acceptsOnlyTrue(): void
    {
        $map = new HashMap(Type::String, Type::True);

        $map->put('confirmed', true);  // ✅

        $this->expectException(\TypeError::class);
        $map->put('no', false);        // ❌ false non è true
    }

    #[Test]
    public function typeLiteralFalse_acceptsOnlyFalse(): void
    {
        $map = new HashMap(Type::String, Type::False);

        $map->put('denied', false);    // ✅

        $this->expectException(\TypeError::class);
        $map->put('yes', true);        // ❌ true non è false
    }

    #[Test]
    public function typeLiteralTrue_rejectsBoolTrue_as1(): void
    {
        // PHP strict: 1 non è true
        $this->expectException(\TypeError::class);

        (new HashMap(Type::String, Type::True))->put('k', 1);
    }

    #[Test]
    public function typeVoid_throwsLogicExceptionOnValidate(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Void cannot be used as a data type');

        $map = new HashMap(Type::String, Type::Void);
        $map->put('k', null);  // lancia LogicException dentro validate()
    }

    #[Test]
    public function typeNever_throwsLogicExceptionOnValidate(): void
    {
        $this->expectException(\LogicException::class);

        $map = new HashMap(Type::String, Type::Never);
        $map->put('k', null);
    }

    #[Test]
    public function typeIsUsableAsDataType_filtersVoidAndNever(): void
    {
        // Tutti i tipi tranne Void e Never sono usabili come tipo di dato
        $usable = array_filter(
            Type::cases(),
            fn(Type $t) => $t->isUsableAsDataType()
        );

        self::assertNotContains(Type::Void,  $usable);
        self::assertNotContains(Type::Never, $usable);
        self::assertContains(Type::Null,     array_values($usable));
        self::assertContains(Type::Mixed,    array_values($usable));
    }

    #[Test]
    public function typeIsScalar_identifiesScalarSubset(): void
    {
        self::assertTrue(Type::Int->isScalar());
        self::assertTrue(Type::Float->isScalar());
        self::assertTrue(Type::String->isScalar());
        self::assertTrue(Type::Bool->isScalar());

        self::assertFalse(Type::Array->isScalar());
        self::assertFalse(Type::Object->isScalar());
        self::assertFalse(Type::Mixed->isScalar());
        self::assertFalse(Type::Null->isScalar());
    }

    #[Test]
    public function allTypeLabelsMatchPhpTypeNames(): void
    {
        // I valori dell'enum devono corrispondere alle stringhe
        // che PHP usa in get_debug_type() e negli errori
        $expected = [
            'int', 'float', 'string', 'bool', 'array',
            'object', 'iterable', 'callable', 'null',
            'resource', 'mixed', 'void', 'never', 'true', 'false',
        ];

        $actual = array_map(fn(Type $t) => $t->label(), Type::cases());

        foreach ($expected as $label) {
            self::assertContains($label, $actual, "Missing type label: $label");
        }
    }
}

