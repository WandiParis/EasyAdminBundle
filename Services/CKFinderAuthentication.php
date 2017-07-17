<?php

namespace Wandi\EasyAdminBundle\Services;

use CKSource\Bundle\CKFinderBundle\Authentication\Authentication as AuthenticationBase;

class CKFinderAuthentication extends AuthenticationBase
{
    public function authenticate()
    {
        return !!$this->container->get('security.token_storage')->getToken()->getUser();
    }
}