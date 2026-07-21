<?php

declare(strict_types=1);

/**
 * SAIS - Introduction Plan Engine
 *
 * Builds introduction plan from proposal, estimate, priority items, and recommended scope.
 */

require_once __DIR__ . '/IntroductionPhaseBuilder.php';
require_once __DIR__ . '/IntroductionTaskBuilder.php';

final class IntroductionPlanEngine
{
    /** @var array<string, mixed> */
    private array $introductionConfig;

    private IntroductionPhaseBuilder $phaseBuilder;
    private IntroductionTaskBuilder $taskBuilder;

    /**
     * @param array<string, mixed> $introductionConfig
     */
    public function __construct(array $introductionConfig)
    {
        $this->introductionConfig = $introductionConfig;
        $this->phaseBuilder = new IntroductionPhaseBuilder($introductionConfig);
        $this->taskBuilder = new IntroductionTaskBuilder();
    }

    /**
     * Build introduction plan.
     *
     * @param array<int, string> $recommendedScope
     * @param array<int, array<string, mixed>> $priorityItems
     * @param array<string, mixed> $estimate
     * @param array<string, mixed> $proposal
     * @return array<string, mixed>
     */
    public function build(
        array $recommendedScope,
        array $priorityItems = [],
        array $estimate = [],
        array $proposal = []
    ): array {
        $estimateItems = $this->extractEstimateItems($estimate);
        $phases = $this->phaseBuilder->build($recommendedScope, $priorityItems, $estimateItems);
        $tasks = $this->taskBuilder->build($phases, $estimateItems);
        $taskSummary = $this->taskBuilder->buildSummary($tasks);

        return [
            'plan_summary' => $this->buildPlanSummary($phases, $tasks, $proposal),
            'phases' => $phases,
            'tasks' => $tasks,
            'priority' => $this->buildPriority($priorityItems),
            'expected_effects' => $this->buildExpectedEffects($phases, $proposal),
            'required_check' => $this->buildRequiredCheck($phases, $estimate),
            'sass_connection' => $this->buildSassConnection($phases),
            'summary_data' => [
                'phase_count' => count($phases),
                'task_count' => count($tasks),
                'high_priority_task_count' => $taskSummary['high_priority_count'] ?? 0,
                'sass_connected_task_count' => $taskSummary['sass_connected_count'] ?? 0,
                'first_task' => $taskSummary['first_task'] ?? '',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $estimate
     * @return array<int, array<string, mixed>>
     */
    private function extractEstimateItems(array $estimate): array
    {
        $items = $estimate['estimate_items'] ?? [];

        if (!is_array($items)) {
            return [];
        }

        return array_values(array_filter(
            $items,
            static fn (mixed $item): bool => is_array($item)
        ));
    }

    /**
     * @param array<int, array<string, mixed>> $phases
     * @param array<int, array<string, mixed>> $tasks
     * @param array<string, mixed> $proposal
     */
    private function buildPlanSummary(array $phases, array $tasks, array $proposal): string
    {
        $default = $this->introductionConfig['default_messages']['plan_summary']
            ?? 'SADS診断結果をもとに、優先改善項目から段階的に導入計画を整理します。';

        $proposalTitle = $proposal['title'] ?? '';

        if (is_string($proposalTitle) && $proposalTitle !== '') {
            return $default . ' 提案テーマは「' . $proposalTitle . '」です。導入フェーズは' . count($phases) . '件、タスクは' . count($tasks) . '件です。';
        }

        return $default . ' 導入フェーズは' . count($phases) . '件、タスクは' . count($tasks) . '件です。';
    }

    /**
     * @param array<int, array<string, mixed>> $priorityItems
     * @return array<int, array<string, mixed>>
     */
    private function buildPriority(array $priorityItems): array
    {
        $items = [];

        foreach ($priorityItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            $items[] = [
                'rank' => (int) ($item['rank'] ?? 999),
                'indicator_id' => (string) ($item['indicator_id'] ?? ''),
                'indicator_name' => (string) ($item['indicator_name'] ?? ''),
                'category' => (string) ($item['category'] ?? 'unknown'),
            ];
        }

        return $items;
    }

    /**
     * @param array<int, array<string, mixed>> $phases
     * @param array<string, mixed> $proposal
     * @return array<int, string>
     */
    private function buildExpectedEffects(array $phases, array $proposal): array
    {
        $effects = [];

        foreach ($phases as $phase) {
            if (!is_array($phase)) {
                continue;
            }

            $effect = (string) ($phase['expected_effect'] ?? '');

            if ($effect !== '') {
                $effects[] = $effect;
            }
        }

        $proposalEffects = $proposal['expected_effects'] ?? [];

        if (is_array($proposalEffects)) {
            foreach ($proposalEffects as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $effect = (string) ($item['expected_effect'] ?? '');

                if ($effect !== '') {
                    $effects[] = $effect;
                }
            }
        }

        return array_values(array_unique($effects));
    }

    /**
     * @param array<int, array<string, mixed>> $phases
     * @param array<string, mixed> $estimate
     * @return array<int, string>
     */
    private function buildRequiredCheck(array $phases, array $estimate): array
    {
        $checks = [];

        foreach ($phases as $phase) {
            if (!is_array($phase)) {
                continue;
            }

            $required = $phase['required_check'] ?? [];

            if (!is_array($required)) {
                continue;
            }

            foreach ($required as $check) {
                if (is_string($check) && trim($check) !== '') {
                    $checks[] = trim($check);
                }
            }
        }

        $estimateChecks = $estimate['required_check'] ?? [];

        if (is_array($estimateChecks)) {
            foreach ($estimateChecks as $check) {
                if (is_string($check) && trim($check) !== '') {
                    $checks[] = trim($check);
                }
            }
        }

        return array_values(array_unique($checks));
    }

    /**
     * @param array<int, array<string, mixed>> $phases
     * @return array<int, string>
     */
    private function buildSassConnection(array $phases): array
    {
        $connections = [];

        foreach ($phases as $phase) {
            if (!is_array($phase)) {
                continue;
            }

            $connection = (string) ($phase['sass_connection'] ?? '');

            if ($connection !== '') {
                $connections[] = $connection;
            }
        }

        return array_values(array_unique($connections));
    }
}