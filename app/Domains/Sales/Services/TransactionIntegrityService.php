<?php

namespace App\Domains\Sales\Services;

class TransactionIntegrityService
{
    public function hash(array $payload): string
    {
        ksort($payload);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public function checksum(iterable $rows): string
    {
        return hash('sha256', json_encode(collect($rows)->values()->all(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public function verify(array $payload, string $hash): bool
    {
        return hash_equals($hash, $this->hash($payload));
    }
}
