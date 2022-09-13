<?php

declare(strict_types=1);

namespace FuzzyWuzzy;

use ArrayAccess;
use ArrayIterator;
use Closure;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * Collection provides an array-like interface for working with a set of elements.
 *
 * @author Michael Crumm <mike@crumm.net>
 *
 * @psalm-template T
 */
class Collection implements ArrayAccess, IteratorAggregate, Countable
{
    /**
     * @var T[] $elements
     * @psalm-var T[] $elements
     */
    private array $elements;

    /**
     * Collection Constructor.
     *
     * @param mixed[] $elements
     * @psalm-param T[] $elements
     */
    public function __construct(array $elements = [])
    {
        $this->elements = $elements;
    }

    /**
     * Adds an element to this collection.
     *
     * @param mixed $element Elements can be of any type.
     * @psalm-param T $element Elements can be of any type.
     */
    public function add(mixed $element): void
    {
        $this->elements[] = $element;
    }

    /**
     * Returns true if the given elements exists in this collection.
     *
     * @param mixed $element
     * @psalm-param T $element
     * @return boolean
     */
    public function contains(mixed $element): bool
    {
        return in_array($element, $this->elements, true);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->elements);
    }

    /**
     * Returns the set difference of this Collection and another comparable.
     *
     * @param array|Traversable $cmp Value to compare against.
     * @psalm-param T[]|Traversable $cmp Value to compare against.
     * @return self
     * @psalm-return self<T>
     * @throws InvalidArgumentException When $cmp is not a valid for
     * difference.
     */
    public function difference(array|Traversable $cmp): self
    {
        return new self(array_diff($this->elements, self::coerce($cmp)->toArray()));
    }

    /**
     * @param Closure $p
     * @return self
     * @psalm-return self<T>
     */
    public function filter(Closure $p): self
    {
        return new self(array_filter($this->elements, $p));
    }

    /**
     * @return ArrayIterator
     * @psalm-return ArrayIterator<array-key, T>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->elements);
    }

    /**
     * Returns the set intersection of this Collection and another comparable.
     *
     * @param array|Traversable $cmp Value to compare against.
     * @psalm-param T[]|Traversable $cmp Value to compare against.
     * @return self
     * @psalm-return self<T>
     * @throws InvalidArgumentException When $cmp is not a valid for
     * intersection.
     */
    public function intersection(array|Traversable $cmp): self
    {
        return new self(array_intersect($this->elements, self::coerce($cmp)->toArray()));
    }

    /**
     * Checks whether or not this collection is empty.
     *
     * @return boolean
     */
    public function isEmpty(): bool
    {
        return empty($this->elements);
    }

    /**
     * Returns a string containing all elements of this collection with a
     * glue string.
     *
     * @param string $glue
     * @return string A string representation of all the array elements in the
     * same order, with the glue string between each element.
     */
    public function join(string $glue = ' '): string
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        return implode($glue, $this->elements);
    }

    /**
     * Returns a new collection, the values of which are the result of mapping
     * the predicate function onto each element in this collection.
     *
     * @param Closure $p Predicate function.
     * @return self
     * @psalm-template MappedT
     * @psalm-param Closure(T): MappedT $p
     * @psalm-return self<MappedT>
     */
    public function map(Closure $p): self
    {
        return new self(array_map($p, $this->elements));
    }

    /**
     * Apply a multisort to this collection of elements.
     *
     * @param mixed $arg [optional]
     * @param mixed $_ [optional]
     * @return self
     * @psalm-return self<T>
     */
    public function multiSort(mixed ...$args): self
    {
        if (func_num_args() < 1) {
            throw new \LogicException('multiSort requires at least one argument.');
        }

        $elements = $this->elements;
        $args[]   = &$elements;

        call_user_func_array('array_multisort', $args);

        return new self($elements);
    }

    /**
     * @param int $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->elements[$offset]);
    }

    /**
     * @param int $offset
     * @return mixed|null
     * @psalm-return T|null
     */
    public function offsetGet(mixed $offset): mixed
    {
        return isset($this->elements[$offset]) ? $this->elements[$offset] : null;
    }

    /**
     * @param int|null $offset
     * @param mixed $value
     * @psalm-param T $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!is_null($offset)) {
            $this->elements[$offset] = $value;
            return;
        }

        $this->elements[] = $value;
    }

    /**
     * @param int $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->elements[$offset]);
    }

    /**
     * Returns a new collection with the elements of this collection, reversed.
     *
     * @return self
     * @psalm-return self<T>
     */
    public function reverse(): self
    {
        return new self(array_reverse($this->elements));
    }

    /**
     * @param int $offset
     * @param int|null $length
     * @return self
     * @psalm-return self<T>
     */
    public function slice(int $offset, ?int $length = null)
    {
        return new self(array_slice($this->elements, $offset, $length, true));
    }

    /**
     * Returns a new collection with the elements of this collection, sorted.
     *
     * @return self
     * @psalm-return self<T>
     */
    public function sort(): self
    {
        $sorted = $this->elements;

        sort($sorted);

        return new self($sorted);
    }

    /**
     * Returns the elements in this collection as an array.
     *
     * @return array
     * @psalm-return T[]
     */
    public function toArray(): array
    {
        return $this->elements;
    }

    /**
     * Coerce an array-like value into a Collection.
     *
     * @psalm-template TNew
     * @param TNew[]|Traversable $elements    Value to compare against.
     * @return self
     * @psalm-return self<TNew>
     */
    public static function coerce(array|Traversable $elements): self
    {
        if ($elements instanceof Collection) {
            return $elements;
        } elseif ($elements instanceof Traversable) {
            $elements = iterator_to_array($elements);
        }

        return new self($elements);
    }
}
