<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }// end __construct()

    #[Route("/login", name: "app_login")]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render(
            '@EasyAdmin/page/login.html.twig',
            [
                'error'                => $error,
                'last_username'        => $lastUsername,
                'translation_domain'   => 'admin',
                'page_title'           => $this->translator->trans('title', domain: 'messages'),
                'csrf_token_intention' => 'authenticate',
                'target_path'          => $this->generateUrl('app_dashboard'),
                'remember_me_enabled'  => true,
                'remember_me_checked'  => true,
            ]
        );
    }// end login()

    /**
     * Logout
     */
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Этот метод может быть пустым — Symfony перехватывает выход автоматически.
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }// end logout()
}// end class
