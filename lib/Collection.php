<?php

declare(strict_types=1);

namespace FuzzyWuzzy;

use ArrayAccess;
use ArrayIterator;
use Closure;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Collection provides an array-like interface for working with a set of elements.
 *
 * @author Michael Crumm <mike@crumm.net>
 */
class Collection implements ArrayAccess, IteratorAggregate, Countable
{
    /** @var mixed[] $elements */
    private array $elements;

    /**
     * Collection Constructor.
     *
     * @param mixed[] $elements
     */
    public function __construct(array $elements = [])
    {
        $this->elements = $elements;
    }

    /**
     * Adds an element to this collection.
     *
     * @param mixed $element Elements can be of any type.
     */
    public function add(mixed $element): void
    {
        $this->elements[] = $element;
    }

    /**
     * Returns true if the given elements exists in this collection.
     *
     * @param mixed $element
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
     * @return static
     * @throws \InvalidArgumentException When $cmp is not a valid for
     * difference.
     */
    public function difference(array|Traversable $cmp): static
    {
        return new static(array_diff($this->elements, static::coerce($cmp)->toArray()));
    }

    /**
     * @param callable $p
     * @return static
     */
    public function filter(Closure $p): static
    {
        return new static(array_filter($this->elements, $p));
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->elements);
    }

    /**
     * Returns the set intersection of this Collection and another comparable.
     *
     * @param array|Traversable $cmp Value to compare against.
     * @return static
     * @throws \InvalidArgumentException When $cmp is not a valid for
     * intersection.
     */
    public function intersection(array|Traversable $cmp): static
    {
        return new static(array_intersect($this->elements, static::coerce($cmp)->toArray()));
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
        return implode((string) $glue, $this->elements);
    }

    /**
     * Returns a new collection, the values of which are the result of mapping
     * the predicate function onto each element in this collection.
     *
     * @param Closure $p Predicate function.
     * @return static
     */
    public function map(Closure $p): static
    {
        return new static(array_map($p, $this->elements));
    }

    /**
     * Apply a multisort to this collection of elements.
     *
     * @param mixed $arg [optional]
     * @param mixed $arg [optional]
     * @param mixed $_ [optional]
     * @return static
     */
    public function multiSort(mixed ...$args): static
    {
        if (func_num_args() < 1) {
            throw new \LogicException('multiSort requires at least one argument.');
        }

        $elements = $this->elements;
        $args[]   = &$elements;

        call_user_func_array('array_multisort', $args);

        return new static($elements);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->elements[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return isset($this->elements[$offset]) ? $this->elements[$offset] : null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
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
     * @param mixed $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->elements[$offset]);
    }

    /**
     * Returns a new collection with the elements of this collection, reversed.
     *
     * @return static
     */
    public function reverse(): static
    {
        return new static(array_reverse($this->elements));
    }

    /**
     * @param mixed $offset
     * @param int|null $length
     * @return static
     */
    public function slice(mixed $offset, ?int $length = null)
    {
        return new static(array_slice($this->elements, $offset, $length, true));
    }

    /**
     * Returns a new collection with the elements of this collection, sorted.
     *
     * @return static
     */
    public function sort(): static
    {
        $sorted = $this->elements;

        sort($sorted);

        return new static($sorted);
    }

    /**
     * Returns the elements in this collection as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->elements;
    }

    /**
     * Coerce an array-like value into a Collection.
     *
     * @param array|Traversable $elements    Value to compare against.
     * @return Collection
     * @throws \InvalidArgumentException When $cmp is not an array or Traversable.
     */
    public static function coerce(array|Traversable $elements)
    {
        if ($elements instanceof Collection) {
            return $elements;
        } elseif ($elements instanceof \Traversable) {
            $elements = iterator_to_array($elements);
        } elseif (!is_array($elements)) {
            throw new \InvalidArgumentException(sprintf(
                'coerce requires an array or \Traversable, %s given.',
                is_object($elements) ? get_class($elements) : gettype($elements)
            ));
        }

        return new static($elements);
    }
}
