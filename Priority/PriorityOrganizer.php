<?php

declare(strict_types=1);

/**
 * SAIS - Priority Organizer
 *
 * Organizes SADS priority_items for SAIS proposal, estimate, and introduction plan.
 */

final class PriorityOrganizer
{
    /**
     * Organize priority items.
     *
     * @param array<int, array<string, mixed>> $priorityItems
     * @return array<string, mixed>
     */
    public function organize(array $priorityItems): array
    {
        $items = $this->normalizeItems($priorityItems);
        $sortedItems = $this->sortByRank($items);

        return [
            'items' => $sortedItems,
            'top_priority_items' => array_slice($sortedItems, 0, 3),
            'primary_item' => $sortedItems[0] ?? null,
            'by_category' => $this->groupByCategory($sortedItems),
            'priority_ids' => $this->extractIndicatorIds($sortedItems),
            'summary' => [
                'total' => count($sortedItems),
                'top_count' => min(3, count($sortedItems)),
                'primary_indicator_id' => isset($sortedItems[0]['indicator_id'])
                    ? (string) $sortedItems[0]['indicator_id']
                    : '',
                'primary_category' => isset($sortedItems[0]['category'])
                    ? (string) $sortedItems[0]['category']
                    : 'unknown',
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $priorityItems
     * @return array<int, array<string, mixed>>
     */
    private function normalizeItems(array $priorityItems): array
    {
        $items = [];

        foreach ($priorityItems as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $items[] = [
                'rank' => $this->normalizeRank($item['rank'] ?? ($index + 1)),
                'indicator_id' => (string) ($item['indicator_id'] ?? 'unknown_' . ($index + 1)),
                'indicator_name' => (string) ($item['indicator_name'] ?? ''),
                'category' => (string) ($item['category'] ?? 'unknown'),
                'internal_score' => $this->normalizeFloat($item['internal_score'] ?? null),
                'display_score' => $this->normalizeFloat($item['display_score'] ?? null),
                'recommendation' => $this->normalizeRecommendation($item['recommendation'] ?? []),
                'reason' => (string) ($item['reason'] ?? ''),
                'improvement_comment' => (string) ($item['improvement_comment'] ?? ''),
            ];
        }

        return $items;
    }

    private function normalizeRank(mixed $value): int
    {
        if (is_numeric($value)) {
            return max(1, (int) $value);
        }

        return 999;
    }

    private function normalizeFloat(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeRecommendation(mixed $recommendation): array
    {
        if (!is_array($recommendation)) {
            return [
                'key' => '',
                'label' => '',
                'sais_connection' => false,
            ];
        }

        return [
            'key' => (string) ($recommendation['key'] ?? ''),
            'label' => (string) ($recommendation['label'] ?? ''),
            'sais_connection' => (bool) ($recommendation['sais_connection'] ?? true),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function sortByRank(array $items): array
    {
        usort(
            $items,
            static fn (array $a, array $b): int => ((int) ($a['rank'] ?? 999)) <=> ((int) ($b['rank'] ?? 999))
        );

        return $items;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function groupByCategory(array $items): array
    {
        $grouped = [];

        foreach ($items as $item) {
            $category = isset($item['category']) && is_scalar($item['category'])
                ? (string) $item['category']
                : 'unknown';

            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }

            $grouped[$category][] = $item;
        }

        ksort($grouped);

        return $grouped;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, string>
     */
    private function extractIndicatorIds(array $items): array
    {
        return array_values(array_map(
            static fn (array $item): string => (string) ($item['indicator_id'] ?? ''),
            $items
        ));
    }
}