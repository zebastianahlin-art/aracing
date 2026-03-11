<?php

declare(strict_types=1);

namespace App\Modules\Customer\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Cms\Services\CmsPageService;
use App\Modules\Customer\Services\AuthService;
use InvalidArgumentException;

final class AuthController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly AuthService $auth,
        private readonly CmsPageService $pages
    ) {
    }

    public function registerForm(): Response
    {
        return new Response($this->views->render('storefront.auth.register', [
            'error' => trim((string) ($_GET['error'] ?? '')),
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }

    public function register(): Response
    {
        try {
            $this->auth->register($_POST);

            return $this->redirect('/account');
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/register?error=' . urlencode($e->getMessage()));
        }
    }

    public function loginForm(): Response
    {
        return new Response($this->views->render('storefront.auth.login', [
            'error' => trim((string) ($_GET['error'] ?? '')),
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }

    public function login(): Response
    {
        try {
            $this->auth->attemptLogin((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''));

            return $this->redirect('/account');
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/login?error=' . urlencode($e->getMessage()));
        }
    }

    public function logout(): Response
    {
        $this->auth->logout();

        return $this->redirect('/');
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
