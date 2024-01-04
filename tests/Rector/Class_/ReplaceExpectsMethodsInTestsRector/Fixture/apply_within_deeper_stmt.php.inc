<?php

namespace RectorLaravel\Tests\Rector\Class_\ReplaceExpectsMethodsInTestsRector\Fixture;

use Illuminate\Foundation\Testing\TestCase;

class BaseTest extends TestCase
{
    public function testSomething()
    {
        if (true) {
            $this->expectsJobs([\App\Jobs\SomeJob::class]);
        }

        $this->get('/');
    }
}
?>
-----
<?php

namespace RectorLaravel\Tests\Rector\Class_\ReplaceExpectsMethodsInTestsRector\Fixture;

use Illuminate\Foundation\Testing\TestCase;

class BaseTest extends TestCase
{
    public function testSomething()
    {
        if (true) {
            \Illuminate\Support\Facades\Bus::fake([\App\Jobs\SomeJob::class]);
        }

        $this->get('/');
        \Illuminate\Support\Facades\Bus::assertDispatched(\App\Jobs\SomeJob::class);
    }
}
?>
