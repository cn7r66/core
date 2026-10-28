<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Assertion\String;

use Override;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\BaseAssertion;
use Vivarium\Assertion\Encoding\IsEncoding;
use Vivarium\Assertion\Failure;
use Vivarium\Assertion\Numeric\IsGreaterThanOrEqualTo;
use Vivarium\Assertion\Var\IsString;

use function mb_strlen;

/** @template-extends BaseAssertion<mixed, string> */
final class HasLengthAtLeast extends BaseAssertion
{
    public function __construct(
        private readonly int $length,
        private readonly string $encoding = 'UTF-8',
    ) {
        (new IsGreaterThanOrEqualTo(0))
            ->assert($length, 'Expected length to be greater than or equal to 0. Got {value}.');

        (new IsEncoding())
            ->assert($encoding);
    }

    #[Override]
    protected function precondition(): Assertion
    {
        return new IsString();
    }

    #[Override]
    protected function condition(mixed $value): bool
    {
        return mb_strlen($value, $this->encoding) >= $this->length;
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure(
            'Expected value to have a length of at least {length}. Got {value}.',
            $value,
            length: $this->length,
        );
    }
}
