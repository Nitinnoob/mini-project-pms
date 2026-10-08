<?php
declare(strict_types=1);

/**
 * Bridges PHP -> Python for Word document generation (.docx).
 *
 * Pre-fills project details, student names/USNs, weekly Saturday guide review logs,
 * attendance records, and Continuous Internal Evaluation (CIE) marks.
 * Supports swappable templates (default: templates/report_template.docx).
 */

if (!function_exists('generate_group_report_docx')) {
    /**
     * Generate pre-filled VTU Word report document.
     *
     * @param array $groupData Associative array of project metadata, members, meetings, attendance, marks.
     * @param string $templatePath Optional path to custom .docx template.
     * @param string $outputDir Directory where generated file is placed.
     * @return array{success: bool, file?: string, error?: string}
     */
    function generate_group_report_docx(array $groupData, string $templatePath = '', string $outputDir = ''): array
    {
        if (empty($outputDir)) {
            $outputDir = sys_get_temp_dir() . '/pms_reports';
        }
        if (!is_dir($outputDir)) {
            @mkdir($outputDir, 0777, true);
        }
        if (!is_dir($outputDir) || !is_writable($outputDir)) {
            $outputDir = sys_get_temp_dir();
        }

        $tmpJson = tempnam(sys_get_temp_dir(), 'pms_ctx_');
        if ($tmpJson === false) {
            return ['success' => false, 'error' => 'Could not create temporary context file.'];
        }
        $jsonPath = $tmpJson . '.json';
        rename($tmpJson, $jsonPath);
        file_put_contents($jsonPath, json_encode($groupData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        // Sanitize project name for safe filename
        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string)($groupData['project_name'] ?? 'report'));
        $outputPath = rtrim($outputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName . '_' . bin2hex(random_bytes(4)) . '.docx';

        $pythonBin = PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';
        $scriptPath = __DIR__ . '/scripts/generate_report.py';

        if (!file_exists($scriptPath)) {
            @unlink($jsonPath);
            return ['success' => false, 'error' => 'Report generator script missing: ' . $scriptPath];
        }

        $cmd = [
            $pythonBin,
            $scriptPath,
            $jsonPath,
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

        $process = @proc_open($cmd, $descriptorSpec, $pipes);
        if (!is_resource($process)) {
            @unlink($jsonPath);
            return ['success' => false, 'error' => 'Could not start Python process. Ensure python is installed and on PATH.'];
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        @unlink($jsonPath);

        if ($exitCode !== 0) {
            $err = trim($stderr) ?: trim($stdout) ?: 'Unknown error from generate_report.py';
            return ['success' => false, 'error' => $err];
        }

        if (!is_file($outputPath)) {
            return ['success' => false, 'error' => 'Generator reported success but output file is missing.'];
        }

        return ['success' => true, 'file' => $outputPath];
    }
}
