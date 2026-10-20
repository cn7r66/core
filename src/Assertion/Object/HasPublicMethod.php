<?php

declare(strict_types=1);

namespace Vivarium\Assertion\Object;

use ReflectionClass;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\Conditional\Either;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\String\IsEmpty;
use Vivarium\Assertion\Type\IsClassOrInterface;
use Vivarium\Assertion\Var\IsObject;
use Vivarium\Type\Type;

use function sprintf;

/** @template-implements Assertion<class-string|object> */
final class HasPublicMethod implements Assertion
{
    public function __construct(private string $method)
    {
    }

    /** @psalm-assert class-string $value */
    public function assert(mixed $value, string $message = ''): void
    {
        if (! $this($value)) {
            $message = sprintf(
                ! (new IsEmpty())($message) ?
                    $message : 'Expected %s to have a public method named %2$s.',
                Type::toLiteral($value),
                Type::toLiteral($this->method),
            );

            throw new AssertionFailed($message);
        }
    }

    /** @psalm-assert-if-true class-string $value */
    public function __invoke(mixed $value): bool
    {
        (new Either(
            new IsClassOrInterface(),
            new IsObject(),
        ))->assert($value, 'Value must be either class, interface or object. Got %s');

        $reflector = new ReflectionClass($value);

        return $reflector->hasMethod($this->method)
            && $reflector->getMethod($this->method)->isPublic();
    }
}
