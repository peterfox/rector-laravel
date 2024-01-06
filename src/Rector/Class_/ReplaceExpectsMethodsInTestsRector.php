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

            /** @var array<string, Expression[]> $expectingNodes */
            $expectingNodes = [];
            /** @var Expression[] $assertions */
            $assertions = [];

            $this->traverseNodesWithCallable($classMethod, function (Node $node) use (&$expectingNodes) {
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

                $facade = match($methodCall->name->name) {
                    'expectsJobs', 'doesntExpectJobs' => 'Bus',
                    'expectsEvents', 'doesntExpectEvents' => 'Event',
                };

                $expectingNodes[$facade] ??= [];
                $expectingNodes[$facade][] = $node;

                return null;
            });

            if (empty($expectingNodes)) {
                continue;
            }

            $dispatchByType = [];

            foreach ($expectingNodes as $facade => $facadeExpectingNodes) {

                /** @var array<ClassConstFetch|String_> $dispatchables */
                $dispatchables = [];
                foreach ($facadeExpectingNodes as $expectingNode) {
                    if (! $expectingNode->expr instanceof MethodCall) {
                        continue;
                    }

                    $methodCallDispatchables = [];

                    // get the arguments of the method call which is either
                    // a String_, ClassConstFetch or Array_
                    $args = $expectingNode->expr->args;

                    // if it's an Array_ then we need to get the values of the array
                    // that are either String_ or ClassConstFetch
                    if ($args[0]->value instanceof Array_) {
                        $methodCallDispatchables = array_filter(array_map(function (ArrayItem $item) {
                            if ($item->value instanceof String_) {
                                return $item->value;
                            } elseif ($item->value instanceof ClassConstFetch) {
                                return $item->value;
                            }
                            return null;
                        }, $args[0]->value->items));
                    } elseif ($args[0]->value instanceof String_ ) {
                        $methodCallDispatchables[] = $args[0]->value;
                    } elseif ($args[0]->value instanceof ClassConstFetch) {
                        $methodCallDispatchables[] = $args[0]->value;
                    }

                    $assertionMethod = match($expectingNode->expr->name->name) {
                        'expectsJobs', 'expectsEvents' => 'assertDispatched',
                        'doesntExpectJobs', 'doesntExpectEvents' => 'assertNotDispatched',
                    };

                    foreach ($methodCallDispatchables as $dispatchable) {
                        $assertions[] = new Expression(
                            new StaticCall(
                                new FullyQualified('Illuminate\Support\Facades\\' . $facade),
                                $assertionMethod,
                                [new Arg($dispatchable)]
                            ),
                        );
                    }

                    $dispatchables = array_merge($dispatchables, $methodCallDispatchables);

                    $changes = true;
                }

                $dispatchByType[$facade] = $dispatchables;
            }

            $fakeMake = [
                'Bus' => false,
                'Event' => false,
            ];;

            foreach ($expectingNodes as $facade => $facadeExpectingNodes) {
                $dispatchables = $dispatchByType[$facade];

                foreach ($facadeExpectingNodes as $expectingNode) {

                    if ($fakeMake[$facade] === false) {

                        $expectingNode->expr = new StaticCall(
                            new FullyQualified('Illuminate\Support\Facades\\' . $facade),
                            'fake',
                            [new Arg(new Array_(array_map(function (String_|ClassConstFetch $dispatchable) {
                                return new ArrayItem($dispatchable);
                            }, $dispatchables)))]
                        );

                        $fakeMake[$facade] = true;
                    } else {
                       $this->traverseNodesWithCallable($classMethod, function (Node $node) use ($expectingNode) {
                           if ($node === $expectingNode) {
                               return NodeTraverser::REMOVE_NODE;
                           }

                           return null;
                       });
                    }


                }
            }

            $classMethod->stmts = array_merge($classMethod->stmts ?? [],  $assertions);
        }

        return $changes ? $node : null;
    }
}
