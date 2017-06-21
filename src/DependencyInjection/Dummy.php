<?php

namespace Mikeevstropov\VkParser\DependencyInjection;

class Dummy
{
    public function __get($name)
    {
        return $this;
    }

    public function __call($name, $arguments)
    {
        return $this;
    }
}