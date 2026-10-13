<?php

namespace App\Support;

use XMLWriter;
use ZipArchive;

/** Écrit le format XLSX standard, sans exécuter les valeurs utilisateur comme formules. */
class ExpenseWorkbook
{
    public function create(iterable $expenses): string
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElementNS(null, 'worksheet', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $xml->startElement('sheetData');
        $row = 1;
        $this->row($xml, $row++, ['ID', 'Chantier', 'Produit / Facture', 'Quantité', 'Unité', 'Prix Unit.', 'Prix Total', 'Catégorie', 'Statut', 'Créé le']);
        foreach ($expenses as $expense) {
            $this->row($xml, $row++, [
                $expense->id, $expense->chantier?->nom ?? 'N/A', $expense->libelle,
                $expense->quantite, $expense->unite?->nom ?? 'N/A', $expense->prix_unitaire,
                $expense->prix_total, $expense->categorie?->nom ?? 'N/A',
                ucfirst(str_replace('_', ' ', $expense->statut_validation)),
                $expense->created_at->format('d/m/Y'),
            ]);
        }
        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();

        $path = tempnam(sys_get_temp_dir(), 'expense-workbook-');
        if ($path === false) {
            throw new \RuntimeException('Impossible de préparer le fichier Excel.');
        }
        $zip = new ZipArchive;
        try {
            if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Impossible de créer le fichier Excel.');
            }
            $entries = [
                '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>',
                '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
                'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Dépenses" sheetId="1" r:id="rId1"/></sheets></workbook>',
                'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>',
                'xl/worksheets/sheet1.xml' => $xml->outputMemory(),
            ];
            foreach ($entries as $name => $content) {
                if (! $zip->addFromString($name, $content)) {
                    throw new \RuntimeException('Impossible de compléter le fichier Excel.');
                }
            }
            if (! $zip->close()) {
                throw new \RuntimeException('Impossible de finaliser le fichier Excel.');
            }
        } catch (\Throwable $exception) {
            if (is_file($path)) {
                unlink($path);
            }
            throw $exception;
        }

        return $path;
    }

    private function row(XMLWriter $xml, int $row, array $values): void
    {
        $xml->startElement('row');
        $xml->writeAttribute('r', (string) $row);
        foreach ($values as $index => $value) {
            $xml->startElement('c');
            $xml->writeAttribute('r', chr(65 + $index).$row);
            if ($row > 1 && in_array($index, [0, 3, 5, 6], true) && $value !== null) {
                $xml->writeAttribute('t', 'n');
                $xml->writeElement('v', (string) $value);
            } else {
                $xml->writeAttribute('t', 'inlineStr');
                $xml->startElement('is');
                $xml->startElement('t');
                $xml->writeAttribute('xml:space', 'preserve');
                $xml->text(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string) $value));
                $xml->endElement();
                $xml->endElement();
            }
            $xml->endElement();
        }
        $xml->endElement();
    }
}
