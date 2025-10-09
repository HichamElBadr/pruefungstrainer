<?php

namespace App\Services;
use PDO;

class QueryHandler
{
     public static function executeUserQuery(PDO $pdo, string $sql): string
    {
        try {
            $stmt = $pdo->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                return "<p>Keine Ergebnisse gefunden.</p>";
            }

            $html = "<table border='1'><tr>";
            foreach (array_keys($rows[0]) as $col) {
                $html .= "<th>$col</th>";
            }
            $html .= "</tr>";

            foreach ($rows as $row) {
                $html .= "<tr>";
                foreach ($row as $val) {
                    $html .= "<td>$val</td>";
                }
                $html .= "</tr>";
            }
            $html .= "</table>";

            return $html;
        } catch (\Exception $e) {
            return "<p style='color:red;'>Fehler: " . $e->getMessage() . "</p>";
        }
    }
}