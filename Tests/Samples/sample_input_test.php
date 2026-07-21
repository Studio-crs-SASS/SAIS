<?php

declare(strict_types=1);

/**
 * SAIS - Sample Input Test
 *
 * Tests SADS to SAIS sample input JSON.
 */

$basePath = dirname(__DIR__, 2);
$samplePath = $basePath . '/Data/samples/sads_sais_bridge_sample.json';

if (!is_file($samplePath)) {
    echo 'FAIL: sample file missing' . PHP_EOL;
    exit(1);
}

$raw = file_get_contents($samplePath);

if ($raw === false || trim($raw) === '') {
    echo 'FAIL: sample file empty' . PHP_EOL;
    exit(1);
}

$json = json_decode($raw, true);

if (!is_array($json)) {
    echo 'FAIL: invalid json' . PHP_EOL;
    echo json_last_error_msg() . PHP_EOL;
    exit(1);
}

$requiredRootFields = [
    'system',
    'target_system',
    'project',
    'version',
    'target',
    'proposal_inputs',
    'priority_items',
    'client_summary',
    'confidence',
    'recommended_scope',
    'additional_check_items',
    'metadata',
];

$checks = [];

foreach ($requiredRootFields as $field) {
    $checks['has_' . $field] = array_key_exists($field, $json);
}

$checks['system_is_sads'] = ($json['system'] ?? '') === 'SADS';
$checks['target_system_is_sais'] = ($json['target_system'] ?? '') === 'SAIS';
$checks['project_is_seen'] = ($json['project'] ?? '') === 'SEEN';
$checks['target_has_url'] = isset($json['target']['url']) && is_string($json['target']['url']);
$checks['proposal_inputs_count_is_2'] = isset($json['proposal_inputs']) && is_array($json['proposal_inputs']) && count($json['proposal_inputs']) === 2;
$checks['priority_items_count_is_2'] = isset($json['priority_items']) && is_array($json['priority_items']) && count($json['priority_items']) === 2;
$checks['recommended_scope_count_is_2'] = isset($json['recommended_scope']) && is_array($json['recommended_scope']) && count($json['recommended_scope']) === 2;
$checks['confidence_display_is_90'] = isset($json['confidence']['display']) && (float) $json['confidence']['display'] === 90.0;

$failed = [];

foreach ($checks as $name => $passed) {
    echo ($passed ? 'PASS: ' : 'FAIL: ') . $name . PHP_EOL;

    if (!$passed) {
        $failed[] = $name;
    }
}

if ($failed !== []) {
    echo 'SAIS Sample Input Test Failed.' . PHP_EOL;
    print_r($failed);
    exit(1);
}

echo 'System: ' . $json['system'] . PHP_EOL;
echo 'Target System: ' . $json['target_system'] . PHP_EOL;
echo 'Project: ' . $json['project'] . PHP_EOL;
echo 'Proposal Inputs: ' . count($json['proposal_inputs']) . PHP_EOL;
echo 'Priority Items: ' . count($json['priority_items']) . PHP_EOL;
echo 'Recommended Scope: ' . count($json['recommended_scope']) . PHP_EOL;
echo 'SAIS Sample Input Test Completed Successfully.' . PHP_EOL;