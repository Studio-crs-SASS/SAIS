<?php

declare(strict_types=1);

/**
 * SAIS - Impact Classifier
 *
 * Classifies proposal inputs by impact.
 */

final class ImpactClassifier
{
    /** @var array<string, string> */
    private array $impactLabels = [
        'high' => '高',
        'medium' => '中',
        'low' => '低',
        'unknown' => '未分類',
    ];

    /** @var array<string, int> */
    private array $impactPriority = [
        'high' => 1,
        'medium' => 2,
        'low' => 3,
        'unknown' => 9,
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

            $impact = $this->normalizeImpact($item['impact'] ?? 'medium');

            if (!isset($grouped[$impact])) {
                $grouped[$impact] = [
                    'impact' => $impact,
                    'label' => $this->getLabel($impact),
                    'priority' => $this->getPriority($impact),
                    'items' => [],
                    'count' => 0,
                ];
            }

            $grouped[$impact]['items'][] = $item;
            $grouped[$impact]['count']++;
        }

        uasort(
            $grouped,
            static fn (array $a, array $b): int => ((int) ($a['priority'] ?? 9)) <=> ((int) ($b['priority'] ?? 9))
        );

        return [
            'impacts' => $grouped,
            'high_impact_items' => $grouped['high']['items'] ?? [],
            'summary' => [
                'impact_count' => count($grouped),
                'total_items' => array_sum(array_map(
                    static fn (array $impact): int => (int) ($impact['count'] ?? 0),
                    $grouped
                )),
                'main_impact' => $this->detectMainImpact($grouped),
                'has_high_impact' => isset($grouped['high']) && (int) $grouped['high']['count'] > 0,
            ],
        ];
    }

    public function normalizeImpact(mixed $impact): string
    {
        if (!is_scalar($impact)) {
            return 'unknown';
        }

        $value = strtolower(trim((string) $impact));

        return match ($value) {
            'high' => 'high',
            'medium' => 'medium',
            'low' => 'low',
            default => 'unknown',
        };
    }

    public function getLabel(string $impact): string
    {
        return $this->impactLabels[$impact] ?? $this->impactLabels['unknown'];
    }

    public function getPriority(string $impact): int
    {
        return $this->impactPriority[$impact] ?? $this->impactPriority['unknown'];
    }

    /**
     * @param array<string, array<string, mixed>> $grouped
     */
    private function detectMainImpact(array $grouped): string
    {
        if ($grouped === []) {
            return 'unknown';
        }

        foreach (['high', 'medium', 'low', 'unknown'] as $impact) {
            if (isset($grouped[$impact]) && (int) ($grouped[$impact]['count'] ?? 0) > 0) {
                return $impact;
            }
        }

        return 'unknown';
    }
}