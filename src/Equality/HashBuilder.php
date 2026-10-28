<?php

/**
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Equality;

use function count;
use function get_resource_id;
use function hash;
use function is_array;
use function is_float;
use function is_object;
use function is_resource;
use function is_scalar;
use function serialize;
use function spl_object_id;

/**
 * Hash codes are valid in the current process only: an object without Equality hashes by its
 * id, which PHP reuses once the object is freed. Never persist them.
 */
final class HashBuilder
{
    private const string ALGORITHM = 'xxh128';

    private string $state;

    public function __construct()
    {
        $this->state = '';
    }

    /** @param mixed $value */
    public function append($value): HashBuilder
    {
        $builder        = clone $this;
        $builder->state = hash(self::ALGORITHM, $this->state . $this->encode($value), true);

        return $builder;
    }

    public function getHashCode(): string
    {
        return hash(self::ALGORITHM, $this->state);
    }

    private function encode(mixed $value): string
    {
        if (is_array($value)) {
            return $this->encodeEach($value);
        }

        if ($value instanceof Equality) {
            return 'e' . serialize($value->hash());
        }

        if (is_object($value)) {
            return 'o' . spl_object_id($value) . ';';
        }

        // -0.0 === 0.0, but serialize() tells them apart
        if (is_float($value) && $value === 0.0) {
            return serialize(0.0);
        }

        if (is_scalar($value) || $value === null) {
            return serialize($value);
        }

        if (is_resource($value)) {
            return 'r' . get_resource_id($value) . ';';
        }

        // a closed resource: is_resource() reports false, so all of them hash alike
        return 'c;';
    }

    /** @param array<mixed> $array */
    private function encodeEach(array $array): string
    {
        $encoded = 'a' . count($array) . '{';
        foreach ($array as $key => $element) {
            $encoded .= $this->encode($key) . $this->encode($element);
        }

        return $encoded . '}';
    }
}
