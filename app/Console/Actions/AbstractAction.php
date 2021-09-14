<?php

namespace app\Console\Actions;

class AbstractAction
{
    protected \App\ResourceManager $resources;

    public function __construct(\App\ResourceManager $resources)
    {
        $this->resources = $resources;
    }
}
