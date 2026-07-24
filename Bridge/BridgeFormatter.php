<?php

declare(strict_types=1);

/**
 * SAIS - Bridge Formatter
 *
 * Formats SAIS output data for SASS bridge.
 */

final class BridgeFormatter
{
    /**
     * Format priority tasks from introduction plan.
     *
     * @param array<string, mixed> $introductionPlan
     * @return array<int, array<string, mixed>>
     */
    public function formatPriorityTasks(array $introductionPlan): array
    {
        $tasks = $introductionPlan['tasks'] ?? [];

        if (!is_array($tasks)) {
            return [];
        }

        $formatted = [];

        foreach ($tasks as $task) {
            if (!is_array($task)) {
                continue;
            }

            $taskName = trim((string) ($task['task_name'] ?? ''));

            if ($taskName === '') {
                continue;
            }

            $formatted[] = [
                'task_id' => (string) ($task['task_id'] ?? ''),
                'phase_id' => (string) ($task['phase_id'] ?? ''),
                'task_name' => $taskName,
                'priority' => (string) ($task['priority'] ?? 'medium'),
                'sass_connection' => (string) ($task['sass_connection'] ?? ''),
                'required_check' => $this->normalizeStringList($task['required_check'] ?? []),
            ];
        }

        $formatted = $this->uniquePriorityTasksByName($formatted);

        usort(
            $formatted,
            static fn (array $a, array $b): int => self::priorityWeight((string) ($a['priority'] ?? 'medium')) <=> self::priorityWeight((string) ($b['priority'] ?? 'medium'))
        );

        return $formatted;
    }

    /**
     * Format SASS scope candidates.
     *
     * @param array<string, mixed> $sassScopeCandidate
     * @return array<int, array<string, mixed>>
     */
    public function formatScopeCandidates(array $sassScopeCandidate): array
    {
        $candidates = $sassScopeCandidate['candidates'] ?? [];

        if (!is_array($candidates)) {
            return [];
        }

        $formatted = [];

        foreach ($candidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            $formatted[] = [
                'scope_name' => (string) ($candidate['scope_name'] ?? ''),
                'priority' => (int) ($candidate['priority'] ?? 999),
                'impact' => (string) ($candidate['impact'] ?? 'medium'),
                'difficulty' => (string) ($candidate['difficulty'] ?? 'medium'),
                'sass_connection_type' => (string) ($candidate['sass_connection_type'] ?? 'pre_improvement'),
                'note' => (string) ($candidate['note'] ?? ''),
            ];
        }

        usort(
            $formatted,
            static fn (array $a, array $b): int => ((int) ($a['priority'] ?? 999)) <=> ((int) ($b['priority'] ?? 999))
        );

        return $formatted;
    }

    /**
     * Format recommended modules.
     *
     * @param array<string, mixed> $sassScopeCandidate
     * @return array<int, string>
     */
    public function formatRecommendedModules(array $sassScopeCandidate): array
    {
        return $this->normalizeStringList($sassScopeCandidate['recommended_modules'] ?? []);
    }

    /**
     * Format operation candidates.
     *
     * @param array<string, mixed> $sassScopeCandidate
     * @return array<int, string>
     */
    public function formatOperationCandidates(array $sassScopeCandidate): array
    {
        return $this->normalizeStringList($sassScopeCandidate['operation_candidates'] ?? []);
    }

    /**
     * Format implementation notes.
     *
     * @param array<string, mixed> $sassScopeCandidate
     * @param array<string, mixed> $estimate
     * @return array<int, string>
     */
    public function formatImplementationNotes(array $sassScopeCandidate, array $estimate = []): array
    {
        $notes = $this->normalizeStringList($sassScopeCandidate['implementation_notes'] ?? []);

        $pricingNote = $estimate['pricing_note'] ?? '';

        if (is_string($pricingNote) && trim($pricingNote) !== '') {
            $notes[] = trim($pricingNote);
        }

        return array_values(array_unique($notes));
    }

