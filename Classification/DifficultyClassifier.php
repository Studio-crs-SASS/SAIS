<?php

declare(strict_types=1);

/**
 * SAIS - Difficulty Classifier
 *
 * Classifies proposal inputs by difficulty.
 */

final class DifficultyClassifier
{
    /** @var array<string, string> */
    private array $difficultyLabels = [
        'high' => '高',
        'medium' => '中',
        'low' => '低',
        'unknown' => '未分類',
    ];

    /** @var array<string, int> */
    private array $difficultyPriority = [
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

            $difficulty = $this->normalizeDifficulty($item['difficulty'] ?? 'medium');

            if (!isset($grouped[$difficulty])) {
                $grouped[$difficulty] = [
                    'difficulty' => $difficulty,
                    'label' => $this->getLabel($difficulty),
                    'priority' => $this->getPriority($difficulty),
                    'items' => [],
                    'count' => 0,
                ];
            }

            $grouped[$difficulty]['items'][] = $item;
            $grouped[$difficulty]['count']++;
        }

        uasort(
            $grouped,
            static fn (array $a, array $b): int => ((int) ($a['priority'] ?? 9)) <=> ((int) ($b['priority'] ?? 9))
        );

        return [
            'difficulties' => $grouped,
            'high_difficulty_items' => $grouped['high']['items'] ?? [],
            'summary' => [
                'difficulty_count' => count($grouped),
                'total_items' => array_sum(array_map(
                    static fn (array $difficulty): int => (int) ($difficulty['count'] ?? 0),
                    $grouped
                )),
                'main_difficulty' => $this->detectMainDifficulty($grouped),
                'has_high_difficulty' => isset($grouped['high']) && (int) $grouped['high']['count'] > 0,
            ],
        ];
    }

    public function normalizeDifficulty(mixed $difficulty): string
    {
        if (!is_scalar($difficulty)) {
            return 'unknown';
        }

        $value = strtolower(trim((string) $difficulty));

        return match ($value) {
            'high' => 'high',
            'medium' => 'medium',
            'low' => 'low',
            default => 'unknown',
        };
    }

    public function getLabel(string $difficulty): string
    {
        return $this->difficultyLabels[$difficulty] ?? $this->difficultyLabels['unknown'];
    }

    public function getPriority(string $difficulty): int
    {
        return $this->difficultyPriority[$difficulty] ?? $this->difficultyPriority['unknown'];
    }

    /**
     * @param array<string, array<string, mixed>> $grouped
     */
    private function detectMainDifficulty(array $grouped): string
    {
        if ($grouped === []) {
            return 'unknown';
        }

        foreach (['high', 'medium', 'low', 'unknown'] as $difficulty) {
            if (isset($grouped[$difficulty]) && (int) ($grouped[$difficulty]['count'] ?? 0) > 0) {
                return $difficulty;
            }
        }

        return 'unknown';
    }
}