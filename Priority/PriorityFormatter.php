<?php

declare(strict_types=1);

/**
 * SAIS - Priority Formatter
 *
 * Formats organized priority items for proposal, estimate, and introduction plan.
 */

final class PriorityFormatter
{
    /**
     * Format priority items for proposal display.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    public function formatForProposal(array $items): array
    {
        $formatted = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $formatted[] = [
                'rank' => (int) ($item['rank'] ?? 999),
                'indicator_id' => (string) ($item['indicator_id'] ?? ''),
                'indicator_name' => (string) ($item['indicator_name'] ?? ''),
                'category' => (string) ($item['category'] ?? 'unknown'),
                'display_score' => $item['display_score'] ?? null,
                'recommendation_label' => $this->extractRecommendationLabel($item),
                'reason' => (string) ($item['reason'] ?? ''),
                'improvement_comment' => (string) ($item['improvement_comment'] ?? ''),
            ];
        }

        return $formatted;
    }

    /**
     * Format priority items for estimate order.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    public function formatForEstimate(array $items): array
    {
        $formatted = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $formatted[] = [
                'priority_rank' => (int) ($item['rank'] ?? 999),
                'source_indicator_id' => (string) ($item['indicator_id'] ?? ''),
                'title' => (string) ($item['indicator_name'] ?? ''),
                'category' => (string) ($item['category'] ?? 'unknown'),
                'score_reference' => $item['display_score'] ?? null,
            ];
        }

        return $formatted;
    }

    /**
     * Format priority items for introduction plan.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    public function formatForIntroduction(array $items): array
    {
        $formatted = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $rank = (int) ($item['rank'] ?? 999);

            $formatted[] = [
                'priority_rank' => $rank,
                'priority_label' => $this->buildPriorityLabel($rank),
                'indicator_id' => (string) ($item['indicator_id'] ?? ''),
                'task_name' => (string) ($item['indicator_name'] ?? ''),
                'category' => (string) ($item['category'] ?? 'unknown'),
            ];
        }

        return $formatted;
    }

    /**
     * Build compact summary.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<string, mixed>
     */
    public function buildSummary(array $items): array
    {
        $primary = $items[0] ?? null;

        return [
            'total' => count($items),
            'primary_indicator_id' => is_array($primary) ? (string) ($primary['indicator_id'] ?? '') : '',
            'primary_indicator_name' => is_array($primary) ? (string) ($primary['indicator_name'] ?? '') : '',
            'primary_category' => is_array($primary) ? (string) ($primary['category'] ?? 'unknown') : 'unknown',
            'top_three' => array_slice($this->formatForProposal($items), 0, 3),
        ];
    }

    /**
     * @param array<string, mixed> $item
     */
    private function extractRecommendationLabel(array $item): string
    {
        $recommendation = $item['recommendation'] ?? [];

        if (!is_array($recommendation)) {
            return '';
        }

        return (string) ($recommendation['label'] ?? '');
    }

    private function buildPriorityLabel(int $rank): string
    {
        return match (true) {
            $rank === 1 => '最優先',
            $rank === 2 => '優先',
            $rank === 3 => '重要',
            default => '補助',
        };
    }
}