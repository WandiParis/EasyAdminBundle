<?php

namespace Wandi\EasyAdminBundle\Services;

use CKSource\Bundle\CKFinderBundle\Authentication\Authentication as AuthenticationBase;

class CKFinderAuthentication extends AuthenticationBase
{
    public function authenticate()
    {
        return (bool) $this->container->get('security.token_storage')->getToken()->getUser();
    }
}
