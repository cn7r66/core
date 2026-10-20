<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Container\Exception;

use RuntimeException;
use Vivarium\Container\Binding;

use function array_map;
use function implode;
use function sprintf;

final class AmbiguousInjection extends RuntimeException implements ContainerException
{
    /** @param non-empty-list<Binding> $candidates Registered bindings of the competing registrations, at least two. */
    public function __construct(private string $slot, private array $candidates)
    {
        parent::__construct(
            sprintf(
                'Ambiguous injection for "%s": registered on %s. Register it on a common subtype to resolve.',
                $slot,
                implode(', ', array_map(static fn (Binding $binding): string => (string) $binding, $candidates)),
            ),
        );
    }

    public function getSlot(): string
    {
        return $this->slot;
    }

    /** @return non-empty-list<Binding> */
    public function getCandidates(): array
    {
        return $this->candidates;
    }
}