    /**
     * Format additional checks.
     *
     * @param array<string, mixed> $additionalCheckResult
     * @param array<string, mixed> $estimate
     * @return array<int, array<string, mixed>>
     */
    public function formatAdditionalChecks(array $additionalCheckResult = [], array $estimate = []): array
    {
        $checks = [];

        $sassChecks = $additionalCheckResult['sass_additional_checks'] ?? [];

        if (is_array($sassChecks)) {
            foreach ($sassChecks as $check) {
                if (!is_array($check)) {
                    continue;
                }

                $checks[] = [
                    'type' => (string) ($check['type'] ?? 'general'),
                    'message' => (string) ($check['message'] ?? ''),
                    'target' => (string) ($check['target'] ?? ''),
                    'impact' => (string) ($check['impact'] ?? 'needs_confirmation'),
                ];
            }
        }

        $requiredChecks = $estimate['required_check'] ?? [];

        if (is_array($requiredChecks)) {
            foreach ($requiredChecks as $check) {
                if (!is_string($check) || trim($check) === '') {
                    continue;
                }

                $checks[] = [
                    'type' => 'estimate',
                    'message' => trim($check),
                    'target' => 'estimate',
                    'impact' => 'estimate_needs_confirmation',
                ];
            }
        }

        return $this->uniqueChecks($checks);
    }

    /**
     * @param array<int, array<string, mixed>> $tasks
     * @return array<int, array<string, mixed>>
     */
    private function uniquePriorityTasksByName(array $tasks): array
    {
        $unique = [];

        foreach ($tasks as $task) {
            $taskName = (string) ($task['task_name'] ?? '');

            if ($taskName === '') {
                continue;
            }

            if (!isset($unique[$taskName])) {
                $unique[$taskName] = $task;
                continue;
            }

            $existing = $unique[$taskName];

            $existingPriority = (string) ($existing['priority'] ?? 'medium');
            $currentPriority = (string) ($task['priority'] ?? 'medium');

            if (self::priorityWeight($currentPriority) < self::priorityWeight($existingPriority)) {
                $task['required_check'] = $this->mergeStringLists(
                    $existing['required_check'] ?? [],
                    $task['required_check'] ?? []
                );

                if ((string) ($task['sass_connection'] ?? '') === '') {
                    $task['sass_connection'] = (string) ($existing['sass_connection'] ?? '');
                }

                $unique[$taskName] = $task;
                continue;
            }

            $existing['required_check'] = $this->mergeStringLists(
                $existing['required_check'] ?? [],
                $task['required_check'] ?? []
            );

            if ((string) ($existing['sass_connection'] ?? '') === '' && (string) ($task['sass_connection'] ?? '') !== '') {
                $existing['sass_connection'] = (string) $task['sass_connection'];
            }

            $unique[$taskName] = $existing;
        }

        return array_values($unique);
    }

    /**
     * @param mixed $items
     * @return array<int, string>
     */
    private function normalizeStringList(mixed $items): array
    {
        if (!is_array($items)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(
                static fn (mixed $item): string => is_scalar($item) ? trim((string) $item) : '',
                $items
            ),
            static fn (string $item): bool => $item !== ''
        )));
    }

    /**
     * @param mixed $first
     * @param mixed $second
     * @return array<int, string>
     */
    private function mergeStringLists(mixed $first, mixed $second): array
    {
        return array_values(array_unique(array_merge(
            $this->normalizeStringList(is_array($first) ? $first : []),
            $this->normalizeStringList(is_array($second) ? $second : [])
        )));
    }

    /**
     * @param array<int, array<string, mixed>> $checks
     * @return array<int, array<string, mixed>>
     */
    private function uniqueChecks(array $checks): array
    {
        $unique = [];

        foreach ($checks as $check) {
            $key = (string) ($check['type'] ?? '') . ':' . (string) ($check['message'] ?? '') . ':' . (string) ($check['target'] ?? '');
            $unique[$key] = $check;
        }

        return array_values($unique);
    }

    private static function priorityWeight(string $priority): int
    {
        return match ($priority) {
            'high' => 1,
            'medium' => 2,
            'low' => 3,
            default => 9,
        };
    }
}