<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion;

use ArrayObject;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Failure;
use Vivarium\Test\Assertion\Stub\StubEnum;

use function fclose;
use function fopen;
use function memory_get_peak_usage;
use function memory_get_usage;
use function memory_reset_peak_usage;
use function range;
use function str_repeat;

use const INF;
use const NAN;

#[CoversClass(Failure::class)]
final class FailureTest extends TestCase
{
    #[DataProvider('provideInvalidParameterNames')]
    public function testConstructorRejectsInvalidParameterName(int|string $name): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Failure('{value}', 1, ...[$name => 1]);
    }

    public function testGetTemplate(): void
    {
        $failure = new Failure('Expected {value}.', 1);

        self::assertSame('Expected {value}.', $failure->getTemplate());
    }

    public function testGetParameters(): void
    {
        $failure = new Failure('{value} {name}', 1, name: 'x');

        self::assertSame(['value' => 'int 1', 'name' => '"x"'], $failure->getParameters());
    }

    public function testWithTemplate(): void
    {
        $failure = new Failure('old {value}', 1, name: 'x');

        $copy = $failure->withTemplate('new {name}');

        self::assertSame('new {name}', $copy->getTemplate());
        self::assertSame($failure->getParameters(), $copy->getParameters());
        self::assertSame('new "x"', $copy->format());
    }

    public function testWithTemplateLeavesOriginalUnchanged(): void
    {
        $failure = new Failure('old {value}', 1);

        $failure->withTemplate('new');

        self::assertSame('old {value}', $failure->getTemplate());
        self::assertSame('old int 1', $failure->format());
    }

    public function testRedacted(): void
    {
        $failure = new Failure('{value} {name}', 'secret', name: 'x');

        $redacted = $failure->redacted();

        self::assertSame('{value} {name}', $redacted->getTemplate());
        self::assertSame(['value' => '[redacted]', 'name' => '[redacted]'], $redacted->getParameters());
        self::assertSame('[redacted] [redacted]', $redacted->format());
    }

    public function testRedactedLeavesOriginalUnchanged(): void
    {
        $failure = new Failure('{value}', 'secret');

        $failure->redacted();

        self::assertSame(['value' => 'string "secret"'], $failure->getParameters());
    }

    public function testFormatLeavesUnknownPlaceholderLiteral(): void
    {
        $failure = new Failure('{value} {missing}', 1);

        self::assertSame('int 1 {missing}', $failure->format());
    }

    /** @param array<string, mixed> $parameters */
    #[DataProvider('provideSingleSubstitutionCases')]
    public function testFormatSubstitutesOnce(
        string $template,
        mixed $value,
        array $parameters,
        string $expected,
    ): void {
        $failure = new Failure($template, $value, ...$parameters);

        self::assertSame($expected, $failure->format());
    }

    #[DataProvider('provideLargeInputs')]
    public function testConstructorUsesBoundedMemory(mixed $value, mixed $parameter): void
    {
        memory_reset_peak_usage();
        $before = memory_get_usage();

        new Failure('{value} {parameter}', $value, parameter: $parameter);

        $growth = memory_get_peak_usage() - $before;

        self::assertLessThan(32 * 1024 * 1024, $growth);
    }

    #[DataProvider('provideRenderedValues')]
    public function testFormat(mixed $input, string $typed, string $plain): void
    {
        $failure = new Failure('{value}|{parameter}', $input, parameter: $input);

        self::assertSame($typed . '|' . $plain, $failure->format());
    }

    /** @return array<string, array{int|string}> */
    public static function provideInvalidParameterNames(): array
    {
        return [
            'positional' => [0],
            'space' => ['bad key'],
            'dash' => ['a-b'],
            'braces' => ['{x}'],
            'empty' => [''],
        ];
    }

    /** @return array<string, array{string, mixed, array<string, mixed>, string}> */
    public static function provideSingleSubstitutionCases(): array
    {
        return [
            'value contains parameter placeholder' => [
                '{value}|{other}',
                '{other}',
                ['other' => 'x'],
                'string "{other}"|"x"',
            ],
            'parameter contains value placeholder' => [
                '{other}|{value}',
                1,
                ['other' => '{value}'],
                '"{value}"|int 1',
            ],
        ];
    }

    /** @return array<string, array{mixed, mixed}> */
    public static function provideLargeInputs(): array
    {
        $large = str_repeat('a', 10 * 1024 * 1024);

        return [
            'large string value' => [$large, 1],
            'list parameter holding a large string' => [1, [$large]],
        ];
    }

    /** @return array<string, array{mixed, string, string}> */
    public static function provideRenderedValues(): array
    {
        $open   = fopen('php://memory', 'r');
        $closed = fopen('php://memory', 'r');
        fclose($closed);

        $enum = StubEnum::class . '::First';

        return [
            'null' => [null, 'null', 'null'],
            'bool' => [true, 'bool true', 'true'],
            'int' => [42, 'int 42', '42'],
            'float integral' => [5.0, 'float 5.0', '5.0'],
            'float negative zero' => [-0.0, 'float -0.0', '-0.0'],
            'float inexact sum' => [0.1 + 0.2, 'float 0.30000000000000004', '0.30000000000000004'],
            'float exponent' => [1e20, 'float 1.0E+20', '1.0E+20'],
            'float infinity' => [INF, 'float INF', 'INF'],
            'float not a number' => [NAN, 'float NAN', 'NAN'],
            'string plain' => ['abc', 'string "abc"', '"abc"'],
            'string empty' => ['', 'string ""', '""'],
            'string class name' => ['App\Foo\Bar', 'string "App\Foo\Bar"', '"App\Foo\Bar"'],
            'string double quote' => ['a"b', 'string "a\"b"', '"a\"b"'],
            'string newline' => ["a\nb", 'string "a\nb"', '"a\nb"'],
            'string carriage return' => ["a\rb", 'string "a\rb"', '"a\rb"'],
            'string tab' => ["a\tb", 'string "a\tb"', '"a\tb"'],
            'string control character' => ["a\x01b", 'string "a\x01b"', '"a\x01b"'],
            'string delete' => ["a\x7Fb", 'string "a\x7Fb"', '"a\x7Fb"'],
            'string bidi override' => ["a\u{202E}b", 'string "a\u{202E}b"', '"a\u{202E}b"'],
            'string zero width space' => ["a\u{200B}b", 'string "a\u{200B}b"', '"a\u{200B}b"'],
            'string no-break space' => ["a\u{00A0}b", 'string "a\u{00A0}b"', '"a\u{00A0}b"'],
            'string zero width joiner' => ["a\u{200D}b", "string \"a\u{200D}b\"", "\"a\u{200D}b\""],
            'string zero width non-joiner' => ["a\u{200C}b", "string \"a\u{200C}b\"", "\"a\u{200C}b\""],
            'string space' => ['a b', 'string "a b"', '"a b"'],
            'string invalid utf-8' => ["a\xFFb", "string \"a\u{FFFD}b\"", "\"a\u{FFFD}b\""],
            'string cut' => [
                str_repeat('a', 60),
                'string(60) "' . str_repeat('a', 50) . '"…',
                '"' . str_repeat('a', 50) . '"…',
            ],
            'string cut at grapheme boundary' => [
                str_repeat("e\u{0301}", 55),
                'string(55) "' . str_repeat("e\u{0301}", 50) . '"…',
                '"' . str_repeat("e\u{0301}", 50) . '"…',
            ],
            'array empty' => [[], 'array(0) []', '[]'],
            'array short list' => [['a', 'b'], 'array(2) ["a", "b"]', '["a", "b"]'],
            'array over element cap' => [
                range(1, 12),
                'array(12) [1, 2, 3, 4, 5, … (12 total)]',
                '[1, 2, 3, 4, 5, … (12 total)]',
            ],
            'array over grapheme cap' => [
                [str_repeat('a', 30), str_repeat('b', 30), str_repeat('c', 30)],
                'array(3) ["' . str_repeat('a', 30) . '", "' . str_repeat('b', 30) . '", … (3 total)]',
                '["' . str_repeat('a', 30) . '", "' . str_repeat('b', 30) . '", … (3 total)]',
            ],
            'array first element always shown' => [
                [str_repeat("\n", 50), 'b'],
                'array(2) ["' . str_repeat('\n', 50) . '", … (2 total)]',
                '["' . str_repeat('\n', 50) . '", … (2 total)]',
            ],
            'array nested array' => [[[1, 2]], 'array(1) [array(2)]', '[array(2)]'],
            'array object element' => [[new ArrayObject()], 'array(1) [ArrayObject]', '[ArrayObject]'],
            'array enum element' => [[StubEnum::First], 'array(1) [' . $enum . ']', '[' . $enum . ']'],
            'array null element' => [[null], 'array(1) [null]', '[null]'],
            'array float element' => [[1.5], 'array(1) [1.5]', '[1.5]'],
            'object' => [new ArrayObject(), 'object ArrayObject', 'ArrayObject'],
            'closure' => [static fn (): int => 1, 'object Closure', 'Closure'],
            'anonymous class' => [
                new class {
                },
                'object class@anonymous',
                'class@anonymous',
            ],
            'enum case' => [StubEnum::First, 'enum ' . $enum, $enum],
            'open resource' => [$open, 'resource (stream)', 'resource (stream)'],
            'closed resource' => [$closed, 'resource (closed)', 'resource (closed)'],
        ];
    }
}
