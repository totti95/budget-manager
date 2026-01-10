<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class CsvImportService
{
    public const MAX_ROWS = 1000;
    public const MAX_FILE_SIZE_KB = 2048;
    public const EXPECTED_HEADERS = ['date', 'label', 'amount_cents', 'subcategory', 'payment_method', 'notes'];

    /**
     * Valider le fichier CSV
     */
    public function validate(UploadedFile $file): void
    {
        // Vérifier la taille du fichier
        if ($file->getSize() > self::MAX_FILE_SIZE_KB * 1024) {
            throw new \Exception('Le fichier est trop volumineux. Taille maximale : ' . self::MAX_FILE_SIZE_KB . ' Ko');
        }

        // Vérifier l'extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['csv', 'txt'])) {
            throw new \Exception('Extension de fichier non autorisée. Seuls .csv et .txt sont acceptés.');
        }

        // Vérifier le type MIME
        $mimeType = $file->getMimeType();
        $allowedMimeTypes = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'];
        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw new \Exception("Type MIME non autorisé : {$mimeType}");
        }
    }

    /**
     * Parser et valider le contenu CSV
     *
     * @return array ['data' => [...], 'errors' => [...]]
     */
    public function parse(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            throw new \Exception("Impossible d'ouvrir le fichier CSV");
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            throw new \Exception('Le fichier CSV est vide');
        }

        // Valider l'en-tête
        $this->validateHeader($header);

        $data = [];
        $errors = [];
        $rowNumber = 1; // Ligne 1 est l'en-tête

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Limite de lignes
            if ($rowNumber > self::MAX_ROWS + 1) {
                $errors[] = 'Limite de ' . self::MAX_ROWS . ' lignes atteinte. Les lignes suivantes ont été ignorées.';
                break;
            }

            // Vérifier que le nombre de colonnes correspond
            if (count($row) !== count($header)) {
                $errors[] = "Ligne {$rowNumber}: Nombre de colonnes incorrect (attendu: " . count($header) . ', trouvé: ' . count($row) . ')';
                continue;
            }

            try {
                $rowData = array_combine($header, $row);
                $validated = $this->validateRow($rowData, $rowNumber);
                $data[] = $validated;
            } catch (\Exception $e) {
                $errors[] = "Ligne {$rowNumber}: " . $e->getMessage();
                Log::warning("CSV import error on line {$rowNumber}", [
                    'error' => $e->getMessage(),
                    'row' => $row,
                ]);
            }
        }

        fclose($handle);

        return [
            'data' => $data,
            'errors' => $errors,
        ];
    }

    /**
     * Valider l'en-tête du CSV
     */
    private function validateHeader(array $header): void
    {
        // Normaliser les en-têtes (trim et minuscules)
        $normalized = array_map(fn ($h) => strtolower(trim($h)), $header);

        // Vérifier que les colonnes obligatoires sont présentes
        $required = ['date', 'label', 'amount_cents', 'subcategory'];
        $missing = array_diff($required, $normalized);

        if (!empty($missing)) {
            throw new \Exception(
                "Colonnes obligatoires manquantes dans l'en-tête : " . implode(', ', $missing) .
                '. En-tête attendu : ' . implode(', ', self::EXPECTED_HEADERS)
            );
        }
    }

    /**
     * Valider et normaliser une ligne de données
     */
    private function validateRow(array $data, int $rowNumber): array
    {
        // Date
        if (empty($data['date'])) {
            throw new \Exception('Date manquante');
        }
        $date = $this->parseDate($data['date']);

        // Label
        $label = trim($data['label'] ?? '');
        if (empty($label)) {
            throw new \Exception('Label manquant');
        }
        if (strlen($label) > 255) {
            throw new \Exception('Label trop long (max 255 caractères)');
        }

        // Amount
        if (!isset($data['amount_cents']) || $data['amount_cents'] === '') {
            throw new \Exception('Montant manquant');
        }
        $amountCents = $this->parseAmount($data['amount_cents']);

        // Subcategory
        $subcategory = trim($data['subcategory'] ?? '');
        if (empty($subcategory)) {
            throw new \Exception('Sous-catégorie manquante');
        }

        // Payment method (optionnel)
        $paymentMethod = !empty($data['payment_method']) ? trim($data['payment_method']) : null;
        if ($paymentMethod && strlen($paymentMethod) > 255) {
            throw new \Exception('Méthode de paiement trop longue (max 255 caractères)');
        }

        // Notes (optionnel)
        $notes = !empty($data['notes']) ? trim($data['notes']) : null;

        return [
            'date' => $date,
            'label' => $label,
            'amount_cents' => $amountCents,
            'subcategory' => $subcategory,
            'payment_method' => $paymentMethod,
            'notes' => $notes,
        ];
    }

    /**
     * Parser une date avec différents formats possibles
     */
    private function parseDate(string $dateStr): string
    {
        $dateStr = trim($dateStr);

        // Essayer différents formats
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $dateStr);
            if ($date && $date->format($format) === $dateStr) {
                return $date->format('Y-m-d');
            }
        }

        throw new \Exception("Format de date invalide: '{$dateStr}'. Formats acceptés: YYYY-MM-DD, DD/MM/YYYY, DD-MM-YYYY");
    }

    /**
     * Parser le montant en centimes
     */
    private function parseAmount(string|int $amount): int
    {
        // Si c'est déjà un entier, on suppose que c'est en centimes
        if (is_int($amount)) {
            if ($amount < 0) {
                throw new \Exception('Le montant ne peut pas être négatif');
            }
            return $amount;
        }

        // Nettoyer la string: supprimer espaces, €, etc.
        $cleaned = trim(str_replace([' ', '€', ','], ['', '', '.'], $amount));

        if (!is_numeric($cleaned)) {
            throw new \Exception("Montant invalide: '{$amount}'");
        }

        $amountCents = (int) $cleaned;
        if ($amountCents < 0) {
            throw new \Exception('Le montant ne peut pas être négatif');
        }

        return $amountCents;
    }
}
