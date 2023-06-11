<?php

namespace RectorLaravel\Rector\FuncCall;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class UcwordsFuncToStrTitleStaticMethodRector extends AbstractRector
{

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Use Str::title() instead of ucwords()', []);
    }

    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }

    public function refactor(Node $node): ?Node\Expr\StaticCall
    {
        if (! $this->isName($node, 'ucwords')) {
            return null;
        }

        return $this->nodeFactory->createStaticCall('Illuminate\Support\Str', 'title', $node->args);
    }
}
