<?php

declare(strict_types=1);

/**
 * SAIS - Additional Check Engine
 *
 * Organizes additional check items for proposal, estimate, SASS bridge, and warnings.
 */

final class AdditionalCheckEngine
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
     * Process additional check items.
     *
     * @param array<int, mixed> $additionalCheckItems
     * @param array<string, mixed> $confidence
     * @return array<string, mixed>
     */
    public function process(array $additionalCheckItems, array $confidence = []): array
    {
        $items = $this->normalizeItems($additionalCheckItems);
        $warnings = $this->buildWarnings($items, $confidence);

        return [
            'items' => $items,
            'proposal_note' => $this->buildProposalNote($items, $confidence),
            'estimate_required_check' => $this->buildEstimateRequiredCheck($items),
            'sass_additional_checks' => $this->buildSassAdditionalChecks($items),
            'warnings' => $warnings,
            'summary' => [
                'total' => count($items),
                'has_additional_check' => count($items) > 0,
                'confidence_display' => $this->extractConfidenceDisplay($confidence),
                'warning_count' => count($warnings),
            ],
        ];
    }

    /**
     * @param array<int, mixed> $additionalCheckItems
     * @return array<int, array<string, mixed>>
     */
    private function normalizeItems(array $additionalCheckItems): array
    {
        $items = [];

        foreach ($additionalCheckItems as $index => $item) {
            if (is_string($item)) {
                $items[] = [
                    'check_id' => 'check_' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'type' => 'general',
                    'message' => trim($item),
                    'target' => '',
                    'impact' => 'needs_confirmation',
                ];

                continue;
            }

            if (!is_array($item)) {
                continue;
            }

            $items[] = [
                'check_id' => (string) ($item['check_id'] ?? 'check_' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)),
                'type' => (string) ($item['type'] ?? 'general'),
                'message' => (string) ($item['message'] ?? ''),
                'target' => (string) ($item['target'] ?? ''),
                'impact' => (string) ($item['impact'] ?? 'needs_confirmation'),
            ];
        }

        return array_values(array_filter(
            $items,
            static fn (array $item): bool => trim((string) ($item['message'] ?? '')) !== ''
        ));
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed> $confidence
     * @return array<int, array<string, mixed>>
     */
    private function buildWarnings(array $items, array $confidence): array
    {
        $warnings = [];

        $confidenceDisplay = $this->extractConfidenceDisplay($confidence);

        if ($confidenceDisplay !== null && $confidenceDisplay < 70.0) {
            $warnings[] = $this->buildWarning('LOW_CONFIDENCE_INPUT', ['confidence']);
        }

        if (count($items) > 0) {
            $warnings[] = $this->buildWarning('ADDITIONAL_CHECK_REQUIRED', ['additional_check_items']);
        }

        return $warnings;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed> $confidence
     */
    private function buildProposalNote(array $items, array $confidence): string
    {
        $confidenceDisplay = $this->extractConfidenceDisplay($confidence);

        if ($confidenceDisplay !== null && $confidenceDisplay < 70.0) {
            return '診断信頼度が低いため、提案前に追加確認を行う必要があります。';
        }

        if (count($items) > 0) {
            return '提案前に確認すべき項目があります。';
        }

        return '現時点で大きな追加確認項目はありません。';
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, string>
     */
    private function buildEstimateRequiredCheck(array $items): array
    {
        $checks = [];

        foreach ($items as $item) {
            $message = (string) ($item['message'] ?? '');

            if ($message !== '') {
                $checks[] = $message;
            }
        }

        return array_values(array_unique($checks));
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function buildSassAdditionalChecks(array $items): array
    {
        $checks = [];

        foreach ($items as $item) {
            $checks[] = [
                'type' => (string) ($item['type'] ?? 'general'),
                'message' => (string) ($item['message'] ?? ''),
                'target' => (string) ($item['target'] ?? ''),
                'impact' => (string) ($item['impact'] ?? 'needs_confirmation'),
            ];
        }

        return $checks;
    }

    /**
     * @param array<string, mixed> $confidence
     */
    private function extractConfidenceDisplay(array $confidence): ?float
    {
        $display = $confidence['display'] ?? null;

        if (is_numeric($display)) {
            return (float) $display;
        }

        return null;
    }

    /**
     * @param array<int, string> $affectedItems
     * @return array<string, mixed>
     */
    private function buildWarning(string $code, array $affectedItems = []): array
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
            'code' => $template['code'] ?? $code,
            'message' => $template['message'] ?? 'Unknown warning.',
            'affected_items' => $affectedItems,
            'impact' => $template['impact'] ?? 'needs_confirmation',
        ];
    }
}