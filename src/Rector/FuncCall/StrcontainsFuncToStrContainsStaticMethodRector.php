<?php

namespace RectorLaravel\Rector\FuncCall;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\StaticCall;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class StrcontainsFuncToStrContainsStaticMethodRector extends AbstractRector
{

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Use Str::contains() instead of str_contains()', []);
    }

    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }

    public function refactor(Node $node): ?StaticCall
    {
        if (! $this->isName($node, 'str_contains')) {
            return null;
        }

        return $this->nodeFactory->createStaticCall('Illuminate\Support\Str', 'contains', [
            $node->args[0],
            $node->args[1],
        ]);
    }
}
