<?php

declare(strict_types=1);

/**
 * SAIS - Introduction Task Builder
 *
 * Builds introduction tasks from phases and estimate items.
 */

final class IntroductionTaskBuilder
{
    /**
     * Build tasks.
     *
     * @param array<int, array<string, mixed>> $phases
     * @param array<int, array<string, mixed>> $estimateItems
     * @return array<int, array<string, mixed>>
     */
    public function build(array $phases, array $estimateItems = []): array
    {
        $tasks = [];

        foreach ($phases as $phaseIndex => $phase) {
            if (!is_array($phase)) {
                continue;
            }

            $phaseTasks = $phase['tasks'] ?? [];

            if (!is_array($phaseTasks)) {
                continue;
            }

            foreach ($phaseTasks as $taskIndex => $taskName) {
                if (!is_string($taskName) || trim($taskName) === '') {
                    continue;
                }

                $tasks[] = [
                    'task_id' => 'task_' . str_pad((string) (count($tasks) + 1), 3, '0', STR_PAD_LEFT),
                    'phase_id' => (string) ($phase['phase_id'] ?? 'phase_' . str_pad((string) ($phaseIndex + 1), 3, '0', STR_PAD_LEFT)),
                    'phase_name' => (string) ($phase['phase_name'] ?? ''),
                    'task_name' => trim($taskName),
                    'priority' => (string) ($phase['priority'] ?? 'medium'),
                    'sass_connection' => (string) ($phase['sass_connection'] ?? ''),
                    'required_check' => $this->detectRequiredCheck($taskName, $phase, $estimateItems),
                    'order' => $taskIndex + 1,
                ];
            }
        }

        return $tasks;
    }

    /**
     * Build task summary.
     *
     * @param array<int, array<string, mixed>> $tasks
     * @return array<string, mixed>
     */
    public function buildSummary(array $tasks): array
    {
        return [
            'total' => count($tasks),
            'high_priority_count' => count(array_filter(
                $tasks,
                static fn (array $task): bool => ($task['priority'] ?? '') === 'high'
            )),
            'sass_connected_count' => count(array_filter(
                $tasks,
                static fn (array $task): bool => trim((string) ($task['sass_connection'] ?? '')) !== ''
            )),
            'first_task' => isset($tasks[0]['task_name']) ? (string) $tasks[0]['task_name'] : '',
        ];
    }

    /**
     * @param array<string, mixed> $phase
     * @param array<int, array<string, mixed>> $estimateItems
     * @return array<int, string>
     */
    private function detectRequiredCheck(string $taskName, array $phase, array $estimateItems): array
    {
        $checks = [];

        $phaseChecks = $phase['required_check'] ?? [];

        if (is_array($phaseChecks)) {
            foreach ($phaseChecks as $check) {
                if (is_string($check) && trim($check) !== '') {
                    $checks[] = trim($check);
                }
            }
        }

        foreach ($estimateItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            $title = (string) ($item['title'] ?? '');

            if ($title !== '' && str_contains($taskName, mb_substr($title, 0, 2))) {
                $required = $item['required_check'] ?? [];

                if (is_array($required)) {
                    foreach ($required as $check) {
                        if (is_string($check) && trim($check) !== '') {
                            $checks[] = trim($check);
                        }
                    }
                }
            }
        }

        return array_values(array_unique($checks));
    }
}