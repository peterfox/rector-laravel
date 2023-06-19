<?php

namespace RectorLaravel\Rector\FuncCall;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \RectorLaravel\Tests\Rector\FuncCall\StrpadFuncToStrPadStaticMethodRector\StrpadFuncToStrPadStaticMethodRectorTest
 */
class StrpadFuncToStrPadStaticMethodRector extends AbstractRector
{

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Use Str::leftPad(), Str::rightPad(), Str::both() instead of str_pad()', [
            new CodeSample(
                <<<'CODE_SAMPLE'
str_pad($string, 10, '0', STR_PAD_LEFT);
CODE_SAMPLE,
                <<<'CODE_SAMPLE'
Str::leftPad($string, 10, '0');
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
        if (! $this->isName($node, 'str_pad')) {
            return null;
        }

        $method = 'padLeft';

        if (($node->args[3] ?? null) !== null) {
            $value = $this->valueResolver->getValue($node->getArgs()[3]->value);

            if ($value === 'STR_PAD_RIGHT') {
                $method = 'padRight';
            } elseif ($value === 'STR_PAD_BOTH') {
                $method = 'padBoth';
            }
        }

        return $this->nodeFactory->createStaticCall('Illuminate\Support\Str', $method, [
            $node->args[0],
            $node->args[1],
            $node->args[2],
        ]);
    }
}
