<?php

declare(strict_types=1);

namespace Brick\Schema;

use Brick\Schema\Interfaces\Thing;

/**
 * Dynamically builds objects implementing arbitrary interfaces.
 *
 * @internal
 */
class ObjectFactory
{
    /**
     * A map of schema.org type to list of schema.org properties.
     *
     * Example: ['Thing'] = ['name', 'image', ...]
     *
     * The keys of this array must exactly match the list of Thing interfaces we support.
     *
     * @var string[][]
     */
    private $propertiesByType = [];

    /**
     * ObjectFactory constructor.
     *
     * @param string[][] $propertiesByType
     */
    public function __construct(array $propertiesByType)
    {
        $this->propertiesByType = $propertiesByType;
    }

    /**
     * Builds an empty Thing implementing the given types.
     *
     * If any unknown types are given, they will be ignored.
     *
     * @param string[] $types The list of schema.org types the object implements, e.g. ['Product', 'Offer'].
     *
     * @return Thing|null The resulting Thing, or null if no known schema.org types are given.
     */
    public function build(array $types) : ?Thing
    {
        $types = array_filter($types, function(string $type) {
            return isset($this->propertiesByType[$type]);
        });

        $types = array_values($types);

        if (! $types) {
            return null;
        }

        $interfaces = array_map(function(string $type) : string {
            return 'Brick\\Schema\\Interfaces\\' . $type;
        }, $types);

        $properties = array_map(function(string $type) : array {
            return $this->propertiesByType[$type];
        }, $types);

        $properties = array_merge(...$properties);
        $properties = array_unique($properties);
        $properties = array_values($properties);

        $php = sprintf(
            'return new class (%s, %s) extends %s implements %s {};',
            var_export($types, true),
            var_export($properties, true),
            Base::class,
            implode(', ', $interfaces)
        );

        return eval($php);
    }
}
