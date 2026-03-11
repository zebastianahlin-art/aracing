<?php

declare(strict_types=1);

namespace App\Modules\Customer\Services;

use App\Modules\Customer\Repositories\UserRepository;
use InvalidArgumentException;

final class AuthService
{
    private const SESSION_USER_ID = 'customer_user_id';

    public function __construct(private readonly UserRepository $users)
    {
    }

    public function register(array $input): int
    {
        $email = mb_strtolower(trim((string) ($input['email'] ?? '')));
        $password = (string) ($input['password'] ?? '');
        $firstName = trim((string) ($input['first_name'] ?? ''));
        $lastName = trim((string) ($input['last_name'] ?? ''));

        if ($email === '' || $password === '' || $firstName === '' || $lastName === '') {
            throw new InvalidArgumentException('Fyll i obligatoriska fält.');
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Ange en giltig e-postadress.');
        }

        if (mb_strlen($password) < 8) {
            throw new InvalidArgumentException('Lösenordet måste vara minst 8 tecken.');
        }

        if ($this->users->findByEmail($email) !== null) {
            throw new InvalidArgumentException('E-postadressen används redan av ett konto.');
        }

        $userId = $this->users->createCustomer([
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'customer',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => trim((string) ($input['phone'] ?? '')),
        ]);

        $this->loginUserId($userId);

        return $userId;
    }

    public function attemptLogin(string $email, string $password): void
    {
        $email = mb_strtolower(trim($email));
        if ($email === '' || trim($password) === '') {
            throw new InvalidArgumentException('Ange e-post och lösenord.');
        }

        $user = $this->users->findByEmail($email);
        if ($user === null || !password_verify($password, (string) ($user['password_hash'] ?? ''))) {
            throw new InvalidArgumentException('Felaktig e-post eller lösenord.');
        }

        if (($user['role'] ?? 'customer') !== 'customer') {
            throw new InvalidArgumentException('Kontot kan inte logga in i kundgränssnittet.');
        }

        $this->loginUserId((int) $user['id']);
    }

    public function logout(): void
    {
        unset($_SESSION[self::SESSION_USER_ID]);
    }

    /** @return array<string, mixed>|null */
    public function currentCustomer(): ?array
    {
        $userId = $this->currentUserId();
        if ($userId === null) {
            return null;
        }

        $user = $this->users->findById($userId);
        if ($user === null || ($user['role'] ?? 'customer') !== 'customer') {
            $this->logout();

            return null;
        }

        return $user;
    }

    public function currentUserId(): ?int
    {
        $id = $_SESSION[self::SESSION_USER_ID] ?? null;

        return is_numeric($id) ? (int) $id : null;
    }

    private function loginUserId(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION[self::SESSION_USER_ID] = $userId;
    }
}
