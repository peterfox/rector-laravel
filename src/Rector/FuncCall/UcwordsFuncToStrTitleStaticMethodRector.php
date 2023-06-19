<?php

namespace RectorLaravel\Rector\FuncCall;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \RectorLaravel\Tests\Rector\FuncCall\UcwordsFuncToStrTitleStaticMethodRector\UcwordsFuncToStrTitleStaticMethodRectorTest
 */
class UcwordsFuncToStrTitleStaticMethodRector extends AbstractRector
{

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Use Str::title() instead of ucwords()', [
            new CodeSample(
                <<<'CODE_SAMPLE'
ucwords($string);
CODE_SAMPLE,
                <<<'CODE_SAMPLE'
Str::title($string);
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
    public function refactor(Node $node): ?Node\Expr\StaticCall
    {
        if (! $this->isName($node, 'ucwords')) {
            return null;
        }

        return $this->nodeFactory->createStaticCall('Illuminate\Support\Str', 'title', $node->args);
    }
}
