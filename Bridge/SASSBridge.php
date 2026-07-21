<?php

declare(strict_types=1);

/**
 * SAIS - SASS Bridge
 *
 * Builds bridge data from SAIS to SASS.
 */

require_once __DIR__ . '/BridgeFormatter.php';

final class SASSBridge
{
    private BridgeFormatter $formatter;

    public function __construct()
    {
        $this->formatter = new BridgeFormatter();
    }

    /**
     * Build SASS bridge output.
     *
     * @param array<string, mixed> $input
     * @param array<string, mixed> $sassScopeCandidate
     * @param array<string, mixed> $introductionPlan
     * @param array<string, mixed> $estimate
     * @param array<string, mixed> $additionalCheckResult
     * @return array<string, mixed>
     */
    public function build(
        array $input,
        array $sassScopeCandidate,
        array $introductionPlan,
        array $estimate = [],
        array $additionalCheckResult = []
    ): array {
        $target = $this->extractTarget($input);
        $scopeCandidates = $this->formatter->formatScopeCandidates($sassScopeCandidate);
        $priorityTasks = $this->formatter->formatPriorityTasks($introductionPlan);
        $recommendedModules = $this->formatter->formatRecommendedModules($sassScopeCandidate);
        $operationCandidates = $this->formatter->formatOperationCandidates($sassScopeCandidate);
        $implementationNotes = $this->formatter->formatImplementationNotes($sassScopeCandidate, $estimate);
        $additionalChecks = $this->formatter->formatAdditionalChecks($additionalCheckResult, $estimate);

        return [
            'system' => 'SAIS',
            'target_system' => 'SASS',
            'project' => 'SEEN',
            'target' => $target,
            'sass_scope_candidate' => $scopeCandidates,
            'priority_tasks' => $priorityTasks,
            'recommended_modules' => $recommendedModules,
            'operation_candidates' => $operationCandidates,
            'implementation_notes' => $implementationNotes,
            'additional_checks' => $additionalChecks,
            'metadata' => $this->buildMetadata(
                count($scopeCandidates),
                count($priorityTasks),
                count($recommendedModules),
                count($additionalChecks)
            ),
        ];
    }

    /**
     * Build compact bridge summary.
     *
     * @param array<string, mixed> $sassBridge
     * @return array<string, mixed>
     */
    public function buildSummary(array $sassBridge): array
    {
        $scopeCandidates = $sassBridge['sass_scope_candidate'] ?? [];
        $priorityTasks = $sassBridge['priority_tasks'] ?? [];
        $recommendedModules = $sassBridge['recommended_modules'] ?? [];
        $additionalChecks = $sassBridge['additional_checks'] ?? [];

        return [
            'target_system' => (string) ($sassBridge['target_system'] ?? 'SASS'),
            'scope_candidate_count' => is_array($scopeCandidates) ? count($scopeCandidates) : 0,
            'priority_task_count' => is_array($priorityTasks) ? count($priorityTasks) : 0,
            'recommended_module_count' => is_array($recommendedModules) ? count($recommendedModules) : 0,
            'additional_check_count' => is_array($additionalChecks) ? count($additionalChecks) : 0,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function extractTarget(array $input): array
    {
        $target = $input['target'] ?? [];

        if (is_array($target)) {
            return $target;
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMetadata(
        int $scopeCandidateCount,
        int $priorityTaskCount,
        int $recommendedModuleCount,
        int $additionalCheckCount
    ): array {
        return [
            'generated_at' => date('c'),
            'bridge_name' => 'SAIS to SASS Bridge',
            'source_engine' => 'SAIS',
            'target_engine' => 'SASS',
            'scope_candidate_count' => $scopeCandidateCount,
            'priority_task_count' => $priorityTaskCount,
            'recommended_module_count' => $recommendedModuleCount,
            'additional_check_count' => $additionalCheckCount,
        ];
    }
}