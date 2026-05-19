<?php

class AuthController {
    public function __construct(private AuthService $service) {}

    public function handle(string $action): void {
        match ($action) {
            'register' => $this->register(),
            'login'    => $this->login(),
            'logout'   => $this->logout(),
            'me'       => $this->me(),
            default    => json_error('Actiune invalida.', 404),
        };
    }

    private function register(): void {
        $data    = json_body();
        $missing = missing_field($data, ['username', 'email', 'password']);
        if ($missing) {
            json_error("Campul '$missing' este obligatoriu.");
        }
        json_response($this->service->register($data['username'], $data['email'], $data['password']), 201);
    }

    private function login(): void {
        $data    = json_body();
        $missing = missing_field($data, ['email', 'password']);
        if ($missing) {
            json_error("Campul '$missing' este obligatoriu.");
        }
        json_response($this->service->login($data['email'], $data['password']));
    }

    private function logout(): void {
        logout_user();
        json_response(['message' => 'Deconectare reusita.']);
    }

    private function me(): void {
        require_auth();
        json_response(['user' => current_user()]);
    }
}
