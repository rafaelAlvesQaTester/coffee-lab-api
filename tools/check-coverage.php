<?php

declare(strict_types=1);

const MINIMUM_LINE_COVERAGE = 70.0;

$reportPath = $argv[1] ?? '';
if ($reportPath === '' || !is_file($reportPath)) {
    fwrite(STDERR, "Coverage report not found. Generate a Clover report before checking coverage.\n");
    exit(1);
}

$report = simplexml_load_file($reportPath);
if (!$report instanceof SimpleXMLElement) {
    fwrite(STDERR, "Coverage report is not valid XML.\n");
    exit(1);
}

$lines = $report->xpath('//line[@type="stmt"]');
if (!is_array($lines) || $lines === []) {
    fwrite(STDERR, "Coverage report contains no executable lines.\n");
    exit(1);
}

$coveredLines = 0;
foreach ($lines as $line) {
    if (!isset($line['count'])) {
        fwrite(STDERR, "Coverage report contains an executable line without a hit count.\n");
        exit(1);
    }

    if ((int) $line['count'] > 0) {
        $coveredLines++;
    }
}

$lineCoverage = ($coveredLines / count($lines)) * 100;
printf(
    "Line coverage: %d/%d (%.2f%%); minimum: %.2f%%\n",
    $coveredLines,
    count($lines),
    $lineCoverage,
    MINIMUM_LINE_COVERAGE,
);

if ($lineCoverage < MINIMUM_LINE_COVERAGE) {
    fwrite(STDERR, "Line coverage is below the required minimum.\n");
    exit(1);
}
