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

        $proposalData = $this->proposalDataBuilder->build(
            $target,
            $proposal,
            $estimate,
            $introductionPlan,
            $sassScopeCandidate,
            $additionalCheckItems
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