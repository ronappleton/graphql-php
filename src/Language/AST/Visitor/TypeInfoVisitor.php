<?php

namespace Digia\GraphQL\Language\AST\Visitor;

use Digia\GraphQL\Language\AST\Node\ArgumentNode;
use Digia\GraphQL\Language\AST\Node\DirectiveNode;
use Digia\GraphQL\Language\AST\Node\EnumValueNode;
use Digia\GraphQL\Language\AST\Node\FieldNode;
use Digia\GraphQL\Language\AST\Node\FragmentDefinitionNode;
use Digia\GraphQL\Language\AST\Node\InlineFragmentNode;
use Digia\GraphQL\Language\AST\Node\ListValueNode;
use Digia\GraphQL\Language\AST\Node\NodeInterface;
use Digia\GraphQL\Language\AST\Node\ObjectFieldNode;
use Digia\GraphQL\Language\AST\Node\OperationDefinitionNode;
use Digia\GraphQL\Language\AST\Node\SelectionSetNode;
use Digia\GraphQL\Language\AST\Node\VariableDefinitionNode;
use Digia\GraphQL\Type\Definition\Argument;
use Digia\GraphQL\Type\Definition\CompositeTypeInterface;
use Digia\GraphQL\Type\Definition\EnumType;
use Digia\GraphQL\Type\Definition\InputObjectType;
use Digia\GraphQL\Type\Definition\ListType;
use Digia\GraphQL\Type\Definition\ObjectType;
use Digia\GraphQL\Util\TypeInfo;
use function Digia\GraphQL\Type\getNamedType;
use function Digia\GraphQL\Type\getNullableType;
use function Digia\GraphQL\Type\isInputType;
use function Digia\GraphQL\Type\isOutputType;
use function Digia\GraphQL\Util\find;
use function Digia\GraphQL\Util\typeFromAST;

class TypeInfoVisitor extends Visitor
{
    /**
     * @var TypeInfo
     */
    protected $typeInfo;

