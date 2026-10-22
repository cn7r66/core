<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Assertion;

use InvalidArgumentException;
use UnitEnum;

use function array_fill_keys;
use function array_keys;
use function array_slice;
use function count;
use function get_debug_type;
use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_object;
use function is_string;
use function mb_ord;
use function mb_scrub;
use function mb_strlen;
use function mb_substitute_character;
use function preg_match;
use function preg_match_all;
use function preg_replace_callback;
use function sprintf;
use function strtr;
use function var_export;

final class Failure
{
    private const string REDACTED = '[redacted]';

    private const int MAX_GRAPHEMES = 50;

    private const int MAX_ELEMENTS = 5;

    private const int MAX_LIST_LENGTH = 80;

    private const int REPLACEMENT_CHARACTER = 0xFFFD;

    private const string ENCODING = 'UTF-8';

    /**
     * Quote, the named controls, every other invisible or layout-changing character
     * (Cc, Cf except ZWNJ/ZWJ, Zl, Zp, Zs except the plain space).
     */
    private const string ESCAPED = '/["\n\r\t]|(?![\x{200C}\x{200D}])[\p{Cc}\p{Cf}\p{Zl}\p{Zp}]|(?! )\p{Zs}/u';

    /** @var array<string, string> */
    private array $parameters;

    public function __construct(
        private string $template,
        mixed $value,
        mixed ...$parameters,
    ) {
        $this->parameters = ['value' => $this->renderValue($value)];

        foreach (array_keys($parameters) as $name) {
            if (is_int($name)) {
                throw new InvalidArgumentException('Expected parameters to be named. Got positional parameter.');
            }

            if (preg_match('/^[A-Za-z0-9_.]+$/', $name) !== 1) {
                throw new InvalidArgumentException(sprintf(
                    'Expected parameter name to match [A-Za-z0-9_.]. Got %s.',
                    $this->renderParameter($name),
                ));
            }

            $this->parameters[$name] = $this->renderParameter($parameters[$name]);
        }
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    /** @return array<string, string> */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function withTemplate(string $template): self
    {
        $failure           = clone $this;
        $failure->template = $template;

        return $failure;
    }

    public function redacted(): self
    {
        $failure             = clone $this;
        $failure->parameters = array_fill_keys(array_keys($this->parameters), self::REDACTED);

        return $failure;
    }

    public function format(): string
    {
        $map = [];
        foreach ($this->parameters as $name => $parameter) {
            $map['{' . $name . '}'] = $parameter;
        }

        return strtr($this->template, $map);
    }

    private function renderValue(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => 'bool ' . ($value ? 'true' : 'false'),
            is_int($value) => 'int ' . $value,
            is_float($value) => 'float ' . var_export($value, true),
            is_string($value) => $this->renderString($value, true),
            is_array($value) => 'array(' . count($value) . ')',
            $value instanceof UnitEnum => 'enum ' . $this->renderEnum($value),
            default => $this->renderOther($value),
        };
    }

    private function renderParameter(mixed $parameter): string
    {
        return match (true) {
            $parameter === null => 'null',
            is_bool($parameter) => $parameter ? 'true' : 'false',
            is_int($parameter) => (string) $parameter,
            is_float($parameter) => var_export($parameter, true),
            is_string($parameter) => $this->renderString($parameter, false),
            is_array($parameter) => $this->renderList($parameter),
            $parameter instanceof UnitEnum => $this->renderEnum($parameter),
            default => $this->renderOther($parameter),
        };
    }

    private function renderEnum(UnitEnum $enum): string
    {
        return $enum::class . '::' . $enum->name;
    }

    private function renderOther(mixed $value): string
    {
        if (is_object($value)) {
            return 'object "' . get_debug_type($value) . '"';
        }

        // resource (stream), resource (closed)
        return get_debug_type($value);
    }

    private function renderString(string $value, bool $typed): string
    {
        preg_match_all('/\X/u', $this->scrub($value), $matches);

        $count  = count($matches[0]);
        $cut    = $count > self::MAX_GRAPHEMES;
        $quoted = '"' . $this->escape(implode('', array_slice($matches[0], 0, self::MAX_GRAPHEMES))) . '"';

        if ($cut) {
            $quoted .= '…';
        }

        if (! $typed) {
            return $quoted;
        }

        return ($cut ? 'string(' . $count . ') ' : 'string ') . $quoted;
    }

    /** @param array<mixed> $values */
    private function renderList(array $values): string
    {
        $shown  = [];
        $length = 0;
        foreach (array_keys($values) as $key) {
            $rendered = $this->renderElement($values[$key]);
            $length  += ($shown === [] ? 0 : 2) + mb_strlen($rendered, self::ENCODING);

            if ($shown !== [] && (count($shown) >= self::MAX_ELEMENTS || $length > self::MAX_LIST_LENGTH)) {
                break;
            }

            $shown[] = $rendered;
        }

        if (count($shown) < count($values)) {
            $shown[] = '… (' . count($values) . ' total)';
        }

        return '[' . implode(', ', $shown) . ']';
    }

    private function renderElement(mixed $element): string
    {
        return match (true) {
            is_array($element) => 'array(' . count($element) . ')',
            is_object($element) => $this->renderValue($element),
            default => $this->renderParameter($element),
        };
    }

    /** Invalid UTF-8 becomes U+FFFD; the default substitute "?" would be indistinguishable from a real one. */
    private function scrub(string $value): string
    {
        $substitute = mb_substitute_character();
        mb_substitute_character(self::REPLACEMENT_CHARACTER);

        try {
            return mb_scrub($value, self::ENCODING);
        } finally {
            mb_substitute_character($substitute);
        }
    }

    private function escape(string $value): string
    {
        $escaped = preg_replace_callback(self::ESCAPED, $this->escapeCharacter(...), $value);

        if ($escaped === null) {
            return $value;
        }

        return $escaped;
    }

    /** @param array<array-key, string> $match */
    private function escapeCharacter(array $match): string
    {
        $character = $match[0];

        return match ($character) {
            '"' => '\"',
            "\n" => '\n',
            "\r" => '\r',
            "\t" => '\t',
            default => $this->escapeCodePoint(mb_ord($character, self::ENCODING)),
        };
    }

    /** Psalm types mb_ord() as int|false, PHPStan as int; the scrubbed input never yields false. */
    private function escapeCodePoint(int|false $codePoint): string
    {
        if ($codePoint === false) {
            return '';
        }

        return $codePoint < 0x80 ? sprintf('\x%02X', $codePoint) : sprintf('\u{%04X}', $codePoint);
    }
}
