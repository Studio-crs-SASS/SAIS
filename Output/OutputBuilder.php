<?php

declare(strict_types=1);

/**
 * SAIS - Output Builder
 *
 * Builds final SAIS output JSON structure.
 */

require_once __DIR__ . '/ProposalDataBuilder.php';
require_once __DIR__ . '/WarningBuilder.php';
require_once __DIR__ . '/ErrorBuilder.php';

final class OutputBuilder
{
    /** @var array<string, mixed> */
    private array $outputConfig;

    /** @var array<string, mixed> */
    private array $warningsConfig;

    private ProposalDataBuilder $proposalDataBuilder;
    private WarningBuilder $warningBuilder;
    private ErrorBuilder $errorBuilder;

    /**
     * @param array<string, mixed> $outputConfig
     * @param array<string, mixed> $warningsConfig
     */
    public function __construct(array $outputConfig, array $warningsConfig)
    {
        $this->outputConfig = $outputConfig;
        $this->warningsConfig = $warningsConfig;
        $this->proposalDataBuilder = new ProposalDataBuilder($outputConfig);
        $this->warningBuilder = new WarningBuilder($warningsConfig);
        $this->errorBuilder = new ErrorBuilder($warningsConfig);
    }

    /**
     * Build success output.
     *
     * @param array<string, mixed> $input
     * @param array<string, mixed> $proposal
     * @param array<string, mixed> $estimate
     * @param array<string, mixed> $introductionPlan
     * @param array<string, mixed> $sassScopeCandidate
     * @param array<string, mixed> $additionalCheckResult
     * @param array<int, array<string, mixed>> $warnings
     * @param array<string, mixed> $sassBridge
     * @return array<string, mixed>
     */
    public function buildSuccess(
        array $input,
        array $proposal,
        array $estimate,
        array $introductionPlan,
        array $sassScopeCandidate,
        array $additionalCheckResult = [],
        array $warnings = [],
        array $sassBridge = []
    ): array {
        $target = $this->extractTarget($input);
        $additionalCheckItems = $input['additional_check_items'] ?? [];

        if (!is_array($additionalCheckItems)) {
            $additionalCheckItems = [];
        }

        $sassReverseSummary = $this->buildSassReverseSummary(
            $sassBridge,
            $sassScopeCandidate,
            $additionalCheckItems
        );
        
        $estimate = $this->attachSassEstimateConnection($estimate, $sassReverseSummary);
        $introductionPlan = $this->attachSassOperationConnection($introductionPlan, $sassReverseSummary);

        $proposalData = $this->proposalDataBuilder->build(
            $target,
            $proposal,
            $estimate,
            $introductionPlan,
            $sassScopeCandidate,
            $additionalCheckItems,
            $sassReverseSummary
        );

        $conditionWarnings = $this->warningBuilder->buildFromConditions(
            $this->extractArray($input, 'confidence'),
            $this->extractList($input, 'proposal_inputs'),
            $this->extractList($input, 'priority_items'),
            $this->extractList($input, 'recommended_scope'),
            $additionalCheckItems
        );

        $estimateWarnings = $this->warningBuilder->buildEstimateWarnings($estimate);
        $sassWarnings = $this->warningBuilder->buildSassScopeWarnings($sassScopeCandidate);
        $additionalWarnings = $this->extractWarningsFromAdditionalCheck($additionalCheckResult);

        $mergedWarnings = $this->warningBuilder->merge(
            $warnings,
            $conditionWarnings,
            $estimateWarnings,
            $sassWarnings,
            $additionalWarnings
        );

        return [
            'status' => 'success',
            'system' => $this->getDefault('system', 'SAIS'),
            'version' => $this->getDefault('version', '1.0'),
            'project' => $this->getDefault('project', 'SEEN'),
            'target' => $target,
            'source' => [
                'system' => (string) ($input['system'] ?? 'SADS'),
                'target_system' => (string) ($input['target_system'] ?? 'SAIS'),
                'version' => (string) ($input['version'] ?? ''),
            ],
            'proposal' => $proposal,
            'estimate' => $estimate,
            'introduction_plan' => $introductionPlan,
            'sass_scope_candidate' => $sassScopeCandidate,
            'additional_check_items' => $additionalCheckItems,
            'proposal_data' => $proposalData,
            'sass_reverse_summary' => $sassReverseSummary,
            'sass_bridge' => $sassBridge,
            'warnings' => $mergedWarnings,
            'metadata' => $this->buildMetadata(),
        ];
    }

