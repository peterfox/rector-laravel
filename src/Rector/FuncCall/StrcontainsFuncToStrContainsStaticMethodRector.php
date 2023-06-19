<?php

namespace RectorLaravel\Rector\FuncCall;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\StaticCall;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \RectorLaravel\Tests\Rector\FuncCall\StrcontainsFuncToStrContainsStaticMethodRector\StrcontainsFuncToStrContainsStaticMethodRectorTest
 */
class StrcontainsFuncToStrContainsStaticMethodRector extends AbstractRector
{

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Use Str::contains() instead of str_contains()', [
            new CodeSample(
                <<<'CODE_SAMPLE'
str_contains($string, $needle);
CODE_SAMPLE,
                <<<'CODE_SAMPLE'
Str::contains($string, $needle);
CODE_SAMPLE,
            ),
        ]);
    }

    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }

    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?StaticCall
    {
        if (! $this->isName($node, 'str_contains')) {
            return null;
        }

        if (count($node->args) !== 2) {
            return null;
        }

        return $this->nodeFactory->createStaticCall('Illuminate\Support\Str', 'contains', [
            $node->args[0],
            $node->args[1],
        ]);
    }
}
