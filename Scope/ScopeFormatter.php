<?php

declare(strict_types=1);

/**
 * SAIS - Scope Formatter
 *
 * Formats scope data for estimate, introduction plan, and SASS bridge.
 */

final class ScopeFormatter
{
    /**
     * Format scope items for estimate.
     *
     * @param array<int, string> $items
     * @return array<int, array<string, mixed>>
     */
    public function formatForEstimate(array $items): array
    {
        $formatted = [];

        foreach ($items as $index => $scope) {
            if (!is_string($scope)) {
                continue;
            }

            $formatted[] = [
                'scope_id' => 'estimate_scope_' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'scope_name' => $scope,
                'pricing_status' => 'scope_needs_confirmation',
            ];
        }

        return $formatted;
    }

    /**
     * Format scope items for introduction plan.
     *
     * @param array<int, string> $items
     * @return array<int, array<string, mixed>>
     */
    public function formatForIntroduction(array $items): array
    {
        $formatted = [];

        foreach ($items as $index => $scope) {
            if (!is_string($scope)) {
                continue;
            }

            $formatted[] = [
                'scope_id' => 'introduction_scope_' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'scope_name' => $scope,
                'phase_candidate' => $this->detectPhaseCandidate($scope),
            ];
        }

        return $formatted;
    }

    /**
     * Format scope candidates for SASS bridge.
     *
     * @param array<int, array<string, mixed>> $candidates
     * @return array<int, array<string, mixed>>
     */
    public function formatForSassBridge(array $candidates): array
    {
        $formatted = [];

        foreach ($candidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            $formatted[] = [
                'scope_name' => (string) ($candidate['scope_name'] ?? ''),
                'priority' => (int) ($candidate['priority'] ?? 999),
                'sass_connection_type' => (string) ($candidate['sass_connection_type'] ?? 'pre_improvement'),
                'note' => (string) ($candidate['note'] ?? ''),
            ];
        }

        return $formatted;
    }

    /**
     * Build compact scope summary.
     *
     * @param array<int, string> $items
     * @return array<string, mixed>
     */
    public function buildSummary(array $items): array
    {
        $unique = array_values(array_unique(array_filter(
            $items,
            static fn (mixed $item): bool => is_string($item) && trim($item) !== ''
        )));

        return [
            'total' => count($unique),
            'items' => $unique,
            'message' => $this->buildMessage($unique),
        ];
    }

    /**
     * @param array<int, string> $items
     */
    private function buildMessage(array $items): string
    {
        if ($items === []) {
            return '導入範囲は確認が必要です。';
        }

        return '今回の導入候補範囲は、' . implode('、', $items) . 'です。';
    }

    private function detectPhaseCandidate(string $scope): string
    {
        if (str_contains($scope, '導入前')) {
            return '導入前改善';
        }

        if (str_contains($scope, '情報構造')) {
            return '情報構造改善';
        }

        if (str_contains($scope, 'コンテンツ')) {
            return 'コンテンツ改善';
        }

        if (str_contains($scope, '導線')) {
            return '導線改善';
        }

        if (str_contains($scope, '運用')) {
            return '運用改善';
        }

        return '導入範囲確認';
    }
}