<?php

namespace Wandi\EasyAdminBundle\Security;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Wandi\EasyAdminBundle\Entity\User;

class FormAuthenticator extends AbstractGuardAuthenticator
{
    use TargetPathTrait;

    private $em;
    private $encoder;
    private $router;
    private $request;
    private $csrfTokenManager;

    /**
     * FormAuthenticator constructor.
     *
     * @param EntityManager       $em
     * @param UserPasswordEncoder $encoder
     * @param Router              $router
     * @param RequestStack        $request
     */
    public function __construct(
        EntityManager $em,
        UserPasswordEncoder $encoder,
        Router $router,
        RequestStack $request,
        CsrfTokenManagerInterface $csrfTokenManager
    ) {
        $this->em = $em;
        $this->encoder = $encoder;
        $this->router = $router;
        $this->request = $request;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    public function getCredentials(Request $request)
    {
        if ($request->get('_route') != 'wandi_easy_admin_login_check') {
            return null;
        }

        $csrfToken = $request->request->get('_csrf_token');

        if (false === $this->csrfTokenManager->isTokenValid(new CsrfToken('authenticate', $csrfToken))) {
            throw new InvalidCsrfTokenException('Invalid CSRF token.');
        }

        $username = $request->request->get('_username');
        $password = $request->request->get('_password');
        $request->getSession()->set(Security::LAST_USERNAME, $username);

        return [
            'username' => $username,
            'password' => $password,
        ];
    }

    /**
     * @param mixed                 $credentials
     * @param UserProviderInterface $userProvider
     *
     * @return null|User
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return $this->em->getRepository('WandiEasyAdminBundle:User')->findOneBy(
            [
                'enabled' => true,
                'username' => $credentials['username'],
            ]
        );
    }

    /**
     * @param mixed         $credentials
     * @param UserInterface $user
     *
     * @return bool
     *
     * @internal param \UserBundle\Entity\User $
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        return $this->encoder->isPasswordValid($user, $credentials['password']);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $targetPath = null;

        if ($request->getSession() instanceof SessionInterface) {
            $targetPath = $this->getTargetPath($request->getSession(), $providerKey);
        }

        if ($targetPath === null) {
            $targetPath = $this->router->generate('easyadmin');
        }

        return new RedirectResponse($targetPath);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $this->request->getCurrentRequest()->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);

        return new RedirectResponse($this->router->generate('wandi_easy_admin_login'));
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new RedirectResponse($this->router->generate('wandi_easy_admin_login'));
    }

    public function supportsRememberMe()
    {
        return true;
    }
}
