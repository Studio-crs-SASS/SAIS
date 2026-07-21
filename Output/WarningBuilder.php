<?php

declare(strict_types=1);

/**
 * SAIS - Warning Builder
 *
 * Builds unified warning objects for SAIS output JSON.
 */

final class WarningBuilder
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
     * Build warning by code.
     *
     * @param array<int, string> $affectedItems
     * @return array<string, mixed>
     */
    public function build(string $code, array $affectedItems = []): array
    {
        $warnings = $this->warningsConfig['warnings'] ?? [];

        $template = is_array($warnings) && isset($warnings[$code]) && is_array($warnings[$code])
            ? $warnings[$code]
            : [
                'code' => $code,
                'message' => 'Unknown warning.',
                'impact' => 'needs_confirmation',
            ];

        return [
            'code' => (string) ($template['code'] ?? $code),
            'message' => (string) ($template['message'] ?? 'Unknown warning.'),
            'affected_items' => $affectedItems,
            'impact' => (string) ($template['impact'] ?? 'needs_confirmation'),
        ];
    }

    /**
     * Merge warning arrays and remove duplicates by code + affected items.
     *
     * @param array<int, mixed> ...$warningGroups
     * @return array<int, array<string, mixed>>
     */
    public function merge(array ...$warningGroups): array
    {
        $merged = [];

        foreach ($warningGroups as $group) {
            foreach ($group as $warning) {
                if (!is_array($warning)) {
                    continue;
                }

                $normalized = $this->normalizeWarning($warning);
                $key = $this->buildUniqueKey($normalized);

                $merged[$key] = $normalized;
            }
        }

        return array_values($merged);
    }

    /**
     * Build warnings from SAIS output conditions.
     *
     * @param array<string, mixed> $confidence
     * @param array<int, mixed> $proposalInputs
     * @param array<int, mixed> $priorityItems
     * @param array<int, mixed> $recommendedScope
     * @param array<int, mixed> $additionalCheckItems
     * @return array<int, array<string, mixed>>
     */
    public function buildFromConditions(
        array $confidence = [],
        array $proposalInputs = [],
        array $priorityItems = [],
        array $recommendedScope = [],
        array $additionalCheckItems = []
    ): array {
        $warnings = [];

        $display = $confidence['display'] ?? null;

        if (is_numeric($display) && (float) $display < 70.0) {
            $warnings[] = $this->build('LOW_CONFIDENCE_INPUT', ['confidence']);
        }

        if ($proposalInputs === []) {
            $warnings[] = $this->build('MISSING_PROPOSAL_INPUT', ['proposal_inputs']);
        }

        if ($priorityItems === []) {
            $warnings[] = $this->build('MISSING_PRIORITY_ITEMS', ['priority_items']);
        }

        if ($recommendedScope === []) {
            $warnings[] = $this->build('MISSING_RECOMMENDED_SCOPE', ['recommended_scope']);
        }

        if ($additionalCheckItems !== []) {
            $warnings[] = $this->build('ADDITIONAL_CHECK_REQUIRED', ['additional_check_items']);
        }

        return $this->merge($warnings);
    }

    /**
     * Build estimate warning.
     *
     * @param array<string, mixed> $estimate
     * @return array<int, array<string, mixed>>
     */
    public function buildEstimateWarnings(array $estimate): array
    {
        $warnings = [];
        $items = $estimate['estimate_items'] ?? [];

        if (!is_array($items)) {
            return [];
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $pricingStatus = $item['pricing_status'] ?? [];

            if (!is_array($pricingStatus)) {
                continue;
            }

            if (($pricingStatus['needs_human_confirmation'] ?? false) === true) {
                $warnings[] = $this->build('ESTIMATE_NEEDS_CONFIRMATION', ['estimate_items']);
                break;
            }
        }

        return $warnings;
    }

    /**
     * Build SASS scope warning.
     *
     * @param array<string, mixed> $sassScopeCandidate
     * @return array<int, array<string, mixed>>
     */
    public function buildSassScopeWarnings(array $sassScopeCandidate): array
    {
        $candidates = $sassScopeCandidate['candidates'] ?? [];

        if (is_array($candidates) && count($candidates) > 0) {
            return [
                $this->build('SASS_SCOPE_NEEDS_CONFIRMATION', ['sass_scope_candidate']),
            ];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $warning
     * @return array<string, mixed>
     */
    private function normalizeWarning(array $warning): array
    {
        $affectedItems = $warning['affected_items'] ?? [];

        if (!is_array($affectedItems)) {
            $affectedItems = [];
        }

        return [
            'code' => (string) ($warning['code'] ?? 'UNKNOWN_WARNING'),
            'message' => (string) ($warning['message'] ?? 'Unknown warning.'),
            'affected_items' => array_values(array_filter(
                $affectedItems,
                static fn (mixed $item): bool => is_string($item) && trim($item) !== ''
            )),
            'impact' => (string) ($warning['impact'] ?? 'needs_confirmation'),
        ];
    }

    /**
     * @param array<string, mixed> $warning
     */
    private function buildUniqueKey(array $warning): string
    {
        $affectedItems = $warning['affected_items'] ?? [];

        if (!is_array($affectedItems)) {
            $affectedItems = [];
        }

        return (string) ($warning['code'] ?? 'UNKNOWN_WARNING') . ':' . implode(',', $affectedItems);
    }
}