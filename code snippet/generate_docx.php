<?php
/**
 * Bridges PHP -> Python for Word document generation.
 *
 * Uses proc_open() with an array command to avoid shell injection.
 * Calls Python script to generate a full-format VTU Computer Science & Engineering
 * Mini-Project Report (.docx) with all front matter, certificate, TOC, chapters,
 * and appendix pages.
 */

function generate_group_report_docx(array $groupData, string $templatePath = '', string $outputDir = ''): array
{
    if (empty($outputDir)) {
        $outputDir = __DIR__ . '/generated_reports';
    }
    if (!is_dir($outputDir)) {
        @mkdir($outputDir, 0777, true);
    }
    if (!is_dir($outputDir) || !is_writable($outputDir)) {
        return ['success' => false, 'error' => 'Output directory missing or not writable: ' . $outputDir];
    }

    $tmpJson = tempnam(sys_get_temp_dir(), 'pms_ctx_');
    rename($tmpJson, $tmpJson .= '.json');
    file_put_contents($tmpJson, json_encode($groupData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    // Sanitize for use as a filename only
    $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $groupData['project_name'] ?? 'report');
    $outputPath = rtrim($outputDir, '/') . '/' . $safeName . '_' . bin2hex(random_bytes(4)) . '.docx';

    $pythonBin = PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';
    $scriptPath = file_exists(__DIR__ . '/generate_report.py') 
        ? __DIR__ . '/generate_report.py' 
        : (file_exists(__DIR__ . '/scripts/generate_report.py') ? __DIR__ . '/scripts/generate_report.py' : 'generate_report.py');

    $cmd = [
        $pythonBin,
        $scriptPath,
        $tmpJson,
        $outputPath,
    ];

    if (!empty($templatePath) && file_exists($templatePath)) {
        $cmd[] = $templatePath;
    }

    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($cmd, $descriptorSpec, $pipes);
    if (!is_resource($process)) {
        @unlink($tmpJson);
        return ['success' => false, 'error' => 'Could not start Python process — check proc_open is enabled and python is on PATH.'];
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    @unlink($tmpJson);

    if ($exitCode !== 0) {
        $err = trim($stderr) ?: trim($stdout) ?: 'Unknown error from generate_report.py';
        return ['success' => false, 'error' => $err];
    }
    if (!is_file($outputPath)) {
        return ['success' => false, 'error' => 'Python reported success but output file is missing.'];
    }

    return ['success' => true, 'file' => $outputPath];
}