    /**
     * Build failed output.
     *
     * @param array<int, array<string, mixed>> $errors
     * @param array<int, array<string, mixed>> $warnings
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function buildFailed(array $errors, array $warnings = [], array $input = []): array
    {
        return [
            'status' => 'failed',
            'system' => $this->getDefault('system', 'SAIS'),
            'version' => $this->getDefault('version', '1.0'),
            'project' => $this->getDefault('project', 'SEEN'),
            'target' => $this->extractTarget($input),
            'source' => [
                'system' => (string) ($input['system'] ?? ''),
                'target_system' => (string) ($input['target_system'] ?? ''),
                'version' => (string) ($input['version'] ?? ''),
            ],
            'errors' => $this->errorBuilder->merge($errors),
            'warnings' => $this->warningBuilder->merge($warnings),
            'metadata' => $this->buildMetadata(),
        ];
    }

    /**
     * Build failed output from error code.
     *
     * @param array<int, string> $affectedItems
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function buildFailedByCode(string $code, array $affectedItems = [], array $input = []): array
    {
        return $this->buildFailed(
            [
                $this->errorBuilder->build($code, $affectedItems),
            ],
            [],
            $input
        );
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
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function extractArray(array $input, string $key): array
    {
        $value = $input[$key] ?? [];

        return is_array($value) ? $value : [];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<int, mixed>
     */
    private function extractList(array $input, string $key): array
    {
        $value = $input[$key] ?? [];

        if (!is_array($value) || !array_is_list($value)) {
            return [];
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $additionalCheckResult
     * @return array<int, array<string, mixed>>
     */
    private function extractWarningsFromAdditionalCheck(array $additionalCheckResult): array
    {
        $warnings = $additionalCheckResult['warnings'] ?? [];

        if (!is_array($warnings)) {
            return [];
        }

        return array_values(array_filter(
            $warnings,
            static fn (mixed $warning): bool => is_array($warning)
        ));
    }

    /**
     * @param array<string, mixed> $sassBridge
     * @param array<string, mixed> $sassScopeCandidate
     * @param array<int, mixed> $additionalCheckItems
     * @return array<string, mixed>
     */
    private function buildSassReverseSummary(
        array $sassBridge,
        array $sassScopeCandidate,
        array $additionalCheckItems
    ): array {
        $bridgeAdditionalChecks = $this->extractListFromArray($sassBridge, 'additional_checks');
        $bridgeRecommendedModules = $this->extractListFromArray($sassBridge, 'recommended_modules');
        $bridgeOperationCandidates = $this->extractListFromArray($sassBridge, 'operation_candidates');
        $bridgePriorityTasks = $this->extractListFromArray($sassBridge, 'priority_tasks');
        $scopeRecommendedModules = $this->extractListFromArray($sassScopeCandidate, 'recommended_modules');
        $scopeOperationCandidates = $this->extractListFromArray($sassScopeCandidate, 'operation_candidates');

        $additionalCheckCount = count($bridgeAdditionalChecks);
        $recommendedModuleCount = count($bridgeRecommendedModules);
        $operationCandidateCount = count($bridgeOperationCandidates);
        $priorityTaskCount = count($bridgePriorityTasks);

        return [
            'summary_label' => 'SASS接続要約',
            'summary_status' => 'SASS接続準備済み',
            'target_system' => (string) ($sassBridge['target_system'] ?? 'SASS'),
            'source_system' => (string) ($sassBridge['system'] ?? 'SAIS'),
            'proposal_connection' => 'SAIS提案内容をSASS導入前改善、導入候補モジュール、月次運用候補へ接続します。',
            'estimate_connection' => 'SASSへ渡す追加確認項目をもとに、見積前確認・導入範囲確認・顧客ヒアリングへ接続します。',
            'advisory_connection' => 'SASS導入後は、月次確認・改善提案・追加提案・顧問運用へ接続できます。',
            'sass_growth_loop_connection' => 'SASS Growth Loopへ接続し、顧客サイト運用、Studio-crs運用、SASSモジュール拡張、100OS展開へ接続します。',
            'bridge_counts' => [
                'priority_task_count' => $priorityTaskCount,
                'recommended_module_count' => $recommendedModuleCount,
                'operation_candidate_count' => $operationCandidateCount,
                'additional_check_count' => $additionalCheckCount,
            ],
            'sais_visible_counts' => [
                'additional_check_items_count' => count($additionalCheckItems),
                'scope_recommended_module_count' => count($scopeRecommendedModules),
                'scope_operation_candidate_count' => count($scopeOperationCandidates),
            ],
            'business_summary' => $this->buildSassReverseBusinessSummary(
                $additionalCheckCount,
                $recommendedModuleCount,
                $operationCandidateCount
            ),
            'next_step_label' => 'SASS導入前確認へ進む',
            'next_step_message' => '追加確認項目、導入候補モジュール、月次運用候補を確認し、SASS導入提案・見積・顧問運用へ接続します。',
        ];
    }

    /**
     * @param array<string, mixed> $estimate
     * @param array<string, mixed> $sassReverseSummary
     * @return array<string, mixed>
     */
    private function attachSassEstimateConnection(array $estimate, array $sassReverseSummary): array
    {
        $bridgeCounts = is_array($sassReverseSummary['bridge_counts'] ?? null)
            ? $sassReverseSummary['bridge_counts']
            : [];

        $additionalCheckCount = (int) ($bridgeCounts['additional_check_count'] ?? 0);
        $recommendedModuleCount = (int) ($bridgeCounts['recommended_module_count'] ?? 0);
        $operationCandidateCount = (int) ($bridgeCounts['operation_candidate_count'] ?? 0);

        $estimate['sass_estimate_connection'] = [
            'section_label' => 'SASS導入前確認',
            'section_status' => (string) ($sassReverseSummary['summary_status'] ?? 'SASS接続情報未確認'),
            'target_system' => (string) ($sassReverseSummary['target_system'] ?? 'SASS'),
            'additional_check_count' => $additionalCheckCount,
            'recommended_module_count' => $recommendedModuleCount,
            'operation_candidate_count' => $operationCandidateCount,
            'estimate_connection' => (string) ($sassReverseSummary['estimate_connection'] ?? ''),
            'pricing_scope_note' => '正式見積では、SASSへ渡す追加確認項目、導入候補モジュール、月次運用候補を確認し、初期導入範囲と顧問運用範囲を整理します。',
            'business_summary' => 'SASS導入前確認'
                . $additionalCheckCount
                . '件、導入候補モジュール'
                . $recommendedModuleCount
                . '件、月次運用候補'
                . $operationCandidateCount
                . '件をもとに、見積前提と導入範囲を整理します。',
            'next_step_label' => '見積前確認へ進む',
            'next_step_message' => '対象ページ、修正範囲、追加確認項目、導入候補モジュールを確認し、正式見積へ接続します。',
        ];

        return $estimate;
    }

    /**
     * @param array<string, mixed> $introductionPlan
     * @param array<string, mixed> $sassReverseSummary
     * @return array<string, mixed>
     */
    private function attachSassOperationConnection(array $introductionPlan, array $sassReverseSummary): array
    {
        $bridgeCounts = is_array($sassReverseSummary['bridge_counts'] ?? null)
            ? $sassReverseSummary['bridge_counts']
            : [];

        $priorityTaskCount = (int) ($bridgeCounts['priority_task_count'] ?? 0);
        $recommendedModuleCount = (int) ($bridgeCounts['recommended_module_count'] ?? 0);
        $operationCandidateCount = (int) ($bridgeCounts['operation_candidate_count'] ?? 0);
        $additionalCheckCount = (int) ($bridgeCounts['additional_check_count'] ?? 0);

        $introductionPlan['sass_operation_connection'] = [
            'section_label' => 'SASS導入後運用接続',
            'section_status' => (string) ($sassReverseSummary['summary_status'] ?? 'SASS接続情報未確認'),
            'target_system' => (string) ($sassReverseSummary['target_system'] ?? 'SASS'),
            'priority_task_count' => $priorityTaskCount,
            'recommended_module_count' => $recommendedModuleCount,
            'operation_candidate_count' => $operationCandidateCount,
            'additional_check_count' => $additionalCheckCount,
            'advisory_connection' => (string) ($sassReverseSummary['advisory_connection'] ?? ''),
            'sass_growth_loop_connection' => (string) ($sassReverseSummary['sass_growth_loop_connection'] ?? ''),
            'monthly_operation_note' => 'SASS導入後は、月次確認・改善提案・追加確認・モジュール拡張候補を継続的に整理します。',
            'business_summary' => '優先タスク'
                . $priorityTaskCount
                . '件、導入候補モジュール'
                . $recommendedModuleCount
                . '件、月次運用候補'
                . $operationCandidateCount
                . '件、追加確認項目'
                . $additionalCheckCount
                . '件をもとに、SASS導入後の顧問運用へ接続します。',
            'next_step_label' => (string) ($sassReverseSummary['next_step_label'] ?? 'SASS導入前確認へ進む'),
            'next_step_message' => (string) ($sassReverseSummary['next_step_message'] ?? ''),
        ];

        return $introductionPlan;
    }

    /**
     * @param array<string, mixed> $source
     * @return array<int, mixed>
     */
    private function extractListFromArray(array $source, string $key): array
    {
        $value = $source[$key] ?? [];

        if (!is_array($value)) {
            return [];
        }

        return array_values($value);
    }

    private function buildSassReverseBusinessSummary(
        int $additionalCheckCount,
        int $recommendedModuleCount,
        int $operationCandidateCount
    ): string {
        return 'SASSへ渡す追加確認項目'
            . $additionalCheckCount
            . '件、導入候補モジュール'
            . $recommendedModuleCount
            . '件、月次運用候補'
            . $operationCandidateCount
            . '件をもとに、SAIS提案をSASS導入・顧問運用へ接続します。';
    }

    private function getDefault(string $key, string $fallback): string
    {
        $defaults = $this->outputConfig['defaults'] ?? [];

        if (is_array($defaults) && isset($defaults[$key]) && is_string($defaults[$key])) {
            return $defaults[$key];
        }

        return $fallback;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMetadata(): array
    {
        return [
            'generated_at' => date('c'),
            'engine' => $this->getDefault('system', 'SAIS'),
            'engine_version' => $this->getDefault('version', '1.0'),
            'source_engine' => $this->getDefault('source_engine', 'SADS'),
            'target_engine' => $this->getDefault('target_engine', 'SASS'),
            'document_set' => $this->getDefault('document_set', 'SAIS Documents Ver.1.0'),
        ];
    }
}