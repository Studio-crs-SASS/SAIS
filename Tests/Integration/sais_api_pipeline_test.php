<?php

declare(strict_types=1);

/**
 * SAIS - API Pipeline Integration Test
 *
 * Tests SAIS full processing pipeline.
 */

$basePath = dirname(__DIR__, 2);

require_once $basePath . '/Input/InputReceiver.php';
require_once $basePath . '/Input/InputValidator.php';
require_once $basePath . '/Classification/ProposalInputClassifier.php';
require_once $basePath . '/Classification/CategoryClassifier.php';
require_once $basePath . '/Classification/ImpactClassifier.php';
require_once $basePath . '/Classification/DifficultyClassifier.php';
require_once $basePath . '/Priority/PriorityOrganizer.php';
require_once $basePath . '/Scope/ScopeOrganizer.php';
require_once $basePath . '/Scope/SASSScopeCandidateEngine.php';
require_once $basePath . '/Check/AdditionalCheckEngine.php';
require_once $basePath . '/Proposal/ProposalEngine.php';
require_once $basePath . '/Estimate/EstimateItemEngine.php';
require_once $basePath . '/Introduction/IntroductionPlanEngine.php';
require_once $basePath . '/Bridge/SASSBridge.php';
require_once $basePath . '/Output/OutputBuilder.php';

$inputConfig = require $basePath . '/Config/input.php';
$proposalConfig = require $basePath . '/Config/proposal.php';
$estimateConfig = require $basePath . '/Config/estimate.php';
$introductionConfig = require $basePath . '/Config/introduction.php';
$scopeConfig = require $basePath . '/Config/scope.php';
$outputConfig = require $basePath . '/Config/output.php';
$warningsConfig = require $basePath . '/Config/warnings.php';

$samplePath = $basePath . '/Data/samples/sads_sais_bridge_sample.json';

$receiver = new InputReceiver();
$validator = new InputValidator($inputConfig, $warningsConfig);
$outputBuilder = new OutputBuilder($outputConfig, $warningsConfig);

$input = $receiver->receiveFromFile($samplePath);
$validation = $validator->validate($input);

if (($validation['valid'] ?? false) !== true) {
    echo 'SAIS Integration Test Failed: Input validation failed.' . PHP_EOL;
    print_r($validation);
    exit(1);
}

$proposalInputs = extractListForSaisTest($input, 'proposal_inputs');
$priorityItems = extractListForSaisTest($input, 'priority_items');
$recommendedScope = extractStringListForSaisTest($input, 'recommended_scope');
$additionalCheckItems = extractListForSaisTest($input, 'additional_check_items');
$confidence = extractArrayForSaisTest($input, 'confidence');
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

$checks = [
    'status_is_success' => ($output['status'] ?? '') === 'success',
    'system_is_sais' => ($output['system'] ?? '') === 'SAIS',
    'project_is_seen' => ($output['project'] ?? '') === 'SEEN',
    'has_proposal' => isset($output['proposal']) && is_array($output['proposal']),
    'has_estimate' => isset($output['estimate']) && is_array($output['estimate']),
    'has_introduction_plan' => isset($output['introduction_plan']) && is_array($output['introduction_plan']),
    'has_sass_scope_candidate' => isset($output['sass_scope_candidate']) && is_array($output['sass_scope_candidate']),
    'has_proposal_data' => isset($output['proposal_data']) && is_array($output['proposal_data']),
    'has_sass_bridge' => isset($output['sass_bridge']) && is_array($output['sass_bridge']),
    'proposal_action_count_is_2' => count($output['proposal']['recommended_actions'] ?? []) === 2,
    'estimate_item_count_is_2' => count($output['estimate']['estimate_items'] ?? []) === 2,
    'bridge_target_is_sass' => ($output['sass_bridge']['target_system'] ?? '') === 'SASS',
];

$failed = [];

foreach ($checks as $name => $passed) {
    echo ($passed ? 'PASS: ' : 'FAIL: ') . $name . PHP_EOL;

    if (!$passed) {
        $failed[] = $name;
    }
}

if ($failed !== []) {
    echo 'SAIS Integration Test Failed.' . PHP_EOL;
    print_r($failed);
    exit(1);
}

file_put_contents(
    $basePath . '/Data/output/sais_api_test_output.json',
    json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
);

echo 'System: ' . $output['system'] . PHP_EOL;
echo 'Status: ' . $output['status'] . PHP_EOL;
echo 'Proposal Title: ' . $output['proposal']['title'] . PHP_EOL;
echo 'Estimate Items: ' . count($output['estimate']['estimate_items']) . PHP_EOL;
echo 'Introduction Phases: ' . count($output['introduction_plan']['phases']) . PHP_EOL;
echo 'SASS Bridge Target: ' . $output['sass_bridge']['target_system'] . PHP_EOL;
echo 'SAIS Integration Test Completed Successfully.' . PHP_EOL;

/**
 * @param array<string, mixed> $input
 * @return array<int, array<string, mixed>>
 */
function extractListForSaisTest(array $input, string $key): array
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
function extractStringListForSaisTest(array $input, string $key): array
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
function extractArrayForSaisTest(array $input, string $key): array
{
    $value = $input[$key] ?? [];

    return is_array($value) ? $value : [];
}