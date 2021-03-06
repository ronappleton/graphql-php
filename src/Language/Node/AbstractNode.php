<?php

namespace Digia\GraphQL\Language\Node;

use Digia\GraphQL\Language\Location;
use Digia\GraphQL\Language\Visitor\AcceptsVisitorsInterface;
use Digia\GraphQL\Language\Visitor\AcceptsVisitorsTrait;
use Digia\GraphQL\Util\ArrayToJsonTrait;
use Digia\GraphQL\Util\SerializationInterface;

abstract class AbstractNode implements NodeInterface, SerializationInterface, AcceptsVisitorsInterface
{
    use AcceptsVisitorsTrait;
    use ArrayToJsonTrait;

    /**
     * @var string
     */
    protected $kind;

    /**
     * @var Location|null
     */
    protected $location;

    /**
     * @return array
     */
    abstract public function toAST(): array;

    /**
     * AbstractNode constructor.
     *
     * @param string        $kind
     * @param Location|null $location
     */
    public function __construct(string $kind, ?Location $location)
    {
        $this->kind     = $kind;
        $this->location = $location;
    }

    /**
     * @return string
     */
    public function getKind(): string
    {
        return $this->kind;
    }

    /**
     * @return Location|null
     */
    public function getLocation(): ?Location
    {
        return $this->location;
    }

    /**
     * @return array|null
     */
    public function getLocationAST(): ?array
    {
        return null !== $this->location ? $this->location->toArray() : null;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->toAST();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJSON();
    }
}
