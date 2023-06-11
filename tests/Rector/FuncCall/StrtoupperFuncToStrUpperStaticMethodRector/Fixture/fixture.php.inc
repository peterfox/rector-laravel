<?php

namespace RectorLaravel\Tests\Rector\FuncCall\StrtoupperFuncToStrUpperStaticMethodRector\Fixture;

class Fixture
{
    public function store()
    {
        return strtoupper('Hi');
    }
}

?>
-----
<?php

namespace RectorLaravel\Tests\Rector\FuncCall\StrtoupperFuncToStrUpperStaticMethodRector\Fixture;

class Fixture
{
    public function store()
    {
        return \Illuminate\Support\Str::upper('Hi');
    }
}

?>
