<?php

namespace Cards\Controller;

use Cards\Auth;
use Cards\Twig;

class AuthController
{
    private string $appUrl;

    public function __construct(string $appUrl)
    {
        $this->appUrl = $appUrl;
    }

    public function showRegister(): void
    {
        echo Twig::render('register.html.twig', ['app_url' => $this->appUrl]);
    }

    public function register(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            echo Twig::render('register.html.twig', [
                'app_url' => $this->appUrl,
                'error' => 'All fields are required.',
            ]);
            return;
        }

        if (Auth::register($email, $password)) {
            Auth::login($email, $password);
            header('Location: /');
            return;
        }

        echo Twig::render('register.html.twig', [
            'app_url' => $this->appUrl,
            'error' => 'Email already in use.',
        ]);
    }

    public function showLogin(): void
    {
        $user = Auth::getUser();
        if ($user) {
            header('Location: /');
            return;
        }
        echo Twig::render('login.html.twig', ['app_url' => $this->appUrl]);
    }

    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (Auth::login($email, $password)) {
            header('Location: /');
            return;
        }

        echo Twig::render('login.html.twig', [
            'app_url' => $this->appUrl,
            'error' => 'Invalid email or password.',
        ]);
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: /login');
    }
}
