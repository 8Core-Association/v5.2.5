<?php

/**
 * Plaćena licenca
 * (c) 2025 8Core Association
 * Tomislav Galić <tomislav@8core.hr>
 * Marko Šimunović <marko@8core.hr>
 * Web: https://8core.hr
 * Kontakt: info@8core.hr | Tel: +385 099 851 0717
 * Sva prava pridržana. Ovaj softver je vlasnički i zaštićen je autorskim i srodnim pravima
 * te ga je izričito zabranjeno umnožavati, distribuirati, mijenjati, objavljivati ili
 * na drugi način eksploatirati bez pismenog odobrenja autora.
 */

/**
 * Interna Oznaka Korisnika Helper Class for SEUP Module
 * Handles internal user designation management and related operations
 */
class Interna_oznaka_korisnika_helper
{
    /**
     * Get internal user designation details by ID
     */
    public static function getInternaOznakaDetails($db, $id)
    {
        try {
            $sql = "SELECT
                        ID,
                        ID_ustanove,
                        ime_prezime,
                        rbr,
                        naziv
                    FROM " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika
                    WHERE ID = " . (int)$id;

            $resql = $db->query($sql);
            if ($resql && $obj = $db->fetch_object($resql)) {
                return [
                    'success' => true,
                    'oznaka' => $obj
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Interna oznaka nije pronađena'
                ];
            }

        } catch (Exception $e) {
            dol_syslog("Error getting interna oznaka details: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get user data by ime_prezime
     */
    public static function getUserData($db, $ime_prezime)
    {
        try {
            if (empty($ime_prezime)) {
                return [
                    'success' => false,
                    'error' => 'Ime korisnika nije poslano'
                ];
            }

            $sql = "SELECT rbr, naziv
                    FROM " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika
                    WHERE ime_prezime = '" . $db->escape($ime_prezime) . "'
                    LIMIT 1";

            $resql = $db->query($sql);

            if ($resql && $db->num_rows($resql) > 0) {
                $obj = $db->fetch_object($resql);
                return [
                    'success' => true,
                    'data' => [
                        'redni_broj' => (int)$obj->rbr,
                        'radno_mjesto' => $obj->naziv
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Korisnik nema unesene podatke'
                ];
            }

        } catch (Exception $e) {
            dol_syslog("Error getting user data: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Add new internal user designation
     */
    public static function addInternaOznaka($db, $ID_ustanove, $ime_prezime, $rbr, $naziv)
    {
        try {
            // Validations
            if (empty($ime_prezime) || $rbr === '' || empty($naziv)) {
                return [
                    'success' => false,
                    'error' => 'Sva polja su obavezna'
                ];
            }

            if (!self::validateRedniBroj($rbr)) {
                return [
                    'success' => false,
                    'error' => 'Neispravan redni broj (vrijednosti moraju biti u rasponu 0 - 99)'
                ];
            }

            // Check for duplicate
            if (self::checkDuplicateRedniBroj($db, $rbr)) {
                return [
                    'success' => false,
                    'error' => 'Korisnik s tim rednim brojem već postoji u bazi'
                ];
            }

            $db->begin();

            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika
                    (ID_ustanove, ime_prezime, rbr, naziv)
                    VALUES (
                        " . (int)$ID_ustanove . ",
                        '" . $db->escape($ime_prezime) . "',
                        '" . $db->escape($rbr) . "',
                        '" . $db->escape($naziv) . "'
                    )";

            if ($db->query($sql)) {
                $db->commit();
                return [
                    'success' => true,
                    'message' => 'Interna Oznaka Korisnika uspješno dodana',
                    'id' => $db->last_insert_id(MAIN_DB_PREFIX . "a_interna_oznaka_korisnika")
                ];
            } else {
                $db->rollback();
                return [
                    'success' => false,
                    'error' => 'Greška baze: ' . $db->lasterror()
                ];
            }

        } catch (Exception $e) {
            $db->rollback();
            dol_syslog("Error adding interna oznaka: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update existing internal user designation
     */
    public static function updateInternaOznaka($db, $id, $ime_prezime, $rbr, $naziv)
    {
        try {
            if (!$id) {
                return [
                    'success' => false,
                    'error' => 'ID je obavezan za ažuriranje'
                ];
            }

            if (empty($ime_prezime) || $rbr === '' || empty($naziv)) {
                return [
                    'success' => false,
                    'error' => 'Sva polja su obavezna'
                ];
            }

            if (!self::validateRedniBroj($rbr)) {
                return [
                    'success' => false,
                    'error' => 'Neispravan redni broj (vrijednosti moraju biti u rasponu 0 - 99)'
                ];
            }

            // Check for duplicate (excluding current record)
            if (self::checkDuplicateRedniBroj($db, $rbr, $id)) {
                return [
                    'success' => false,
                    'error' => 'Korisnik s tim rednim brojem već postoji u bazi'
                ];
            }

            $db->begin();

            $sql = "UPDATE " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika
                    SET ime_prezime = '" . $db->escape($ime_prezime) . "',
                        rbr = '" . $db->escape($rbr) . "',
                        naziv = '" . $db->escape($naziv) . "'
                    WHERE ID = " . (int)$id;

            if ($db->query($sql)) {
                $db->commit();
                return [
                    'success' => true,
                    'message' => 'Interna Oznaka Korisnika uspješno ažurirana'
                ];
            } else {
                $db->rollback();
                return [
                    'success' => false,
                    'error' => 'Greška baze: ' . $db->lasterror()
                ];
            }

        } catch (Exception $e) {
            $db->rollback();
            dol_syslog("Error updating interna oznaka: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete internal user designation
     */
    public static function deleteInternaOznaka($db, $id)
    {
        try {
            if (!$id) {
                return [
                    'success' => false,
                    'error' => 'ID je obavezan za brisanje'
                ];
            }

            $db->begin();

            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika
                    WHERE ID = " . (int)$id;

            if ($db->query($sql)) {
                $db->commit();
                return [
                    'success' => true,
                    'message' => 'Interna Oznaka Korisnika uspješno obrisana'
                ];
            } else {
                $db->rollback();
                return [
                    'success' => false,
                    'error' => 'Greška baze: ' . $db->lasterror()
                ];
            }

        } catch (Exception $e) {
            $db->rollback();
            dol_syslog("Error deleting interna oznaka: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Search internal user designations (autocomplete)
     */
    public static function searchInterneOznake($db, $search)
    {
        try {
            if (strlen($search) < 3) {
                return [
                    'success' => false,
                    'message' => 'Minimalno 3 slova'
                ];
            }

            $sql = "SELECT ID, ime_prezime, rbr, naziv
                    FROM " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika
                    WHERE ime_prezime LIKE '%" . $db->escape($search) . "%'
                    ORDER BY ime_prezime ASC
                    LIMIT 10";

            $resql = $db->query($sql);

            $results = [];
            if ($resql) {
                while ($obj = $db->fetch_object($resql)) {
                    $results[] = [
                        'id' => (int)$obj->ID,
                        'ime' => $obj->ime_prezime,
                        'rbr' => (int)$obj->rbr,
                        'naziv' => $obj->naziv,
                        'label' => $obj->ime_prezime . ' [' . sprintf('%02d', $obj->rbr) . '] - ' . $obj->naziv
                    ];
                }
            }

            return [
                'success' => true,
                'results' => $results
            ];

        } catch (Exception $e) {
            dol_syslog("Error searching interne oznake: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get all internal user designations
     */
    public static function getAllInterneOznake($db, $ID_ustanove = null)
    {
        try {
            $sql = "SELECT ID, ID_ustanove, ime_prezime, rbr, naziv
                    FROM " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika";

            if ($ID_ustanove !== null) {
                $sql .= " WHERE ID_ustanove = " . (int)$ID_ustanove;
            }

            $sql .= " ORDER BY rbr ASC";

            $resql = $db->query($sql);
            $oznake = [];

            if ($resql) {
                while ($obj = $db->fetch_object($resql)) {
                    $oznake[] = $obj;
                }
            }

            return [
                'success' => true,
                'oznake' => $oznake,
                'count' => count($oznake)
            ];

        } catch (Exception $e) {
            dol_syslog("Error getting all interne oznake: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get statistics
     */
    public static function getStatistics($db, $ID_ustanove = null)
    {
        try {
            $stats = [
                'total' => 0,
                'with_radno_mjesto' => 0,
                'avg_rbr' => 0,
                'min_rbr' => null,
                'max_rbr' => null
            ];

            $whereClause = $ID_ustanove ? " WHERE ID_ustanove = " . (int)$ID_ustanove : "";

            // Total count
            $sql = "SELECT COUNT(*) as total FROM " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika" . $whereClause;
            $resql = $db->query($sql);
            if ($resql && $obj = $db->fetch_object($resql)) {
                $stats['total'] = (int)$obj->total;
            }

            // With radno mjesto
            $sql = "SELECT COUNT(*) as count
                    FROM " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika
                    " . $whereClause . ($whereClause ? " AND" : " WHERE") . " naziv IS NOT NULL AND naziv != ''";
            $resql = $db->query($sql);
            if ($resql && $obj = $db->fetch_object($resql)) {
                $stats['with_radno_mjesto'] = (int)$obj->count;
            }

            // RBR statistics
            $sql = "SELECT AVG(rbr) as avg_rbr, MIN(rbr) as min_rbr, MAX(rbr) as max_rbr
                    FROM " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika" . $whereClause;
            $resql = $db->query($sql);
            if ($resql && $obj = $db->fetch_object($resql)) {
                $stats['avg_rbr'] = round((float)$obj->avg_rbr, 2);
                $stats['min_rbr'] = (int)$obj->min_rbr;
                $stats['max_rbr'] = (int)$obj->max_rbr;
            }

            return [
                'success' => true,
                'stats' => $stats
            ];

        } catch (Exception $e) {
            dol_syslog("Error getting statistics: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate redni broj format
     */
    public static function validateRedniBroj($rbr)
    {
        return preg_match('/^\d{1,2}$/', (string)$rbr) && $rbr >= 0 && $rbr <= 99;
    }

    /**
     * Check if redni broj already exists
     */
    public static function checkDuplicateRedniBroj($db, $rbr, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as cnt
                FROM " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika
                WHERE rbr = '" . $db->escape($rbr) . "'";

        if ($excludeId !== null) {
            $sql .= " AND ID != " . (int)$excludeId;
        }

        $resql = $db->query($sql);
        if ($resql && $obj = $db->fetch_object($resql)) {
            return (int)$obj->cnt > 0;
        }

        return false;
    }

    /**
     * Export to CSV format
     */
    public static function exportToCSV($db, $ID_ustanove = null)
    {
        try {
            $result = self::getAllInterneOznake($db, $ID_ustanove);

            if (!$result['success'] || empty($result['oznake'])) {
                return [
                    'success' => false,
                    'error' => 'Nema podataka za izvoz'
                ];
            }

            $oznake = $result['oznake'];

            // Create CSV content
            $csvContent = "Rb.,Ime_i_Prezime,Redni_Broj,Radno_Mjesto\n";

            foreach ($oznake as $index => $oznaka) {
                $csvContent .= sprintf(
                    "%d,\"%s\",\"%02d\",\"%s\"\n",
                    $index + 1,
                    str_replace('"', '""', $oznaka->ime_prezime),
                    (int)$oznaka->rbr,
                    str_replace('"', '""', $oznaka->naziv)
                );
            }

            // Save to ECM temp folder
            $filename = 'interne_oznake_' . date('Y-m-d_H-i-s') . '.csv';
            $tempPath = DOL_DATA_ROOT . '/ecm/temp/' . $filename;

            // Ensure temp directory exists
            if (!is_dir(DOL_DATA_ROOT . '/ecm/temp/')) {
                dol_mkdir(DOL_DATA_ROOT . '/ecm/temp/');
            }

            // Write CSV with UTF-8 BOM for proper Excel encoding
            $csvWithBOM = "\xEF\xBB\xBF" . $csvContent;
            file_put_contents($tempPath, $csvWithBOM);

            $downloadUrl = DOL_URL_ROOT . '/document.php?modulepart=ecm&file=' . urlencode('temp/' . $filename);

            return [
                'success' => true,
                'message' => 'CSV datoteka je kreirana',
                'filename' => $filename,
                'download_url' => $downloadUrl,
                'records_count' => count($oznake)
            ];

        } catch (Exception $e) {
            dol_syslog("Error exporting CSV: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format redni broj with leading zero
     */
    public static function formatRedniBroj($rbr)
    {
        return sprintf('%02d', (int)$rbr);
    }

    /**
     * Get available redni brojevi (not used)
     */
    public static function getAvailableRedniBrojevi($db, $ID_ustanove = null)
    {
        try {
            $sql = "SELECT rbr FROM " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika";
            if ($ID_ustanove !== null) {
                $sql .= " WHERE ID_ustanove = " . (int)$ID_ustanove;
            }

            $resql = $db->query($sql);
            $usedNumbers = [];

            if ($resql) {
                while ($obj = $db->fetch_object($resql)) {
                    $usedNumbers[] = (int)$obj->rbr;
                }
            }

            $availableNumbers = [];
            for ($i = 0; $i <= 99; $i++) {
                if (!in_array($i, $usedNumbers)) {
                    $availableNumbers[] = $i;
                }
            }

            return [
                'success' => true,
                'available' => $availableNumbers,
                'count' => count($availableNumbers)
            ];

        } catch (Exception $e) {
            dol_syslog("Error getting available redni brojevi: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
