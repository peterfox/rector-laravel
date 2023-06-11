<?php

namespace RectorLaravel\Tests\Rector\FuncCall\StrtolowerFuncToStrLowerStaticMethodRector\Fixture;

class Fixture
{
    public function store()
    {
        return strtolower('Hi');
    }
}

?>
-----
<?php

namespace RectorLaravel\Tests\Rector\FuncCall\StrtolowerFuncToStrLowerStaticMethodRector\Fixture;

class Fixture
{
    public function store()
    {
        return \Illuminate\Support\Str::lower('Hi');
    }
}

?>
