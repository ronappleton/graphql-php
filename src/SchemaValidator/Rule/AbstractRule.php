<?php

namespace Digia\GraphQL\SchemaValidator\Rule;

use Digia\GraphQL\SchemaValidator\ValidationContextInterface;

abstract class AbstractRule implements RuleInterface
{
    /**
     * @var ValidationContextInterface
     */
    protected $context;

    /**
     * @param ValidationContextInterface $context
     * @return $this
     */
    public function setContext(ValidationContextInterface $context)
    {
        $this->context = $context;
        return $this;
    }
}