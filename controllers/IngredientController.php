<?php

class IngredientController {
    public function __construct(private IngredientService $service) {}

    public function handle(): void {
        json_error('Endpoint neimplementat inca.', 501);
    }
}
