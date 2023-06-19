<?php

namespace RectorLaravel\Rector\FuncCall;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \RectorLaravel\Tests\Rector\FuncCall\UcfirstFuncToStrUcfirstStaticMethodRector\UcfirstFuncToStrUcfirstStaticMethodRectorTest
 */
class UcfirstFuncToStrUcfirstStaticMethodRector extends AbstractRector
{

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Use Str::ucfirst() instead of ucfirst()', [
            new CodeSample(
                <<<'CODE_SAMPLE'
ucfirst($string);
CODE_SAMPLE,
                <<<'CODE_SAMPLE'
Str::ucfirst($string);
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
        if (! $this->isName($node, 'ucfirst')) {
            return null;
        }

        return $this->nodeFactory->createStaticCall('Illuminate\Support\Str', 'ucfirst', $node->args);
    }
}
