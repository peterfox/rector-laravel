<?php

declare(strict_types=1);

namespace RectorLaravel\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeTraverser;
use PHPStan\Type\ObjectType;
use Rector\Core\Rector\AbstractRector;
use RectorLaravel\ValueObject\FakeableDispatches;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \RectorLaravel\Tests\Rector\Class_\ReplaceExpectsMethodsInTestsRector\ReplaceExpectsMethodsInTestsRectorTest
 */
class ReplaceExpectsMethodsInTestsRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace expectJobs and expectEvents methods in tests', [
            new CodeSample(
                <<<'CODE_SAMPLE'
use Illuminate\Foundation\Testing\TestCase;

class SomethingTest extends TestCase
{
    public function testSomething()
    {
        $this->expectsJobs([\App\Jobs\SomeJob::class, \App\Jobs\SomeOtherJob::class]);
        $this->expectsEvents(\App\Events\SomeEvent::class);
        $this->doesntExpectEvents(\App\Events\SomeOtherEvent::class);

        $this->get('/');
    }
}
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
use Illuminate\Foundation\Testing\TestCase;

class SomethingTest extends TestCase
{
    public function testSomething()
    {
        \Illuminate\Support\Facades\Bus::fake([\App\Jobs\SomeJob::class, \App\Jobs\SomeOtherJob::class]);
        \Illuminate\Support\Facades\Event::fake([\App\Events\SomeEvent::class, \App\Events\SomeOtherEvent::class]);

        $this->get('/');

        \Illuminate\Support\Facades\Bus::assertDispatched(\App\Jobs\SomeJob::class);
        \Illuminate\Support\Facades\Bus::assertDispatched(\App\Jobs\SomeOtherJob::class);
        \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\SomeEvent::class);
        \Illuminate\Support\Facades\Event::assertNotDispatched(\App\Events\SomeOtherEvent::class);
    }
}
CODE_SAMPLE
            ),
        ]);
    }

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param  Node\Stmt\Class_  $node
     */
    public function refactor(Node $node): ?Class_
    {
        if (! $this->isObjectType($node, new ObjectType('\Illuminate\Foundation\Testing\TestCase'))) {
            return null;
        }

        $changes = false;

        // loop over all methods in class
        foreach ($node->getMethods() as $classMethod) {

            /** @var array<string, FakeableDispatches> $fakeableDispatches */
            $fakeableDispatches = [];

            $this->traverseNodesWithCallable($classMethod, function (Node $node) use (&$fakeableDispatches) {
                if (! $node instanceof Expression) {
                    return null;
                }

                if (! $node->expr instanceof MethodCall) {
                    return null;
                }

                $methodCall = $node->expr;

                if (! $methodCall->var instanceof Variable || ! $this->isName($node->expr->var, 'this')) {
                    return null;
                }

                if (! $this->isNames($methodCall->name, [
                    'expectsJobs',
                    'expectsEvents',
                    'doesntExpectEvents',
                    'doesntExpectJobs'
                ])) {
                    return null;
                }

                if (! $methodCall->name instanceof Identifier) {
                    return null;
                }

                $facade = match($methodCall->name->name) {
                    'expectsJobs', 'doesntExpectJobs' => 'Bus',
                    'expectsEvents', 'doesntExpectEvents' => 'Event',
                    default => null,
                };

                if ($facade === null) {
                    return null;
                }

                $fakeableDispatches[$facade] ??= new FakeableDispatches(
                    new FullyQualified('Illuminate\Support\Facades\\' . $facade),
                );
                $fakeableDispatches[$facade]->addExpressionNode($node);

                return null;
            });

            if ($fakeableDispatches === []) {
                continue;
            }

            $this->traverseNodesWithCallable($classMethod, function (Node $node) use (&$fakeableDispatches) {
                if (! $node instanceof Expression) {
                    return null;
                }

                foreach ($fakeableDispatches as $fakeableDispatch) {
                    if ($fakeableDispatch->hasNode($node)) {
                        if ($fakeableDispatch->replaced) {
                            return NodeTraverser::REMOVE_NODE;
                        } else {
                            $fakeableDispatch->replaced = true;
                            return $fakeableDispatch->generateFakeMethod();
                        }
                    }
                }

                return null;
            });

            foreach ($fakeableDispatches as $fakeableDispatch) {
                $classMethod->stmts = array_merge(
                    $classMethod->stmts ?? [],
                    array_map(
                        fn (StaticCall $call) => new Expression($call),
                        $fakeableDispatch->generateAssertionStatements()
                    ),
                );
                $changes = true;
            }
        }

        return $changes ? $node : null;
    }
}
