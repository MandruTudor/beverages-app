<?php

class ShoppingListController {
    public function __construct(private ShoppingListService $service) {}

    public function handle(): void {
        json_error('Endpoint neimplementat inca.', 501);
    }
}
