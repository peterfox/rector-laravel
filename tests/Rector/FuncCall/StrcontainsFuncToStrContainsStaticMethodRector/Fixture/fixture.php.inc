<?php

namespace RectorLaravel\Tests\Rector\FuncCall\StrcontainsFuncToStrContainsStaticMethodRector\Fixture;

class Fixture
{
    public function store()
    {
        str_contains('Hi', 'i');
    }
}

?>
-----
<?php

namespace RectorLaravel\Tests\Rector\FuncCall\StrcontainsFuncToStrContainsStaticMethodRector\Fixture;

class Fixture
{
    public function store()
    {
        \Illuminate\Support\Str::contains('Hi', 'i');
    }
}

?>
