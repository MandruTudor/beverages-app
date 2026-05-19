<?php

class ExportController {
    public function __construct(private ExportService $service) {}

    public function handle(): void {
        json_error('Endpoint neimplementat inca.', 501);
    }
}
