<?php

// TODO: Move this file under the Node namespace

namespace Digia\GraphQL\Language;

use Digia\GraphQL\Error\PrintException;
use Digia\GraphQL\Language\Node\ArgumentNode;
use Digia\GraphQL\Language\Node\BooleanValueNode;
use Digia\GraphQL\Language\Node\DirectiveNode;
use Digia\GraphQL\Language\Node\DocumentNode;
use Digia\GraphQL\Language\Node\EnumValueNode;
use Digia\GraphQL\Language\Node\FieldNode;
use Digia\GraphQL\Language\Node\FloatValueNode;
use Digia\GraphQL\Language\Node\FragmentDefinitionNode;
use Digia\GraphQL\Language\Node\FragmentSpreadNode;
use Digia\GraphQL\Language\Node\InlineFragmentNode;
use Digia\GraphQL\Language\Node\IntValueNode;
use Digia\GraphQL\Language\Node\ListTypeNode;
use Digia\GraphQL\Language\Node\ListValueNode;
use Digia\GraphQL\Language\Node\NamedTypeNode;
use Digia\GraphQL\Language\Node\NameNode;
use Digia\GraphQL\Language\Node\NodeInterface;
use Digia\GraphQL\Language\Node\NonNullTypeNode;
use Digia\GraphQL\Language\Node\NullValueNode;
use Digia\GraphQL\Language\Node\ObjectFieldNode;
use Digia\GraphQL\Language\Node\ObjectValueNode;
use Digia\GraphQL\Language\Node\OperationDefinitionNode;
use Digia\GraphQL\Language\Node\SelectionSetNode;
use Digia\GraphQL\Language\Node\StringValueNode;
use Digia\GraphQL\Language\Node\VariableDefinitionNode;
use Digia\GraphQL\Language\Node\VariableNode;
use function Digia\GraphQL\Util\toString;

class NodePrinter implements NodePrinterInterface
{
    /**
     * @inheritdoc
     * @throws PrintException
     */
    public function print(NodeInterface $node): string
    {
        $printMethod = 'print' . $node->getKind();

        if (\method_exists($this, $printMethod)) {
            return $this->{$printMethod}($node);
        }

        throw new PrintException(\sprintf('Invalid AST Node: %s.', toString($node)));
    }

    /**
     * @param NameNode $node
     * @return string
     */
    protected function printName(NameNode $node): string
    {
        return $node->getValue();
    }

    /**
     * @param VariableNode $node
     * @return string
     */
    protected function printVariable(VariableNode $node): string
    {
        return '$' . $node->getName();
    }

    // Document

    /**
     * @param DocumentNode $node
     * @return string
     */
    protected function printDocument(DocumentNode $node): string
    {
        return \implode("\n\n", $node->getDefinitions()) . "\n";
    }

    /**
     * @param OperationDefinitionNode $node
     * @return string
     * @throws PrintException
     */
    protected function printOperationDefinition(OperationDefinitionNode $node): string
    {
        $operation            = $node->getOperation();
        $name                 = $this->printOne($node->getName());
        $variablesDefinitions = $this->printMany($node->getVariableDefinitions());
        $directives           = $this->printMany($node->getDirectives());
        $selectionSet         = $this->printOne($node->getSelectionSet());

        // Anonymous queries with no directives or variable definitions can use
        // the query short form.
        return empty($name) && empty($directives) && empty($variablesDefinitions) && $operation === 'query'
            ? $selectionSet
            : \implode(' ', [
                $operation,
                $name . wrap('(', \implode(', ', $variablesDefinitions), ')'),
                \implode(' ', $directives),
                $selectionSet,
            ]);
    }

    /**
     * @param VariableDefinitionNode $node
     * @return string
     * @throws PrintException
     */
    protected function printVariableDefinition(VariableDefinitionNode $node): string
    {
        $variable     = $this->printOne($node->getVariable());
        $type         = $this->printOne($node->getType());
        $defaultValue = $this->printOne($node->getDefaultValue());

        return $variable . ': ' . $type . wrap(' = ', $defaultValue);
    }

    /**
     * @param SelectionSetNode $node
     * @return string
     */
    protected function printSelectionSet(SelectionSetNode $node): string
    {
        return block($this->printMany($node->getSelections()));
    }

    /**
     * @param FieldNode $node
     * @return string
     * @throws PrintException
     */
    protected function printField(FieldNode $node): string
    {
        $alias        = $this->printOne($node->getAlias());
        $name         = $this->printOne($node->getName());
        $arguments    = $this->printMany($node->getArguments());
        $directives   = $this->printMany($node->getDirectives());
        $selectionSet = $this->printOne($node->getSelectionSet());

        return \implode(' ', [
            wrap('', $alias, ': ') . $name . wrap('(', \implode(', ', $arguments), ')'),
            \implode(' ', $directives),
            $selectionSet,
        ]);
    }

    /**
     * @param ArgumentNode $node
     * @return string
     * @throws PrintException
     */
    protected function printArgument(ArgumentNode $node): string
    {
        $name  = $this->printOne($node->getName());
        $value = $this->printOne($node->getValue());

        return $name . ': ' . $value;
    }

