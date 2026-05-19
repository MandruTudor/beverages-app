<?php

class AllergenController {
    public function __construct(private AllergenService $service) {}

    public function handle(): void {
        json_error('Endpoint neimplementat inca.', 501);
    }
}
