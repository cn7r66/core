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
use Vivarium\Type\Exception\NotAnIntersection;

use function array_all;
use function array_any;
use function array_diff_assoc;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function strcmp;
use function usort;

final class Intersection implements Type
{
    /** @var list<Clazz> */
    private array $members;

    public function __construct(Type ...$members)
    {
        $members = $this->classes(array_values($members));

        $this->assertAtLeastTwoMembers($members);
        $this->assertNoDuplicates($members);

        usort($members, static fn (Clazz $left, Clazz $right): int => strcmp((string) $left, (string) $right));

        $this->members = $members;
    }

    /** The canonical spelling of members joined in an intersection: a union member is parenthesized. */
    public static function format(Type ...$members): string
    {
        return implode('&', array_map(
            static fn (Type $member): string => $member instanceof Union ?
                '(' . (string) $member . ')' : (string) $member,
            $members,
        ));
    }

    /** @return list<Clazz> */
    public function getMembers(): array
    {
        return $this->members;
    }

    /**
     * Whether this intersection names every class of $other, so PHP rejects the two side by side in
     * a union. The check is on names, as PHP's is, not on subtyping.
     */
    public function isRedundantWith(Clazz|Intersection $other): bool
    {
        $others = $other instanceof Intersection ? $other->getMembers() : [$other];

        return array_all($others, fn (Clazz $class): bool => array_any($this->members, $class->equals(...)));
    }

    #[Override]
    public function accepts(Type $type): bool
    {
        if ($type instanceof Union) {
            return array_all($type->getMembers(), fn (Type $member): bool => $this->accepts($member));
        }

        return array_all($this->members, static fn (Clazz $member): bool => $member->accepts($type));
    }

    #[Override]
    public function acceptsVar(mixed $var): bool
    {
        return array_all($this->members, static fn (Clazz $member): bool => $member->acceptsVar($var));
    }

    #[Override]
    public function __toString(): string
    {
        return self::format(...$this->members);
    }

    #[Override]
    public function equals(object $object): bool
    {
        if (! $object instanceof Intersection) {
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
     * @param list<Type> $members
     *
     * @return list<Clazz>
     */
    private function classes(array $members): array
    {
        $classes = [];
        foreach ($members as $member) {
            if (! $member instanceof Clazz) {
                throw new NotAnIntersection(
                    $members,
                    '{member} cannot be part of an intersection',
                    member: (string) $member,
                );
            }

            $classes[] = $member;
        }

        return $classes;
    }

    /** @param list<Clazz> $members */
    private function assertAtLeastTwoMembers(array $members): void
    {
        if (count($members) < 2) {
            throw new NotAnIntersection($members, 'an intersection needs at least two members');
        }
    }

    /** @param list<Clazz> $members */
    private function assertNoDuplicates(array $members): void
    {
        $names      = array_map(static fn (Clazz $member): string => (string) $member, $members);
        $duplicates = array_values(array_diff_assoc($names, array_unique($names)));
        if ($duplicates !== []) {
            throw new NotAnIntersection($members, 'duplicate type {member}', member: $duplicates[0]);
        }
    }
}
