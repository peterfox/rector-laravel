<?php

namespace RectorLaravel\Tests\Rector\Class_\ReplaceExpectsMethodsInTestsRector\Fixture;

use Illuminate\Foundation\Testing\TestCase;

class BaseTest extends TestCase
{
    public function testSomething()
    {
        true ?
            $this->expectsJobs([\App\Jobs\SomeJob::class])
            : $this->doesntExpectEvents(\App\Jobs\SomeJob::class);

        $this->get('/');
    }
}
?>
