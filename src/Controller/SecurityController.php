<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * Login.
     */
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Если пользователь уже авторизован, перенаправить на главную.
        if ($this->getUser()) {
            // return $this->redirectToRoute('app_dashboard');
        }

        // Получить ошибку входа, если она есть.
        $error = $authenticationUtils->getLastAuthenticationError();
        // Последнее введённое имя пользователя.
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render(
            'security/login.html.twig',
            [
                'last_username' => $lastUsername,
                'error'         => $error,
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
