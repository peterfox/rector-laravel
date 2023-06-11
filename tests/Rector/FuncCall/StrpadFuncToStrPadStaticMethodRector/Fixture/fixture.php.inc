<?php

namespace RectorLaravel\Tests\Rector\FuncCall\StrpadFuncToStrPadStaticMethodRector\Fixture;

class Fixture
{
    public function store()
    {
        str_pad('Hi', 10, '0', STR_PAD_LEFT);
        str_pad('Hi', 10, '0');
        str_pad('Hi', 10, '0', STR_PAD_RIGHT);
        str_pad('Hi', 10, '0', STR_PAD_BOTH);
    }
}

?>
-----
<?php

namespace RectorLaravel\Tests\Rector\FuncCall\StrpadFuncToStrPadStaticMethodRector\Fixture;

class Fixture
{
    public function store()
    {
        \Illuminate\Support\Str::padLeft('Hi', 10, '0');
        \Illuminate\Support\Str::padLeft('Hi', 10, '0');
        \Illuminate\Support\Str::padRight('Hi', 10, '0');
        \Illuminate\Support\Str::padBoth('Hi', 10, '0');
    }
}

?>
