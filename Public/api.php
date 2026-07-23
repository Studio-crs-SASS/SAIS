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

function convertSadsOutputToSaisInputIfNeeded(array $input): array
{
    $isAlreadySaisInput =
        isset($input['proposal_inputs']) ||
        isset($input['priority_items']) ||
        isset($input['recommended_scope']);

    if ($isAlreadySaisInput) {
        return $input;
    }

    $isSadsOutput =
        (($input['system'] ?? '') === 'SADS') ||
        isset($input['scores']) ||
        isset($input['categories']) ||
        isset($input['indicators']);

    if (!$isSadsOutput) {
        return $input;
    }

    $target = is_array($input['target'] ?? null) ? $input['target'] : [];
    $scores = is_array($input['scores'] ?? null) ? $input['scores'] : [];
    $categories = is_array($input['categories'] ?? null) ? $input['categories'] : [];
    $indicators = is_array($input['indicators'] ?? null) ? $input['indicators'] : [];

    $overallScore = $scores['display']['overall']
        ?? $scores['overall']
        ?? $input['score_display']
        ?? null;

    $domain = (string) ($target['domain'] ?? '');
    $url = (string) ($target['url'] ?? '');

    $proposalInputs = [];
    $priorityItems = [];
    $recommendedScope = [];
    $additionalCheckItems = [];

    foreach ($categories as $categoryKey => $category) {
        if (!is_array($category)) {
            continue;
        }

        $displayScore = $category['display_score']
            ?? $category['score']
            ?? null;

        $label = (string) ($category['label'] ?? $categoryKey);

        $proposalInputs[] = [
            'id' => 'category_' . (string) $categoryKey,
            'title' => strtoupper((string) $categoryKey) . ' improvement proposal',
            'category' => (string) $categoryKey,
            'summary' => $label . ' score: ' . (string) $displayScore,
            'score' => $displayScore,
            'source' => 'SADS categories',
        ];

        if (is_numeric($displayScore) && (float) $displayScore < 85) {
            $priorityItems[] = [
                'id' => 'priority_' . (string) $categoryKey,
                'title' => strtoupper((string) $categoryKey) . ' enhancement',
                'category' => (string) $categoryKey,
                'priority' => (float) $displayScore < 70 ? 'high' : 'medium',
                'impact' => 'proposal_quality',
                'difficulty' => 'medium',
                'reason' => 'Category score is below the preferred proposal threshold.',
                'score' => $displayScore,
            ];
        }
    }

    foreach ($indicators as $index => $indicator) {
        if (!is_array($indicator)) {
            continue;
        }

        $indicatorId = (string) ($indicator['indicator_id'] ?? ('indicator_' . (string) $index));
        $displayScore = $indicator['display_score']
            ?? $indicator['score']
            ?? null;

        if (is_numeric($displayScore) && (float) $displayScore < 70) {
            $priorityItems[] = [
                'id' => 'priority_' . $indicatorId,
                'title' => $indicatorId,
                'category' => (string) ($indicator['category'] ?? 'diagnosis'),
                'priority' => (float) $displayScore < 60 ? 'high' : 'medium',
                'impact' => 'diagnosis_result',
                'difficulty' => 'medium',
                'reason' => (string) ($indicator['input_summary'] ?? 'Indicator score requires attention.'),
                'score' => $displayScore,
            ];
        }
    }

    if ($recommendedScope === []) {
        $recommendedScope = [
            'Web structure enhancement',
            'Content quality enhancement',
            'Internal flow enhancement',
            'SASS introduction preparation',
        ];
    }

    if ($additionalCheckItems === []) {
        $additionalCheckItems = [
            [
                'id' => 'check_cta',
                'title' => 'CTA and conversion path check',
                'reason' => 'Confirm whether the site has a clear inquiry or consultation route.',
            ],
            [
                'id' => 'check_content_depth',
                'title' => 'Content depth check',
                'reason' => 'Confirm whether service explanation is enough for proposal generation.',
            ],
        ];
    }

    if ($proposalInputs === []) {
        $proposalInputs[] = [
            'id' => 'proposal_overall',
            'title' => 'Overall web improvement proposal',
            'category' => 'overall',
            'summary' => 'SADS overall score: ' . (string) $overallScore,
            'score' => $overallScore,
            'source' => 'SADS overall score',
        ];
    }

    if ($priorityItems === []) {
        $priorityItems[] = [
            'id' => 'priority_overall',
            'title' => 'Overall improvement review',
            'category' => 'overall',
            'priority' => 'medium',
            'impact' => 'proposal_quality',
            'difficulty' => 'medium',
            'reason' => 'Create introduction proposal from SADS diagnostic result.',
            'score' => $overallScore,
        ];
    }

    $clientSummaryParts = [];

    if ($domain !== '') {
        $clientSummaryParts[] = 'Target domain: ' . $domain;
    }

    if ($url !== '') {
        $clientSummaryParts[] = 'Target URL: ' . $url;
    }

    if ($overallScore !== null) {
        $clientSummaryParts[] = 'SADS overall score: ' . (string) $overallScore . ' / 100';
    }

    $clientSummary = implode(' / ', $clientSummaryParts);

        return [
        'system' => 'SADS',
        'target_system' => 'SAIS',
        'project' => 'SEEN',
        'version' => '1.0',
        'target' => [
            'url' => $url,
            'domain' => $domain,
            'checked_at' => (string) ($target['checked_at'] ?? date(DATE_ATOM)),
        ],
        'proposal_inputs' => $proposalInputs,
        'priority_items' => array_slice($priorityItems, 0, 12),
        'client_summary' => $clientSummary,
        'confidence' => is_array($input['confidence'] ?? null)
            ? $input['confidence']
            : [
                'internal' => is_numeric($overallScore) ? round((float) $overallScore / 10, 1) : null,
                'display' => $overallScore,
                'level' => is_numeric($overallScore) && (float) $overallScore >= 80 ? 'high' : 'medium',
                'missing_data_count' => 0,
            ],
        'recommended_scope' => $recommendedScope,
        'additional_check_items' => $additionalCheckItems,
        'metadata' => [
            'generated_at' => date(DATE_ATOM),
            'engine' => 'SAIS',
            'source_engine' => 'SADS',
            'adapter' => 'SADS output to SAIS input',
            'adapter_version' => '1.2',
        ],
    ];
}

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
    $input = convertSadsOutputToSaisInputIfNeeded($input);


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