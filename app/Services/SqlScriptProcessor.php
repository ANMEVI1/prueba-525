<?php

namespace App\Services;

/**
 * Parses SQL dumps produced by mysqldump, phpMyAdmin or this system and
 * adapts them to the target engine (MySQL 8 or MariaDB) before execution.
 */
class SqlScriptProcessor
{
    /**
     * Splits a SQL script into executable statements. Respects quoted strings,
     * backtick identifiers, comments and DELIMITER blocks (triggers/routines).
     *
     * @return list<string>
     */
    public function split(string $sql): array
    {
        $sql = $this->stripBom($sql);
        $length = strlen($sql);
        $statements = [];
        $buffer = '';
        $delimiter = ';';
        $position = 0;

        while ($position < $length) {
            if (trim($buffer) === '' && preg_match('/\G[ \t]*DELIMITER[ \t]+(\S+)[^\n]*(?:\n|$)/Ai', $sql, $match, 0, $position) === 1) {
                $delimiter = $match[1];
                $buffer = '';
                $position += strlen($match[0]);

                continue;
            }

            $character = $sql[$position];
            $next = $sql[$position + 1] ?? '';

            if ($character === "'" || $character === '"' || $character === '`') {
                $end = $this->findQuoteEnd($sql, $position, $character);
                $buffer .= substr($sql, $position, $end - $position);
                $position = $end;

                continue;
            }

            if ($character === '#' || ($character === '-' && $next === '-' && ($position + 2 >= $length || ctype_space($sql[$position + 2])))) {
                $lineEnd = strpos($sql, "\n", $position);
                $position = $lineEnd === false ? $length : $lineEnd + 1;
                $buffer .= "\n";

                continue;
            }

            if ($character === '/' && $next === '*') {
                $commentEnd = strpos($sql, '*/', $position + 2);
                $commentEnd = $commentEnd === false ? $length : $commentEnd + 2;
                $comment = substr($sql, $position, $commentEnd - $position);

                if (str_starts_with($comment, '/*!') || str_starts_with($comment, '/*M!')) {
                    $buffer .= $comment;
                }

                $position = $commentEnd;

                continue;
            }

            if (substr_compare($sql, $delimiter, $position, strlen($delimiter)) === 0) {
                $this->pushStatement($statements, $buffer);
                $buffer = '';
                $position += strlen($delimiter);

                continue;
            }

            $chunkLength = strcspn($sql, "'\"`#-/\n".$delimiter[0], $position);
            $chunkLength = max(1, $chunkLength);
            $buffer .= substr($sql, $position, $chunkLength);
            $position += $chunkLength;
        }

        $this->pushStatement($statements, $buffer);

        return $statements;
    }

    /**
     * Rewrites engine-specific syntax so a dump from MySQL 8 runs on MariaDB
     * (XAMPP) and vice versa, and removes statements that need SUPER privilege.
     */
    public function adaptForServer(string $statement, string $flavor): ?string
    {
        if (preg_match('/^\s*(?:CREATE\s+(?:DATABASE|SCHEMA)|DROP\s+(?:DATABASE|SCHEMA)|USE\s)/i', $statement) === 1) {
            return null;
        }

        if (preg_match('/@@(?:GLOBAL\.)?GTID_PURGED|@@SESSION\.SQL_LOG_BIN|SQL_LOG_BIN\s*=/i', $statement) === 1) {
            return null;
        }

        $statement = preg_replace('/\sDEFINER\s*=\s*(?:`[^`]*`|\'[^\']*\'|\S+)@(?:`[^`]*`|\'[^\']*\'|\S+)/i', '', $statement) ?? $statement;

        if ($flavor === 'MariaDB') {
            $statement = preg_replace('/utf8mb4_0900_[a-z_]+/i', 'utf8mb4_unicode_ci', $statement) ?? $statement;
            $statement = preg_replace('/\/\*!80\d{3}.*?\*\//s', '', $statement) ?? $statement;
        } else {
            $statement = preg_replace('/utf8mb4_uca1400_[a-z_]+/i', 'utf8mb4_unicode_ci', $statement) ?? $statement;
            $statement = preg_replace('/utf8mb3_uca1400_[a-z_]+/i', 'utf8mb3_unicode_ci', $statement) ?? $statement;
        }

        return trim($statement) === '' ? null : $statement;
    }

    /**
     * Returns the table a data/structure statement targets, or null.
     */
    public function targetTable(string $statement): ?string
    {
        $pattern = '/^\s*(?:\/\*!\d+\s*)?(?:'
            .'DROP\s+TABLE(?:\s+IF\s+EXISTS)?'
            .'|CREATE\s+(?:TEMPORARY\s+)?TABLE(?:\s+IF\s+NOT\s+EXISTS)?'
            .'|INSERT(?:\s+IGNORE)?\s+INTO'
            .'|REPLACE\s+INTO'
            .'|ALTER\s+TABLE'
            .'|LOCK\s+TABLES'
            .'|TRUNCATE(?:\s+TABLE)?'
            .'|DELETE\s+FROM'
            .')\s+`?([A-Za-z0-9_$]+)`?/i';

        return preg_match($pattern, $statement, $match) === 1 ? $match[1] : null;
    }

    public function isSqliteDatabase(string $contents): bool
    {
        return str_starts_with($contents, "SQLite format 3\0");
    }

    /**
     * @param  list<string>  $statements
     */
    private function pushStatement(array &$statements, string $buffer): void
    {
        $statement = trim($buffer);

        if ($statement !== '') {
            $statements[] = $statement;
        }
    }

    private function findQuoteEnd(string $sql, int $start, string $quote): int
    {
        $length = strlen($sql);
        $position = $start + 1;

        while ($position < $length) {
            $jump = strcspn($sql, $quote.'\\', $position);
            $position += $jump;

            if ($position >= $length) {
                return $length;
            }

            if ($sql[$position] === '\\' && $quote !== '`') {
                $position += 2;

                continue;
            }

            if ($sql[$position] === $quote) {
                if (($sql[$position + 1] ?? '') === $quote) {
                    $position += 2;

                    continue;
                }

                return $position + 1;
            }

            $position++;
        }

        return $length;
    }

    private function stripBom(string $sql): string
    {
        return str_starts_with($sql, "\xEF\xBB\xBF") ? substr($sql, 3) : $sql;
    }
}
