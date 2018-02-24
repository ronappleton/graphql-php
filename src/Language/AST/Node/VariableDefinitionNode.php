<?php

namespace Digia\GraphQL\Language\AST\Node;

use Digia\GraphQL\Language\AST\Node\Behavior\NameTrait;
use Digia\GraphQL\Language\AST\NodeKindEnum;
use Digia\GraphQL\Language\AST\Node\Behavior\DefaultValueTrait;
use Digia\GraphQL\Language\AST\Node\Contract\DefinitionNodeInterface;
use Digia\GraphQL\Language\AST\Node\Contract\TypeNodeInterface;

class VariableDefinitionNode extends AbstractNode implements DefinitionNodeInterface
{

    use NameTrait;
    use DefaultValueTrait;

    /**
     * @var string
     */
    protected $kind = NodeKindEnum::VARIABLE_DEFINITION;

    /**
     * @var VariableNode
     */
    protected $variable;

    /**
     * @var TypeNodeInterface
     */
    protected $type;

    /**
     * @return VariableNode
     */
    public function getVariable(): VariableNode
    {
        return $this->variable;
    }

    /**
     * @return TypeNodeInterface
     */
    public function getType(): TypeNodeInterface
    {
        return $this->type;
    }
}