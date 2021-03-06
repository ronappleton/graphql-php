<?php

namespace Digia\GraphQL\Execution;

use Digia\GraphQL\Language\Node\DocumentNode;
use Digia\GraphQL\Schema\Schema;

interface ExecutionInterface
{
    /**
     * @param Schema        $schema
     * @param DocumentNode  $documentNode
     * @param mixed         $rootValue
     * @param mixed         $contextValue
     * @param array         $variableValues
     * @param string|null   $operationName
     * @param callable|null $fieldResolver
     * @return ExecutionResult
     */
    public function execute(
        Schema $schema,
        DocumentNode $documentNode,
        $rootValue = null,
        $contextValue = null,
        array $variableValues = [],
        string $operationName = null,
        callable $fieldResolver = null
    ): ExecutionResult;
}
