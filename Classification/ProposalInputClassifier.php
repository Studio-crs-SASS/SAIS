<?php

declare(strict_types=1);

/**
 * SAIS - Proposal Input Classifier
 *
 * Classifies proposal_inputs from SADS SAISBridge Output.
 */

final class ProposalInputClassifier
{
    /**
     * Classify proposal inputs.
     *
     * @param array<int, array<string, mixed>> $proposalInputs
     * @return array<string, mixed>
     */
    public function classify(array $proposalInputs): array
    {
        $items = $this->normalizeItems($proposalInputs);

        return [
            'items' => $items,
            'by_category' => $this->groupByField($items, 'category'),
            'by_impact' => $this->groupByField($items, 'impact'),
            'by_difficulty' => $this->groupByField($items, 'difficulty'),
            'high_impact_items' => $this->filterByField($items, 'impact', 'high'),
            'high_difficulty_items' => $this->filterByField($items, 'difficulty', 'high'),
            'sorted_by_priority' => $this->sortByPriority($items),
            'summary' => [
                'total' => count($items),
                'category_count' => count($this->groupByField($items, 'category')),
                'high_impact_count' => count($this->filterByField($items, 'impact', 'high')),
                'high_difficulty_count' => count($this->filterByField($items, 'difficulty', 'high')),
            ],
        ];
    }

    /**
     * Normalize proposal input items.
     *
     * @param array<int, array<string, mixed>> $proposalInputs
     * @return array<int, array<string, mixed>>
     */
    private function normalizeItems(array $proposalInputs): array
    {
        $items = [];

        foreach ($proposalInputs as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $items[] = [
                'indicator_id' => (string) ($item['indicator_id'] ?? 'unknown_' . ($index + 1)),
                'category' => (string) ($item['category'] ?? 'unknown'),
                'title' => (string) ($item['title'] ?? ''),
                'summary' => (string) ($item['summary'] ?? ''),
                'action' => (string) ($item['action'] ?? ''),
                'expected_effect' => (string) ($item['expected_effect'] ?? ''),
                'impact' => (string) ($item['impact'] ?? 'medium'),
                'difficulty' => (string) ($item['difficulty'] ?? 'medium'),
                'priority_rank' => $this->normalizePriorityRank($item['priority_rank'] ?? ($index + 1)),
                'recommended_service_scope' => (string) ($item['recommended_service_scope'] ?? ''),
            ];
        }

        return $items;
    }

    private function normalizePriorityRank(mixed $value): int
    {
        if (is_numeric($value)) {
            return max(1, (int) $value);
        }

        return 999;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function groupByField(array $items, string $field): array
    {
        $grouped = [];

        foreach ($items as $item) {
            $key = isset($item[$field]) && is_scalar($item[$field])
                ? (string) $item[$field]
                : 'unknown';

            if (!isset($grouped[$key])) {
                $grouped[$key] = [];
            }

            $grouped[$key][] = $item;
        }

        ksort($grouped);

        return $grouped;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function filterByField(array $items, string $field, string $value): array
    {
        return array_values(array_filter(
            $items,
            static fn (array $item): bool => ($item[$field] ?? null) === $value
        ));
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function sortByPriority(array $items): array
    {
        usort(
            $items,
            static fn (array $a, array $b): int => ((int) ($a['priority_rank'] ?? 999)) <=> ((int) ($b['priority_rank'] ?? 999))
        );

        return $items;
    }
}