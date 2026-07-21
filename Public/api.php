<?php

declare(strict_types=1);

/**
 * SAIS - Public API
 *
 * Entry point for SAIS JSON processing.
 */

header('Content-Type: application/json; charset=utf-8');

$basePath = dirname(__DIR__);

require_once $basePath . '/Input/InputReceiver.php';
require_once $basePath . '/Input/InputValidator.php';
require_once $basePath . '/Classification/ProposalInputClassifier.php';
require_once $basePath . '/Classification/CategoryClassifier.php';
require_once $basePath . '/Classification/ImpactClassifier.php';
require_once $basePath . '/Classification/DifficultyClassifier.php';
require_once $basePath . '/Priority/PriorityOrganizer.php';
require_once $basePath . '/Priority/PriorityFormatter.php';
require_once $basePath . '/Scope/ScopeOrganizer.php';
require_once $basePath . '/Scope/SASSScopeCandidateEngine.php';
require_once $basePath . '/Scope/ScopeFormatter.php';
require_once $basePath . '/Check/AdditionalCheckEngine.php';
require_once $basePath . '/Proposal/ProposalEngine.php';
require_once $basePath . '/Estimate/EstimateItemEngine.php';
require_once $basePath . '/Introduction/IntroductionPlanEngine.php';
require_once $basePath . '/Bridge/SASSBridge.php';
require_once $basePath . '/Output/OutputBuilder.php';

