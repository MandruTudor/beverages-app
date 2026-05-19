<?php

class PreferenceController {
    public function __construct(private PreferenceService $service) {}

    public function handle(): void {
        json_error('Endpoint neimplementat inca.', 501);
    }
}
