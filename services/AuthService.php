<?php

class AuthService {
    public function __construct(private UserRepository $repo) {}

    public function register(string $username, string $email, string $password): array {
        $username = trim($username);
        $email    = trim($email);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_error('Adresa de email este invalida.');
        }
        if (strlen($password) < 8) {
            json_error('Parola trebuie sa aiba cel putin 8 caractere.');
        }
        if ($this->repo->findByUsernameOrEmail($username, $email)) {
            json_error('Username-ul sau emailul este deja folosit.', 409);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $id   = $this->repo->create($username, $email, $hash);

        return [
            'message' => 'Cont creat cu succes.',
            'user'    => ['id' => $id, 'username' => $username, 'email' => $email],
        ];
    }

    public function login(string $email, string $password): array {
        $user = $this->repo->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            json_error('Email sau parola incorecte.', 401);
        }

        login_user($user);

        return [
            'message' => 'Autentificare reusita.',
            'user'    => [
                'id'       => $user['id'],
                'username' => $user['username'],
                'email'    => $user['email'],
                'role'     => $user['role'],
            ],
        ];
    }
}