try {
    $appConfig = require $basePath . '/Config/app.php';
    $inputConfig = require $basePath . '/Config/input.php';
    $proposalConfig = require $basePath . '/Config/proposal.php';
    $estimateConfig = require $basePath . '/Config/estimate.php';
    $introductionConfig = require $basePath . '/Config/introduction.php';
    $scopeConfig = require $basePath . '/Config/scope.php';
    $outputConfig = require $basePath . '/Config/output.php';
    $warningsConfig = require $basePath . '/Config/warnings.php';

    $receiver = new InputReceiver();
    $validator = new InputValidator($inputConfig, $warningsConfig);
    $outputBuilder = new OutputBuilder($outputConfig, $warningsConfig);

    $input = $receiver->receive();

    if ($input === []) {
        http_response_code(400);

        echo json_encode(
            $outputBuilder->buildFailedByCode('INVALID_JSON', ['input'], $input),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );

        exit;
    }

    $validation = $validator->validate($input);

    if (($validation['valid'] ?? false) !== true) {
        http_response_code(400);

        echo json_encode(
            $outputBuilder->buildFailed(
                $validation['errors'] ?? [],
                $validation['warnings'] ?? [],
                $input
            ),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );

        exit;
    }

    $proposalInputs = extractList($input, 'proposal_inputs');
    $priorityItems = extractList($input, 'priority_items');
    $recommendedScope = extractStringList($input, 'recommended_scope');
    $additionalCheckItems = extractList($input, 'additional_check_items');
    $confidence = extractArray($input, 'confidence');
    $clientSummary = is_string($input['client_summary'] ?? null)
        ? $input['client_summary']
        : '';

    $proposalInputClassifier = new ProposalInputClassifier();
    $categoryClassifier = new CategoryClassifier();
    $impactClassifier = new ImpactClassifier();
    $difficultyClassifier = new DifficultyClassifier();
    $priorityOrganizer = new PriorityOrganizer();
    $scopeOrganizer = new ScopeOrganizer();
    $additionalCheckEngine = new AdditionalCheckEngine($warningsConfig);
    $proposalEngine = new ProposalEngine($proposalConfig);
    $estimateEngine = new EstimateItemEngine($estimateConfig);
    $introductionEngine = new IntroductionPlanEngine($introductionConfig);
    $sassScopeCandidateEngine = new SASSScopeCandidateEngine($scopeConfig);
    $sassBridgeEngine = new SASSBridge();

    $classifiedProposalInputs = $proposalInputClassifier->classify($proposalInputs);
    $categoryClassification = $categoryClassifier->classify($classifiedProposalInputs['items'] ?? []);
    $impactClassification = $impactClassifier->classify($classifiedProposalInputs['items'] ?? []);
    $difficultyClassification = $difficultyClassifier->classify($classifiedProposalInputs['items'] ?? []);
    $organizedPriority = $priorityOrganizer->organize($priorityItems);
    $organizedScope = $scopeOrganizer->organize($recommendedScope);
    $additionalCheckResult = $additionalCheckEngine->process($additionalCheckItems, $confidence);

    $normalizedProposalInputs = is_array($classifiedProposalInputs['items'] ?? null)
        ? $classifiedProposalInputs['items']
        : [];

    $normalizedPriorityItems = is_array($organizedPriority['items'] ?? null)
        ? $organizedPriority['items']
        : [];

    $proposal = $proposalEngine->build(
        $normalizedProposalInputs,
        $normalizedPriorityItems,
        $clientSummary,
        $recommendedScope,
        $confidence,
        $additionalCheckResult
    );

    $estimate = $estimateEngine->build(
        $normalizedProposalInputs,
        $normalizedPriorityItems,
        $recommendedScope,
        $additionalCheckResult
    );

    $introductionPlan = $introductionEngine->build(
        $recommendedScope,
        $normalizedPriorityItems,
        $estimate,
        $proposal
    );

    $scopeCandidates = is_array($organizedScope['sass_scope_candidates'] ?? null)
        ? $organizedScope['sass_scope_candidates']
        : [];

    $sassScopeCandidate = $sassScopeCandidateEngine->build(
        $scopeCandidates,
        $normalizedPriorityItems,
        $estimate['estimate_items'] ?? []
    );

    $sassBridge = $sassBridgeEngine->build(
        $input,
        $sassScopeCandidate,
        $introductionPlan,
        $estimate,
        $additionalCheckResult
    );

    $output = $outputBuilder->buildSuccess(
        $input,
        $proposal,
        $estimate,
        $introductionPlan,
        $sassScopeCandidate,
        $additionalCheckResult,
        array_merge(
            $validation['warnings'] ?? [],
            $additionalCheckResult['warnings'] ?? []
        ),
        $sassBridge
    );

    $output['processing'] = [
        'classification' => [
            'proposal_input_summary' => $classifiedProposalInputs['summary'] ?? [],
            'category_summary' => $categoryClassification['summary'] ?? [],
            'impact_summary' => $impactClassification['summary'] ?? [],
            'difficulty_summary' => $difficultyClassification['summary'] ?? [],
        ],
        'priority_summary' => $organizedPriority['summary'] ?? [],
        'scope_summary' => $organizedScope['summary'] ?? [],
        'additional_check_summary' => $additionalCheckResult['summary'] ?? [],
    ];

    echo json_encode(
        $output,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
    );

    exit;
} catch (Throwable $throwable) {
    http_response_code(500);

    $warningsConfig = isset($warningsConfig) && is_array($warningsConfig)
        ? $warningsConfig
        : require $basePath . '/Config/warnings.php';

    $outputConfig = isset($outputConfig) && is_array($outputConfig)
        ? $outputConfig
        : require $basePath . '/Config/output.php';

    $outputBuilder = new OutputBuilder($outputConfig, $warningsConfig);

    echo json_encode(
        $outputBuilder->buildFailedByCode(
            'OUTPUT_BUILD_FAILED',
            [$throwable->getMessage()],
            []
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
    );

    exit;
}

/**
 * @param array<string, mixed> $input
 * @return array<int, array<string, mixed>>
 */
function extractList(array $input, string $key): array
{
    $value = $input[$key] ?? [];

    if (!is_array($value) || !array_is_list($value)) {
        return [];
    }

    return array_values(array_filter(
        $value,
        static fn (mixed $item): bool => is_array($item)
    ));
}

/**
 * @param array<string, mixed> $input
 * @return array<int, string>
 */
function extractStringList(array $input, string $key): array
{
    $value = $input[$key] ?? [];

    if (!is_array($value) || !array_is_list($value)) {
        return [];
    }

    return array_values(array_filter(
        array_map(
            static fn (mixed $item): string => is_scalar($item) ? trim((string) $item) : '',
            $value
        ),
        static fn (string $item): bool => $item !== ''
    ));
}

/**
 * @param array<string, mixed> $input
 * @return array<string, mixed>
 */
function extractArray(array $input, string $key): array
{
    $value = $input[$key] ?? [];

    return is_array($value) ? $value : [];
}