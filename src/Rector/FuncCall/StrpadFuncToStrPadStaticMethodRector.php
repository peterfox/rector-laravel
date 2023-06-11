<?php

namespace RectorLaravel\Rector\FuncCall;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class StrpadFuncToStrPadStaticMethodRector extends AbstractRector
{

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Use Str::leftPad(), Str::rightPad(), Str::both() instead of str_pad()', []);
    }

    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }

    public function refactor(Node $node): ?Node\Expr\StaticCall
    {
        if (! $this->isName($node, 'str_pad')) {
            return null;
        }

        $method = 'padLeft';

        if ($node->args[3] ?? null) {
            $value = $this->valueResolver->getValue($node->args[3]->value);

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
