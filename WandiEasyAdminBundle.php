<?php

namespace Wandi\EasyAdminBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class WandiEasyAdminBundle extends Bundle
{
    public function getParent()
    {
        return 'EasyAdminBundle';
    }
}
