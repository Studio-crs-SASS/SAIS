<?php

declare(strict_types=1);

/**
 * SAIS - Error Builder
 *
 * Builds unified error objects for failed SAIS output JSON.
 */

final class ErrorBuilder
{
    /** @var array<string, mixed> */
    private array $warningsConfig;

    /**
     * @param array<string, mixed> $warningsConfig
     */
    public function __construct(array $warningsConfig)
    {
        $this->warningsConfig = $warningsConfig;
    }

    /**
     * Build error by code.
     *
     * @param array<int, string> $affectedItems
     * @return array<string, mixed>
     */
    public function build(string $code, array $affectedItems = []): array
    {
        $errors = $this->warningsConfig['errors'] ?? [];

        $template = is_array($errors) && isset($errors[$code]) && is_array($errors[$code])
            ? $errors[$code]
            : [
                'code' => $code,
                'message' => 'Unknown error.',
                'impact' => 'processing_stopped',
            ];

        return [
            'code' => (string) ($template['code'] ?? $code),
            'message' => (string) ($template['message'] ?? 'Unknown error.'),
            'affected_items' => $affectedItems,
            'impact' => (string) ($template['impact'] ?? 'processing_stopped'),
        ];
    }

    /**
     * Build multiple errors.
     *
     * @param array<int, string> $codes
     * @return array<int, array<string, mixed>>
     */
    public function buildMany(array $codes): array
    {
        $errors = [];

        foreach ($codes as $code) {
            if (!is_string($code) || trim($code) === '') {
                continue;
            }

            $errors[] = $this->build($code);
        }

        return $errors;
    }

    /**
     * Merge errors and remove duplicates.
     *
     * @param array<int, mixed> ...$errorGroups
     * @return array<int, array<string, mixed>>
     */
    public function merge(array ...$errorGroups): array
    {
        $merged = [];

        foreach ($errorGroups as $group) {
            foreach ($group as $error) {
                if (!is_array($error)) {
                    continue;
                }

                $normalized = $this->normalizeError($error);
                $key = $this->buildUniqueKey($normalized);

                $merged[$key] = $normalized;
            }
        }

        return array_values($merged);
    }

    /**
     * Build failed output errors from exception.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildFromException(Throwable $throwable, string $fallbackCode = 'OUTPUT_BUILD_FAILED'): array
    {
        return [
            [
                'code' => $fallbackCode,
                'message' => $throwable->getMessage() !== ''
                    ? $throwable->getMessage()
                    : 'Output JSON generation failed.',
                'affected_items' => [],
                'impact' => 'processing_stopped',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $error
     * @return array<string, mixed>
     */
    private function normalizeError(array $error): array
    {
        $affectedItems = $error['affected_items'] ?? [];

        if (!is_array($affectedItems)) {
            $affectedItems = [];
        }

        return [
            'code' => (string) ($error['code'] ?? 'UNKNOWN_ERROR'),
            'message' => (string) ($error['message'] ?? 'Unknown error.'),
            'affected_items' => array_values(array_filter(
                $affectedItems,
                static fn (mixed $item): bool => is_string($item) && trim($item) !== ''
            )),
            'impact' => (string) ($error['impact'] ?? 'processing_stopped'),
        ];
    }

    /**
     * @param array<string, mixed> $error
     */
    private function buildUniqueKey(array $error): string
    {
        $affectedItems = $error['affected_items'] ?? [];

        if (!is_array($affectedItems)) {
            $affectedItems = [];
        }

        return (string) ($error['code'] ?? 'UNKNOWN_ERROR') . ':' . implode(',', $affectedItems);
    }
}