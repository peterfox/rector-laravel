<?php

namespace RectorLaravel\Tests\Rector\FuncCall\UcfirstFuncToStrUcfirstStaticMethodRector\Fixture;

class Fixture
{
    public function store()
    {
        return ucwords('Hello friend');
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
        return \Illuminate\Support\Str::title('Hello friend');
    }
}

?>
