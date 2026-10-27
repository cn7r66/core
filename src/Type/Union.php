<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Type;

use Override;
use Vivarium\Equality\EqualsBuilder;
use Vivarium\Equality\HashBuilder;
use Vivarium\Type\Exception\NotAUnion;

use function array_all;
use function array_any;
use function array_diff_assoc;
use function array_intersect;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function in_array;
use function sprintf;
use function strcmp;
use function usort;

final class Union implements Type
{
    private const array STANDALONE = [
        Primitive::MIXED,
        Primitive::NEVER,
        Primitive::VOID,
    ];

    /** @var list<Primitive|Clazz|Intersection> */
    private array $members;

    public function __construct(Type ...$members)
    {
        $members = $this->unionable(array_values($members));

        $this->assertAtLeastTwoMembers($members);

        $names = array_map(self::format(...), $members);

        $this->assertNoDuplicates($members, $names);
        $this->assertNoStandaloneType($members, $names);
        $this->assertNoRedundantTrueOrFalse($members, $names);
        $this->assertNotBothTrueAndFalse($members, $names);
        $this->assertNoRedundantObject($members, $names);
        $this->assertNoRedundantIntersection($members);

        usort($members, static fn (Type $left, Type $right): int => strcmp(self::format($left), self::format($right)));

        $this->members = $members;
    }

    /** The canonical spelling of members joined in a union: an intersection member is parenthesized. */
    public static function format(Type ...$members): string
    {
        return implode('|', array_map(
            static fn (Type $member): string => $member instanceof Intersection ?
                '(' . (string) $member . ')' : (string) $member,
            $members,
        ));
    }

    /** @return list<Primitive|Clazz|Intersection> */
    public function getMembers(): array
    {
        return $this->members;
    }

    #[Override]
    public function accepts(Type $type): bool
    {
        if ($type instanceof Union) {
            return array_all($type->getMembers(), fn (Type $member): bool => $this->accepts($member));
        }

        return array_any(
            $this->members,
            static fn (Type $member): bool => $member->accepts($type),
        );
    }

    #[Override]
    public function acceptsVar(mixed $var): bool
    {
        return array_any(
            $this->members,
            static fn (Type $member): bool => $member->acceptsVar($var),
        );
    }

    #[Override]
    public function __toString(): string
    {
        return self::format(...$this->members);
    }

    #[Override]
    public function equals(object $object): bool
    {
        if (! $object instanceof Union) {
            return false;
        }

        if ($object === $this) {
            return true;
        }

        return (new EqualsBuilder())
            ->append((string) $this, (string) $object)
            ->isEquals();
    }

    #[Override]
    public function hash(): string
    {
        return (new HashBuilder())
            ->append((string) $this)
            ->getHashCode();
    }

    /**
     * Nested unions are flattened: `iterable` expands to one.
     *
     * @param list<Type> $members
     *
     * @return list<Primitive|Clazz|Intersection>
     */
    private function unionable(array $members): array
    {
        $unionable = [];
        foreach ($members as $member) {
            if ($member instanceof Union) {
                $unionable = [...$unionable, ...$member->getMembers()];
                continue;
            }

            if (! $member instanceof Primitive && ! $member instanceof Clazz && ! $member instanceof Intersection) {
                throw new NotAUnion($members, sprintf('%s cannot be part of a union type', (string) $member));
            }

            $unionable[] = $member;
        }

        return $unionable;
    }

    /** @param list<Primitive|Clazz|Intersection> $members */
    private function assertAtLeastTwoMembers(array $members): void
    {
        if (count($members) < 2) {
            throw new NotAUnion($members, 'a union needs at least two members');
        }
    }

    /**
     * @param list<Primitive|Clazz|Intersection> $members
     * @param list<string>                       $names
     */
    private function assertNoDuplicates(array $members, array $names): void
    {
        $duplicates = array_values(array_diff_assoc($names, array_unique($names)));
        if ($duplicates !== []) {
            throw new NotAUnion($members, sprintf('duplicate type %s', $duplicates[0]));
        }
    }

    /**
     * @param list<Primitive|Clazz|Intersection> $members
     * @param list<string>                       $names
     */
    private function assertNoStandaloneType(array $members, array $names): void
    {
        $standalone = array_values(array_intersect(self::STANDALONE, $names));
        if ($standalone !== []) {
            throw new NotAUnion($members, sprintf('%s can only be used as a standalone type', $standalone[0]));
        }
    }

    /**
     * @param list<Primitive|Clazz|Intersection> $members
     * @param list<string>                       $names
     */
    private function assertNoRedundantTrueOrFalse(array $members, array $names): void
    {
        if (! in_array(Primitive::BOOL, $names, true)) {
            return;
        }

        $redundant = array_values(array_intersect([Primitive::TRUE, Primitive::FALSE], $names));
        if ($redundant !== []) {
            throw new NotAUnion($members, sprintf('%s is redundant with bool', $redundant[0]));
        }
    }

    /**
     * @param list<Primitive|Clazz|Intersection> $members
     * @param list<string>                       $names
     */
    private function assertNotBothTrueAndFalse(array $members, array $names): void
    {
        if (in_array(Primitive::TRUE, $names, true) && in_array(Primitive::FALSE, $names, true)) {
            throw new NotAUnion($members, 'contains both true and false, bool must be used instead');
        }
    }

    /**
     * @param list<Primitive|Clazz|Intersection> $members
     * @param list<string>                       $names
     */
    private function assertNoRedundantObject(array $members, array $names): void
    {
        if (! in_array(Primitive::OBJECT, $names, true)) {
            return;
        }

        if (array_any($members, static fn (Type $member): bool => ! $member instanceof Primitive)) {
            throw new NotAUnion($members, 'contains both object and a class type');
        }
    }

    /** @param list<Primitive|Clazz|Intersection> $members */
    private function assertNoRedundantIntersection(array $members): void
    {
        foreach ($members as $intersection) {
            if (! $intersection instanceof Intersection) {
                continue;
            }

            foreach ($members as $other) {
                if ($other === $intersection || $other instanceof Primitive) {
                    continue;
                }

                if ($intersection->isRedundantWith($other)) {
                    throw new NotAUnion($members, sprintf(
                        '%s is more restrictive than %s',
                        self::format($intersection),
                        self::format($other),
                    ));
                }
            }
        }
    }
}
