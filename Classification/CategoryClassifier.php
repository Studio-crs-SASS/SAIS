<?php

declare(strict_types=1);

/**
 * SAIS - Category Classifier
 *
 * Classifies items by SAIS/SADS category.
 */

final class CategoryClassifier
{
    /** @var array<string, string> */
    private array $categoryLabels = [
        'depth' => '情報構造',
        'volume' => '情報量',
        'relationship' => '情報接続',
        'flow' => '行動導線',
        'unknown' => '未分類',
    ];

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<string, mixed>
     */
    public function classify(array $items): array
    {
        $grouped = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $category = $this->normalizeCategory($item['category'] ?? 'unknown');

            if (!isset($grouped[$category])) {
                $grouped[$category] = [
                    'category' => $category,
                    'label' => $this->getLabel($category),
                    'items' => [],
                    'count' => 0,
                ];
            }

            $grouped[$category]['items'][] = $item;
            $grouped[$category]['count']++;
        }

        return [
            'categories' => $grouped,
            'summary' => [
                'category_count' => count($grouped),
                'total_items' => array_sum(array_map(
                    static fn (array $category): int => (int) ($category['count'] ?? 0),
                    $grouped
                )),
                'main_category' => $this->detectMainCategory($grouped),
            ],
        ];
    }

    public function normalizeCategory(mixed $category): string
    {
        if (!is_scalar($category)) {
            return 'unknown';
        }

        $value = strtolower(trim((string) $category));

        return match ($value) {
            'depth' => 'depth',
            'volume' => 'volume',
            'relationship' => 'relationship',
            'flow' => 'flow',
            default => 'unknown',
        };
    }

    public function getLabel(string $category): string
    {
        return $this->categoryLabels[$category] ?? $this->categoryLabels['unknown'];
    }

    /**
     * @param array<string, array<string, mixed>> $grouped
     */
    private function detectMainCategory(array $grouped): string
    {
        if ($grouped === []) {
            return 'unknown';
        }

        uasort(
            $grouped,
            static fn (array $a, array $b): int => ((int) ($b['count'] ?? 0)) <=> ((int) ($a['count'] ?? 0))
        );

        $first = array_key_first($grouped);

        return is_string($first) ? $first : 'unknown';
    }
}