<?php

/**
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Equality;

use function array_keys;
use function is_array;
use function is_float;
use function is_object;
use function serialize;
use function sha1;
use function spl_object_id;

final class HashBuilder
{
    private string $total;

    public function __construct()
    {
        $this->total = '';
    }

    /** @param mixed $value */
    public function append($value): HashBuilder
    {
        if (is_array($value)) {
            return $this->appendEach($value);
        }

        if (is_object($value)) {
            return $this->appendObject($value);
        }

        // -0.0 === 0.0, but serialize() tells them apart
        if (is_float($value) && $value === 0.0) {
            $value = 0.0;
        }

        $builder         = clone $this;
        $builder->total .= serialize($value);

        return $builder;
    }

    public function getHashCode(): string
    {
        return sha1($this->total);
    }

    /** @param mixed[] $array */
    private function appendEach(array $array): HashBuilder
    {
        $builder = clone $this;
        foreach (array_keys($array) as $key) {
            $builder = $builder->append($key);
            $builder = $builder->append($array[$key]);
        }

        return $builder;
    }

    private function appendObject(object $object): HashBuilder
    {
        $builder         = clone $this;
        $builder->total .= $object instanceof Equality ?
            $object->hash() : serialize(spl_object_id($object));

        return $builder;
    }
}
