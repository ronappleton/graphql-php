<?php

namespace Digia\GraphQL\Type;

use Digia\GraphQL\GraphQL;
use Digia\GraphQL\Language\DirectiveLocationEnum;
use League\Container\ServiceProvider\AbstractServiceProvider;
use function Digia\GraphQL\Type\Boolean;
use function Digia\GraphQL\Type\newDirective;
use function Digia\GraphQL\Type\newNonNull;
use function Digia\GraphQL\Type\String;

class DirectivesProvider extends AbstractServiceProvider
{
    /**
     * @var array
     */
    protected $provides = [
        GraphQL::INCLUDE_DIRECTIVE,
        GraphQL::SKIP_DIRECTIVE,
        GraphQL::DEPRECATED_DIRECTIVE,
    ];

    /**
     * @inheritdoc
     */
    public function register()
    {
        $this->container->add(GraphQL::INCLUDE_DIRECTIVE, function () {
            return newDirective([
                'name'        => 'include',
                'description' =>
                    'Directs the executor to include this field or fragment only when ' .
                    'the `if` argument is true.',
                'locations'   => [
                    DirectiveLocationEnum::FIELD,
                    DirectiveLocationEnum::FRAGMENT_SPREAD,
                    DirectiveLocationEnum::INLINE_FRAGMENT,
                ],
                'args'        => [
                    'if' => [
                        'type'        => newNonNull(Boolean()),
                        'description' => 'Included when true.',
                    ],
                ],
            ]);
        }, true/* $shared */);

        $this->container->add(GraphQL::SKIP_DIRECTIVE, function () {
            return newDirective([
                'name'        => 'skip',
                'description' =>
                    'Directs the executor to skip this field or fragment when the `if` ' .
                    'argument is true.',
                'locations'   => [
                    DirectiveLocationEnum::FIELD,
                    DirectiveLocationEnum::FRAGMENT_SPREAD,
                    DirectiveLocationEnum::INLINE_FRAGMENT,
                ],
                'args'        => [
                    'if' => [
                        'type'        => newNonNull(Boolean()),
                        'description' => 'Skipped when true.',
                    ],
                ],
            ]);
        }, true/* $shared */);

        $this->container->add(GraphQL::DEPRECATED_DIRECTIVE, function () {
            return newDirective([
                'name'        => 'deprecated',
                'description' => 'Marks an element of a GraphQL schema as no longer supported.',
                'locations'   => [
                    DirectiveLocationEnum::FIELD_DEFINITION,
                    DirectiveLocationEnum::ENUM_VALUE,
                ],
                'args'        => [
                    'reason' => [
                        'type'         => String(),
                        'description'  =>
                            'Explains why this element was deprecated, usually also including a ' .
                            'suggestion for how to access supported similar data. Formatted ' .
                            'in [Markdown](https://daringfireball.net/projects/markdown/).',
                        'defaultValue' => DEFAULT_DEPRECATION_REASON,
                    ],
                ]
            ]);
        }, true/* $shared */);
    }
}
