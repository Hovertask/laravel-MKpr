<?php
// Render the full Composer advisory report into a reviewable GitHub Actions summary.
$path = $argv[1] ?? 'composer-security-audit.json';
$report = is_file($path) ? json_decode(file_get_contents($path), true) : null;
if (!is_array($report)) {
    fwrite(STDERR, "Missing or invalid Composer audit JSON\n");
    exit(1);
}

$advisories = $report['advisories'] ?? [];
if (!is_array($advisories)) {
    fwrite(STDERR, "Invalid Composer advisories format\n");
    exit(1);
}

$lines = ['### Composer dependency security review', '', '| Package | Advisories | Severity |', '|---|---:|---|'];
$total = 0;
ksort($advisories);
foreach ($advisories as $package => $items) {
    if (!is_array($items)) {
        continue;
    }
    $total += count($items);
    $severities = [];
    foreach ($items as $advisory) {
        $severity = is_array($advisory) ? ($advisory['severity'] ?? 'unspecified') : 'unspecified';
        $severities[] = strtolower((string) $severity);
    }
    $severities = array_unique($severities);
    $safeName = str_replace('|', '\\|', (string) $package);
    $lines[] = '| `' . $safeName . '` | ' . count($items) . ' | ' . implode(', ', $severities) . ' |';
}
$lines[] = '';
$lines[] = '**Affected packages:** ' . count($advisories) . ' · **Advisories:** ' . $total;
$lines[] = 'Review the uploaded JSON for advisory IDs, links, and affected versions.';
$summary = implode("\n", $lines) . "\n";
$summaryPath = getenv('GITHUB_STEP_SUMMARY');
if ($summaryPath) {
    file_put_contents($summaryPath, $summary, FILE_APPEND);
} else {
    echo $summary;
}
