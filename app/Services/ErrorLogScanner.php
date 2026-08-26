<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class ErrorLogScanner
{
    private const ERROR_LEVELS = ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'];

    public function __construct(
        private readonly string $logDirectory,
    ) {}

    public function scan(CarbonInterface $since, CarbonInterface $until): ErrorLogReport
    {
        $entries = [];

        foreach ($this->logFiles() as $file) {
            array_push($entries, ...$this->parseFile($file, $since, $until));
        }

        $groups = [];

        foreach ($entries as $entry) {
            $key = $entry['fingerprint'];

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'fingerprint' => $key,
                    'level' => $entry['level'],
                    'count' => 0,
                    'sample' => $entry['message'],
                ];
            }

            $groups[$key]['count']++;

            if ($this->levelRank($entry['level']) > $this->levelRank($groups[$key]['level'])) {
                $groups[$key]['level'] = $entry['level'];
            }
        }

        uasort($groups, fn (array $a, array $b) => $b['count'] <=> $a['count']);

        return new ErrorLogReport(
            total: count($entries),
            uniqueCount: count($groups),
            groups: array_values($groups),
        );
    }

    /**
     * @return list<string>
     */
    private function logFiles(): array
    {
        if (! is_dir($this->logDirectory)) {
            return [];
        }

        $files = glob($this->logDirectory.DIRECTORY_SEPARATOR.'laravel*.log') ?: [];
        sort($files);

        return $files;
    }

    /**
     * @return list<array{timestamp: CarbonInterface, level: string, message: string, fingerprint: string}>
     */
    private function parseFile(string $path, CarbonInterface $since, CarbonInterface $until): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        $entries = [];
        $current = null;

        while (($line = fgets($handle)) !== false) {
            if (! preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+\w+\.(\w+):\s*(.*)$/', $line, $match)) {
                continue;
            }

            if ($current && $this->isReportable($current, $since, $until)) {
                $entries[] = $current;
            }

            $current = [
                'timestamp' => Carbon::parse($match[1]),
                'level' => strtoupper($match[2]),
                'message' => $match[3],
                'fingerprint' => $this->fingerprint($match[3]),
            ];
        }

        if ($current && $this->isReportable($current, $since, $until)) {
            $entries[] = $current;
        }

        fclose($handle);

        return $entries;
    }

    /**
     * @param  array{timestamp: CarbonInterface, level: string, message: string, fingerprint: string}  $entry
     */
    private function isReportable(array $entry, CarbonInterface $since, CarbonInterface $until): bool
    {
        return in_array($entry['level'], self::ERROR_LEVELS, true)
            && $entry['timestamp']->gte($since)
            && $entry['timestamp']->lte($until);
    }

    private function fingerprint(string $message): string
    {
        $base = preg_replace('/\s*\{.*\}\s*$/s', '', $message) ?? $message;
        $base = preg_replace('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', '{uuid}', $base) ?? $base;
        $base = preg_replace('/\b\d+\b/', '{n}', $base) ?? $base;
        $base = trim(preg_replace('/\s+/', ' ', $base) ?? $base);

        return mb_substr($base, 0, 180);
    }

    private function levelRank(string $level): int
    {
        return match (strtoupper($level)) {
            'EMERGENCY' => 4,
            'ALERT' => 3,
            'CRITICAL' => 2,
            'ERROR' => 1,
            default => 0,
        };
    }
}
