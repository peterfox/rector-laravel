<?php

namespace RectorLaravel\Tests\Rector\FuncCall\UcfirstFuncToStrUcfirstStaticMethodRector\Fixture;

class Fixture
{
    public function store()
    {
        return ucfirst('hello friend');
    }
}

?>
-----
<?php

namespace RectorLaravel\Tests\Rector\FuncCall\UcfirstFuncToStrUcfirstStaticMethodRector\Fixture;

class Fixture
{
    public function store()
    {
        return \Illuminate\Support\Str::ucfirst('hello friend');
    }
}

?>
