<?php

declare(strict_types = 1);

namespace Wandi\EasyAdminBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;

class AdminController extends BaseAdminController
{
    /**
     * Login action.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAction()
    {
        $authenticationUtils = $this->get('security.authentication_utils');
        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render(
            '@WandiEasyAdmin/Admin/login.html.twig',
            [
                'error' => $error,
                'lastUsername' => $lastUsername,
                'config' => $this->getParameter('easyadmin.config'),
            ]
        );
    }
}