    /**
     * TypeInfoVisitor constructor.
     * @param TypeInfo      $typeInfo
     * @param callable|null $enterFunction
     * @param callable|null $leaveFunction
     */
    public function __construct(TypeInfo $typeInfo, ?callable $enterFunction = null, ?callable $leaveFunction = null)
    {
        parent::__construct($enterFunction, $leaveFunction);

        $this->typeInfo = $typeInfo;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function enterNode(
        NodeInterface $node,
        $key = null,
        ?NodeInterface $parent = null,
        array $path = []
    ): ?NodeInterface {
        $schema = $this->typeInfo->getSchema();

        if ($node instanceof SelectionSetNode) {
            $namedType = getNamedType($this->typeInfo->getType());
            $this->typeInfo->pushParentType($namedType instanceof CompositeTypeInterface ? $namedType : null);
        } elseif ($node instanceof FieldNode) {
            $parentType      = $this->typeInfo->getParentType();
            $fieldDefinition = null;
            $fieldType       = null;
            if (null !== $parentType) {
                $fieldDefinition = $this->typeInfo->resolveFieldDefinition($schema, $parentType, $node);
                if (null !== $fieldDefinition) {
                    $fieldType = $fieldDefinition->getType();
                }
            }
            $this->typeInfo->pushFieldDefinition($fieldDefinition);
            $this->typeInfo->pushType(isOutputType($fieldType) ? $fieldType : null);
        } elseif ($node instanceof DirectiveNode) {
            $this->typeInfo->setDirective($schema->getDirective($node->getNameValue()));
        } elseif ($node instanceof OperationDefinitionNode) {
            $type      = null;
            $operation = $node->getOperation();
            if ($operation === 'query') {
                $type = $schema->getQuery();
            } elseif ($operation === 'mutation') {
                $type = $schema->getMutation();
            } elseif ($operation === 'subscription') {
                $type = $schema->getSubscription();
            }
            $this->typeInfo->pushType($type instanceof ObjectType ? $type : null);
        } elseif ($node instanceof InlineFragmentNode || $node instanceof FragmentDefinitionNode) {
            $typeCondition = $node->getTypeCondition();
            $outputType    = null !== $typeCondition
                ? typeFromAST($schema, $typeCondition)
                : getNamedType($this->typeInfo->getType());
            $this->typeInfo->pushType(isOutputType($outputType) ? $outputType : null);
        } elseif ($node instanceof VariableDefinitionNode) {
            $inputType = typeFromAST($schema, $node->getType());
            /** @noinspection PhpParamsInspection */
            $this->typeInfo->pushInputType(isInputType($inputType) ? $inputType : null);
        } elseif ($node instanceof ArgumentNode) {
            $argumentType       = null;
            $argumentDefinition = null;
            $fieldOrDirective   = $this->typeInfo->getDirective() ?: $this->typeInfo->getFieldDefinition();
            if (null !== $fieldOrDirective) {
                $argumentDefinition = find(
                    $fieldOrDirective->getArguments(),
                    function (Argument $argument) use ($node) {
                        return $argument->getName() === $node->getNameValue();
                    }
                );
                if (null !== $argumentDefinition) {
                    $argumentType = $argumentDefinition->getType();
                }
            }
            $this->typeInfo->setArgument($argumentDefinition);
            $this->typeInfo->pushInputType(isInputType($argumentType) ? $argumentType : null);
        } elseif ($node instanceof ListValueNode) {
            $listType = getNullableType($this->typeInfo->getInputType());
            $itemType = $listType instanceof ListType ? $listType->getOfType() : $listType;
            $this->typeInfo->pushInputType(isInputType($itemType) ? $itemType : null);
        } elseif ($node instanceof ObjectFieldNode) {
            $objectType     = getNamedType($this->typeInfo->getInputType());
            $inputFieldType = null;
            if ($objectType instanceof InputObjectType) {
                $fields     = $objectType->getFields();
                $inputField = $fields[$node->getNameValue()] ?? null;
                if (null !== $inputField) {
                    $inputFieldType = $inputField->getType();
                }
            }
            /** @noinspection PhpParamsInspection */
            $this->typeInfo->pushInputType(isInputType($inputFieldType) ? $inputFieldType : null);
        } elseif ($node instanceof EnumValueNode) {
            $enumType  = getNamedType($this->typeInfo->getInputType());
            $enumValue = null;
            if ($enumType instanceof EnumType) {
                $enumValue = $enumType->getValue($node->getValue());
            }
            $this->typeInfo->setEnumValue($enumValue);
        }

        return parent::enterNode($node, $key, $parent, $path);
    }

    /**
     * @inheritdoc
     */
    public function leaveNode(
        NodeInterface $node,
        $key = null,
        ?NodeInterface $parent = null,
        array $path = []
    ): ?NodeInterface {
        $newNode = parent::leaveNode($node, $key, $parent, $path);

        if (null === $newNode) {
            return null;
        }

        if ($newNode instanceof SelectionSetNode) {
            $this->typeInfo->popParentType();
        } elseif ($newNode instanceof FieldNode) {
            $this->typeInfo->popFieldDefinition();
            $this->typeInfo->popType();
        } elseif ($newNode instanceof DirectiveNode) {
            $this->typeInfo->setDirective(null);
        } elseif ($newNode instanceof OperationDefinitionNode) {
            $this->typeInfo->popType();
        } elseif ($newNode instanceof InlineFragmentNode || $newNode instanceof FragmentDefinitionNode) {
            $this->typeInfo->popType();
        } elseif ($newNode instanceof VariableDefinitionNode) {
            $this->typeInfo->popInputType();
        } elseif ($newNode instanceof ArgumentNode) {
            $this->typeInfo->setArgument(null);
            $this->typeInfo->popInputType();
        } elseif ($newNode instanceof ListValueNode) {
            $this->typeInfo->popInputType();
        } elseif ($newNode instanceof ObjectFieldNode) {
            $this->typeInfo->popInputType();
        } elseif ($newNode instanceof EnumValueNode) {
            $this->typeInfo->setEnumValue(null);
        }

        return $newNode;
    }
}
