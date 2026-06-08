<?php
namespace App\Helpers;

class SimplePdf
{
    public static function table(string $title, array $headers, array $rows, array $kpis = []): string
    {
        $lines = [$title, 'Generated: ' . date('Y-m-d H:i:s'), ''];
        if (!empty($kpis)) {
            $metricLine = [];
            foreach ($kpis as $kpi) {
                $metricLine[] = $kpi['label'] . ': ' . $kpi['value'];
            }
            $lines[] = implode('   ', $metricLine);
            $lines[] = '';
        }

        $lines[] = implode(' | ', array_values($headers));
        $lines[] = str_repeat('-', 110);

        foreach ($rows as $row) {
            $cells = [];
            foreach (array_keys($headers) as $key) {
                $cells[] = self::clip((string)($row[$key] ?? ''), 24);
            }
            $lines[] = implode(' | ', $cells);
        }

        if (empty($rows)) {
            $lines[] = 'No records matched this report.';
        }

        return self::document($lines);
    }

    private static function document(array $lines): string
    {
        $chunks = array_chunk($lines, 48);
        $objects = [];
        $pages = [];

        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[] = '';
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>";

        foreach ($chunks as $pageIndex => $pageLines) {
            $pageObj = count($objects) + 1;
            $contentObj = $pageObj + 1;
            $pages[] = $pageObj . ' 0 R';

            $stream = "BT\n/F1 9 Tf\n40 790 Td\n12 TL\n";
            foreach ($pageLines as $line) {
                $stream .= '(' . self::escape($line) . ") Tj\nT*\n";
            }
            $stream .= "ET";

            $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 842] /Resources << /Font << /F1 3 0 R >> >> /Contents {$contentObj} 0 R >>";
            $objects[] = "<< /Length " . strlen($stream) . " >>\nstream\n{$stream}\nendstream";
        }

        $objects[1] = "<< /Type /Pages /Kids [" . implode(' ', $pages) . "] /Count " . count($pages) . " >>";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    private static function escape(string $text): string
    {
        $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private static function clip(string $text, int $length): string
    {
        $text = preg_replace('/\s+/', ' ', $text);
        return strlen($text) > $length ? substr($text, 0, $length - 1) . '.' : $text;
    }
}
