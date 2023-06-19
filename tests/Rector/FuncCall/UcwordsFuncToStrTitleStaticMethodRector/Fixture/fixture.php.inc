<?php

namespace RectorLaravel\Tests\Rector\FuncCall\UcwordsFuncToStrTitleStaticMethodRector\Fixture;

class Fixture
{
    public function store()
    {
        return ucwords('hello friend');
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
        return \Illuminate\Support\Str::title('hello friend');
    }
}

?>
