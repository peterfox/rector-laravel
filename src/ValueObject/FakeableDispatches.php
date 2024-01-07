<?php

namespace RectorLaravel\ValueObject;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use Webmozart\Assert\Assert;

class FakeableDispatches
{
    public const DISPATCHED = 'dispatched';
    public const NOT_DISPATCHED = 'notDispatched';

    /**
     * @var array<String_|ClassConstFetch>
     */
    protected $assertDispatched = [];

    /**
     * @var array<String_|ClassConstFetch>
     */
    protected $assertNotDispatched = [];

    /**
     * @var array<Expression>
     */
    protected $exprNodes = [];

    public bool $replaced = false;

    public function __construct(protected FullyQualified $facade)
    {
    }

    public function addExpressionNode(Expression $expression): void
    {
        Assert::isInstanceOf($expression->expr, MethodCall::class);
        Assert::isInstanceOf($expression->expr->name, Identifier::class);
        $methodCall = $expression->expr;

        $assertionType = match($methodCall->name->name) {
            'expectsJobs', 'expectsEvents' => FakeableDispatches::DISPATCHED,
            'doesntExpectJobs', 'doesntExpectEvents' => FakeableDispatches::NOT_DISPATCHED,
            default => throw new \InvalidArgumentException('Invalid method call')
        };

        if ($methodCall->args[0] instanceof Arg && $methodCall->args[0]->value instanceof Array_) {
            foreach ($methodCall->args[0]->value->items as $item) {
                if ($item instanceof ArrayItem) {
                    if ($item->value instanceof String_ || $item->value instanceof ClassConstFetch) {
                        $this->addDispatachable($item->value, $assertionType);
                    }
                }
            }
        } elseif ($methodCall->args[0] instanceof Arg && $methodCall->args[0]->value instanceof String_) {
            $this->addDispatachable($methodCall->args[0]->value, $assertionType);
        } elseif ($methodCall->args[0] instanceof Arg && $methodCall->args[0]->value instanceof ClassConstFetch) {
            $this->addDispatachable($methodCall->args[0]->value, $assertionType);
        }

        $this->exprNodes[] = $expression;
    }

    public function hasNode(Expression $node): bool
    {
        return \in_array($node, $this->exprNodes, true);
    }

    /**
     * @return StaticCall
     */
    public function generateFakeMethod(): StaticCall
    {
        return new StaticCall($this->facade, 'fake',
            [new Arg($this->allDispatchables())]
        );
    }

    /**
     * @return StaticCall[]
     */
    public function generateAssertionStatements(): array
    {
        $assertions = [];

        foreach ($this->assertDispatched as $dispatched) {
            $assertions[] = new StaticCall($this->facade, 'assertDispatched',
                [new Arg($dispatched)]
            );
        }

        foreach ($this->assertNotDispatched as $dispatched) {
            $assertions[] = new StaticCall($this->facade, 'assertNotDispatched',
                [new Arg($dispatched)]
            );
        }

        if ($assertions === []) {
            throw new \RuntimeException('No dispatchables have been added.');
        }

        return $assertions;
    }

    /**
     * @param String_|ClassConstFetch|array<String_|ClassConstFetch> $dispatchable
     * @param string $dispatched
     * @return $this
     */
    protected function addDispatachable(String_|ClassConstFetch|array $dispatchable, string $dispatched = self::DISPATCHED): static
    {
        if (\is_array($dispatchable)) {
            foreach ($dispatchable as $item) {
                $this->addDispatachable($item, $dispatched);
            }
            return $this;
        }

        if ($dispatched === self::DISPATCHED) {
            $this->assertDispatched[$this->generateKey($dispatchable)] = $dispatchable;
            return $this;
        } elseif ($dispatched === self::NOT_DISPATCHED) {
            $this->assertNotDispatched[$this->generateKey($dispatchable)] = $dispatchable;
            return $this;
        }

        throw new \InvalidArgumentException('Invalid dispatched value');
    }

    protected function allDispatchables(): Array_
    {
        $compiledDispatchables = [];

        foreach ($this->assertDispatched as $dispatched) {
            $compiledDispatchables[$this->generateKey($dispatched)] = new ArrayItem($dispatched);
        }
        foreach ($this->assertNotDispatched as $dispatched) {
            $compiledDispatchables[$this->generateKey($dispatched)] = new ArrayItem($dispatched);
        }

        return new Array_(array_values($compiledDispatchables));
    }

    protected function generateKey(String_|ClassConstFetch $item): string
    {
        if ($item instanceof String_) {
            return $item->value;
        }

        if ($item->class instanceof Name) {
            return $item->class->toString();
        }

        throw new \RuntimeException();
    }
}
