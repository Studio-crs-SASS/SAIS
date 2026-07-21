<?php

declare(strict_types=1);

/**
 * SAIS - Input Receiver
 *
 * Receives JSON input for SAIS.
 */

final class InputReceiver
{
    /**
     * Receive raw JSON string from PHP input stream.
     */
    public function receiveRaw(): string
    {
        $raw = file_get_contents('php://input');

        if ($raw === false) {
            return '';
        }

        return trim($raw);
    }

    /**
     * Decode JSON string into associative array.
     *
     * @return array<string, mixed>
     */
    public function decodeJson(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    /**
     * Receive and decode JSON from request body.
     *
     * @return array<string, mixed>
     */
    public function receive(): array
    {
        $raw = $this->receiveRaw();

        return $this->decodeJson($raw);
    }

    /**
     * Load JSON input from file path.
     *
     * @return array<string, mixed>
     */
    public function receiveFromFile(string $filePath): array
    {
        if (!is_file($filePath)) {
            return [];
        }

        $raw = file_get_contents($filePath);

        if ($raw === false) {
            return [];
        }

        return $this->decodeJson($raw);
    }
}