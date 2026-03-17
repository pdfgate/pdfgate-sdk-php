<?php

declare(strict_types=1);

namespace PdfGate\Internal\Release;

final class ChangelogBuilder
{
    /**
     * @param list<string> $subjects
     * @return array{notes:string, previous_tag:?string}
     */
    public function buildReleaseNotes(array $subjects, ?string $previousTag = null): array
    {
        $sections = array(
            'Added' => array(),
            'Fixed' => array(),
            'Changed' => array(),
            'Documentation' => array(),
            'Tests' => array(),
            'Maintenance' => array(),
        );

        foreach ($subjects as $subject) {
            if (trim($subject) === '') {
                continue;
            }

            $categorized = $this->categorizeSubject($subject);
            $sections[$categorized['section']][] = $categorized['description'];
        }

        if ($this->isEmptySections($sections)) {
            $sections['Changed'][] = $previousTag !== null
                ? sprintf('No changes since %s.', $previousTag)
                : 'No changes available for this release.';
        }

        return array(
            'notes' => $this->renderSections($sections),
            'previous_tag' => $previousTag,
        );
    }

    public function updateChangelog(string $existingChangelog, string $version, string $releaseDate, string $notes): string
    {
        $preamble = array();
        $history = array();
        $lines = preg_split("/\r\n|\n|\r/", $existingChangelog);

        if ($lines === false) {
            $lines = array();
        }

        $encounteredFirstSection = false;
        $captureHistory = false;
        $skipSection = false;

        foreach ($lines as $line) {
            if (!$encounteredFirstSection && strpos($line, '## [') !== 0) {
                $preamble[] = $line;
                continue;
            }

            $encounteredFirstSection = true;

            if (strpos($line, '## [') === 0) {
                $captureHistory = true;

                if ($line === '## [Unreleased]' || preg_match('/^## \[' . preg_quote($version, '/') . '\] - /', $line) === 1) {
                    $skipSection = true;
                    continue;
                }

                $skipSection = false;
            }

            if ($captureHistory && !$skipSection) {
                $history[] = $line;
            }
        }

        $result = rtrim(implode("\n", $preamble)) . "\n\n";
        $result .= "## [Unreleased]\n\n";
        $result .= sprintf("## [%s] - %s\n\n", $version, $releaseDate);
        $result .= rtrim($notes) . "\n";

        if ($this->containsMeaningfulContent($history)) {
            $result .= "\n" . ltrim(rtrim(implode("\n", $history)), "\n") . "\n";
        }

        return $result;
    }

    /**
     * @return array{section:string,description:string}
     */
    private function categorizeSubject(string $subject): array
    {
        if (preg_match('/^([A-Za-z]+)(\([^)]+\))?(!)?:\s*(.+)$/', $subject, $matches) === 1) {
            $type = strtolower($matches[1]);
            $description = $matches[4];

            switch ($type) {
                case 'feat':
                    return array('section' => 'Added', 'description' => $description);
                case 'fix':
                    return array('section' => 'Fixed', 'description' => $description);
                case 'refactor':
                    return array('section' => 'Changed', 'description' => $description);
                case 'docs':
                    return array('section' => 'Documentation', 'description' => $description);
                case 'test':
                    return array('section' => 'Tests', 'description' => $description);
                case 'chore':
                    return array('section' => 'Maintenance', 'description' => $description);
            }
        }

        return array('section' => 'Changed', 'description' => $subject);
    }

    /**
     * @param array<string, list<string>> $sections
     */
    private function renderSections(array $sections): string
    {
        $chunks = array();

        foreach ($sections as $section => $items) {
            if ($items === array()) {
                continue;
            }

            $lines = array(sprintf('### %s', $section), '');
            foreach ($items as $item) {
                $lines[] = sprintf('- %s', $item);
            }

            $chunks[] = implode("\n", $lines);
        }

        return implode("\n\n", $chunks);
    }

    /**
     * @param array<string, list<string>> $sections
     */
    private function isEmptySections(array $sections): bool
    {
        foreach ($sections as $items) {
            if ($items !== array()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<string> $lines
     */
    private function containsMeaningfulContent(array $lines): bool
    {
        foreach ($lines as $line) {
            if (trim($line) !== '') {
                return true;
            }
        }

        return false;
    }
}
