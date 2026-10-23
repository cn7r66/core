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

    private const int REPLACEMENT_CODE_POINT = 0xFFFD;

    private const string REPLACEMENT_CHARACTER = "\u{FFFD}";

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
        $this->parameters = ['value' => $this->formatValue($value)];

        foreach (array_keys($parameters) as $name) {
            if (is_int($name)) {
                throw new InvalidArgumentException('Expected parameters to be named. Got positional parameter.');
            }

            if (preg_match('/^[A-Za-z0-9_.]+$/', $name) !== 1) {
                throw new InvalidArgumentException(sprintf(
                    'Expected parameter name to match [A-Za-z0-9_.]. Got %s.',
                    $this->formatParameter($name),
                ));
            }

            $this->parameters[$name] = $this->formatParameter($parameters[$name]);
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

    private function formatValue(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => 'bool ' . $this->formatBool($value),
            is_int($value) => 'int ' . $this->formatInt($value),
            is_float($value) => 'float ' . $this->formatFloat($value),
            is_string($value) => $this->formatTypedString($value),
            is_array($value) => $this->formatCount($value) . ' ' . $this->formatList($value),
            $value instanceof UnitEnum => 'enum ' . $this->formatEnum($value),
            is_object($value) => 'object ' . $this->formatObject($value),
            default => $this->formatResource($value),
        };
    }

    private function formatParameter(mixed $parameter): string
    {
        return match (true) {
            $parameter === null => 'null',
            is_bool($parameter) => $this->formatBool($parameter),
            is_int($parameter) => $this->formatInt($parameter),
            is_float($parameter) => $this->formatFloat($parameter),
            is_string($parameter) => $this->formatString(...$this->cut($parameter)),
            is_array($parameter) => $this->formatList($parameter),
            $parameter instanceof UnitEnum => $this->formatEnum($parameter),
            is_object($parameter) => $this->formatObject($parameter),
            default => $this->formatResource($parameter),
        };
    }

    private function formatBool(bool $value): string
    {
        return $value ? 'true' : 'false';
    }

    private function formatInt(int $value): string
    {
        return (string) $value;
    }

    private function formatFloat(float $value): string
    {
        return var_export($value, true);
    }

    private function formatTypedString(string $value): string
    {
        [$head, $count] = $this->cut($value);

        return ($count > self::MAX_GRAPHEMES ? 'string(' . $count . ') ' : 'string ')
            . $this->formatString($head, $count);
    }

    private function formatString(string $head, int $count): string
    {
        $quoted = '"' . $this->escape($head) . '"';

        if ($count > self::MAX_GRAPHEMES) {
            $quoted .= '…';
        }

        return $quoted;
    }

    /** @param array<mixed> $values */
    private function formatList(array $values): string
    {
        $shown  = [];
        $length = 0;
        /** @var mixed $element */
        foreach ($values as $element) {
            $formatted = is_array($element) ? $this->formatCount($element) : $this->formatParameter($element);
            $length   += ($shown === [] ? 0 : 2) + $this->length($formatted);

            if ($shown !== [] && (count($shown) >= self::MAX_ELEMENTS || $length > self::MAX_LIST_LENGTH)) {
                break;
            }

            $shown[] = $formatted;
        }

        if (count($shown) < count($values)) {
            $shown[] = '… (' . count($values) . ' total)';
        }

        return '[' . implode(', ', $shown) . ']';
    }

    /** @param array<mixed> $values */
    private function formatCount(array $values): string
    {
        return 'array(' . count($values) . ')';
    }

    private function formatEnum(UnitEnum $enum): string
    {
        return $enum::class . '::' . $enum->name;
    }

    private function formatObject(object $object): string
    {
        return get_debug_type($object);
    }

    /** "resource (stream)", "resource (closed)" */
    private function formatResource(mixed $resource): string
    {
        return get_debug_type($resource);
    }

    /** @return array{string, int} the first MAX_GRAPHEMES graphemes and the total grapheme count */
    private function cut(string $value): array
    {
        $scrubbed = $this->scrub($value);
        $length   = $this->length($scrubbed);

        if (preg_match('/^\X{0,' . self::MAX_GRAPHEMES . '}/u', $scrubbed, $head) !== 1) {
            return [self::REPLACEMENT_CHARACTER, $length];
        }

        return [$head[0], $length];
    }

    /** Counts without building a match array: the value is untrusted and may be arbitrarily large. */
    private function length(string $value): int
    {
        return (int) preg_match_all('/\X/u', $value);
    }

    /** Invalid UTF-8 becomes U+FFFD; the default substitute "?" would be indistinguishable from a real one. */
    private function scrub(string $value): string
    {
        $substitute = mb_substitute_character();
        mb_substitute_character(self::REPLACEMENT_CODE_POINT);

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
            return self::REPLACEMENT_CHARACTER;
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

    private function escapeCodePoint(int|false $codePoint): string
    {
        if ($codePoint === false) {
            return '';
        }

        return $codePoint < 0x80 ? sprintf('\x%02X', $codePoint) : sprintf('\u{%04X}', $codePoint);
    }
}
