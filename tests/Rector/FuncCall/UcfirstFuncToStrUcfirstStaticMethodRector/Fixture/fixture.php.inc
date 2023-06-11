<?php

namespace RectorLaravel\Tests\Rector\FuncCall\UcwordsFuncToStrTitleStaticMethodRector\Fixture;

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

namespace RectorLaravel\Tests\Rector\FuncCall\UcwordsFuncToStrTitleStaticMethodRector\Fixture;

class Fixture
{
    public function store()
    {
        return \Illuminate\Support\Str::title('Hello friend');
    }
}

?>
