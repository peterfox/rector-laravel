<?php

namespace RectorLaravel\Rector\FuncCall;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class StrtoupperFuncToStrUpperStaticMethodRector extends AbstractRector
{

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Use Str::upper() instead of strtoupper()', []);
    }

    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }

    public function refactor(Node $node): ?Node\Expr\StaticCall
    {
        if (! $this->isName($node, 'strtoupper')) {
            return null;
        }

        return $this->nodeFactory->createStaticCall('Illuminate\Support\Str', 'upper', $node->args);
    }
}