    // Fragments

    /**
     * @param FragmentSpreadNode $node
     * @return string
     * @throws PrintException
     */
    protected function printFragmentSpread(FragmentSpreadNode $node): string
    {
        $name       = $this->printOne($node->getName());
        $directives = $this->printMany($node->getDirectives());

        return '...' . $name . wrap(' ', \implode(' ', $directives));
    }

    /**
     * @param InlineFragmentNode $node
     * @return string
     * @throws PrintException
     */
    protected function printInlineFragment(InlineFragmentNode $node): string
    {
        $typeCondition = $this->printOne($node->getTypeCondition());
        $directives    = $this->printMany($node->getDirectives());
        $selectionSet  = $this->printOne($node->getSelectionSet());

        return \implode(' ', [
            '...', wrap('on ', $typeCondition),
            \implode(' ', $directives),
            $selectionSet
        ]);
    }

    /**
     * @param FragmentDefinitionNode $node
     * @return string
     * @throws PrintException
     */
    protected function printFragmentDefinition(FragmentDefinitionNode $node): string
    {
        $name                = $this->printOne($node->getName());
        $typeCondition       = $this->printOne($node->getTypeCondition());
        $variableDefinitions = $this->printMany($node->getVariableDefinitions());
        $directives          = $this->printMany($node->getDirectives());
        $selectionSet        = $this->printOne($node->getSelectionSet());

        // Note: fragment variable definitions are experimental and may be changed
        // or removed in the future.
        return \implode(' ', [
            'fragment ' . $name . wrap('(', \implode(', ', $variableDefinitions), ')'),
            'on ' . $typeCondition . ' ' . \implode(' ', $directives),
            $selectionSet
        ]);
    }

    // Value

    /**
     * @param IntValueNode $node
     * @return string
     */
    protected function printIntValue(IntValueNode $node): string
    {
        return $node->getValue();
    }

    /**
     * @param FloatValueNode $node
     * @return string
     */
    protected function printFloatValue(FloatValueNode $node): string
    {
        return $node->getValue();
    }

    /**
     * @param StringValueNode $node
     * @return string
     */
    protected function printStringValue(StringValueNode $node): string
    {
        $value = $node->getValue();

        return $node->isBlock()
            ? printBlockString($value, false)
            : \json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param BooleanValueNode $node
     * @return string
     */
    protected function printBooleanValue(BooleanValueNode $node): string
    {
        return $node->getValue() ? 'true' : 'false';
    }

    /**
     * @param NullValueNode $node
     * @return string
     */
    protected function printNullValue(NullValueNode $node): string
    {
        return 'null';
    }

    /**
     * @param EnumValueNode $node
     * @return string
     */
    protected function printEnumValue(EnumValueNode $node): string
    {
        return $node->getValue();
    }

    /**
     * @param ListValueNode $node
     * @return string
     */
    protected function printListValue(ListValueNode $node): string
    {
        $values = $this->printMany($node->getValues());
        return wrap('[', \implode(', ', $values), ']');
    }

    /**
     * @param ObjectValueNode $node
     * @return string
     */
    protected function printObjectValue(ObjectValueNode $node): string
    {
        $fields = $this->printMany($node->getFields());
        return wrap('{', \implode(', ', $fields), '}');
    }

    /**
     * @param ObjectFieldNode $node
     * @return string
     * @throws PrintException
     */
    protected function printObjectField(ObjectFieldNode $node): string
    {
        $name  = $this->printOne($node->getName());
        $value = $this->printOne($node->getValue());

        return $name . ': ' . $value;
    }

    // Directive

    /**
     * @param DirectiveNode $node
     * @return string
     * @throws PrintException
     */
    protected function printDirective(DirectiveNode $node): string
    {
        $name      = $this->printOne($node->getName());
        $arguments = $this->printMany($node->getArguments());

        return '@' . $name . wrap('(', \implode(', ', $arguments), ')');
    }

    // Type

    /**
     * @param NamedTypeNode $node
     * @return string
     * @throws PrintException
     */
    protected function printNamedType(NamedTypeNode $node): string
    {
        return $this->printOne($node->getName());
    }

    /**
     * @param ListTypeNode $node
     * @return string
     * @throws PrintException
     */
    protected function printListType(ListTypeNode $node): string
    {
        return wrap('[', $this->printOne($node->getType()), ']');
    }

    /**
     * @param NonNullTypeNode $node
     * @return string
     * @throws PrintException
     */
    protected function printNonNullType(NonNullTypeNode $node): string
    {
        return $this->printOne($node->getType()) . '!';
    }

    /**
     * @param NodeInterface|null $node
     * @return string
     * @throws PrintException
     */
    protected function printOne(?NodeInterface $node): string
    {
        return null !== $node ? $this->print($node) : '';
    }

    /**
     * @param array $nodes
     * @return array
     */
    protected function printMany(array $nodes): array
    {
        return \array_map(function ($node) {
            return $this->print($node);
        }, $nodes);
    }
}
