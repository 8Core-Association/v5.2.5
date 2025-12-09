<?php

/**
 * Plaćena licenca
 * (c) 2025 Tomislav Galić <tomislav@8core.hr>
 * Suradnik: Marko Šimunović <marko@8core.hr>
 * Web: https://8core.hr
 * Kontakt: info@8core.hr | Tel: +385 099 851 0717
 * Sva prava pridržana. Ovaj softver je vlasnički i zabranjeno ga je
 * distribuirati ili mijenjati bez izričitog dopuštenja autora.
 */

/**
 * Assignment_Helper
 *
 * Helper class for managing user assignments to predmeti
 * Handles assignment logic, permissions, and queries
 */
class Assignment_Helper
{
    /**
     * Ensure assignment columns exist in llx_a_predmet
     *
     * @param DoliDB $db Database handler
     * @return bool Success status
     */
    public static function ensureAssignmentColumns($db)
    {
        $sql = "SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "a_predmet LIKE 'fk_user_assigned'";
        $resql = $db->query($sql);

        if ($resql && $db->num_rows($resql) > 0) {
            return true; // Columns already exist
        }

        // Add columns if they don't exist
        $sql = "ALTER TABLE " . MAIN_DB_PREFIX . "a_predmet
                ADD COLUMN fk_user_assigned INT DEFAULT NULL COMMENT 'Dodijeljeni korisnik',
                ADD COLUMN date_assigned DATETIME DEFAULT NULL COMMENT 'Datum dodjele',
                ADD COLUMN assigned_by INT DEFAULT NULL COMMENT 'Admin koji je dodjelio',
                ADD INDEX idx_assigned (fk_user_assigned),
                ADD INDEX idx_assigned_by (assigned_by)";

        return $db->query($sql);
    }

    /**
     * Assign predmet to user
     *
     * @param DoliDB $db Database handler
     * @param int $predmet_id Predmet ID
     * @param int $user_id User to assign to
     * @param int $assigned_by Admin user ID who is assigning
     * @return bool Success status
     */
    public static function assignUser($db, $predmet_id, $user_id, $assigned_by)
    {
        self::ensureAssignmentColumns($db);

        $sql = "UPDATE " . MAIN_DB_PREFIX . "a_predmet
                SET fk_user_assigned = " . (int)$user_id . ",
                    date_assigned = NOW(),
                    assigned_by = " . (int)$assigned_by . "
                WHERE ID_predmeta = " . (int)$predmet_id;

        $result = $db->query($sql);

        if ($result) {
            dol_syslog("Assignment_Helper::assignUser - Predmet $predmet_id assigned to user $user_id by $assigned_by", LOG_INFO);
            return true;
        } else {
            dol_syslog("Assignment_Helper::assignUser - Failed: " . $db->lasterror(), LOG_ERR);
            return false;
        }
    }

    /**
     * Unassign user from predmet
     *
     * @param DoliDB $db Database handler
     * @param int $predmet_id Predmet ID
     * @return bool Success status
     */
    public static function unassignUser($db, $predmet_id)
    {
        $sql = "UPDATE " . MAIN_DB_PREFIX . "a_predmet
                SET fk_user_assigned = NULL,
                    date_assigned = NULL,
                    assigned_by = NULL
                WHERE ID_predmeta = " . (int)$predmet_id;

        $result = $db->query($sql);

        if ($result) {
            dol_syslog("Assignment_Helper::unassignUser - Predmet $predmet_id unassigned", LOG_INFO);
            return true;
        } else {
            dol_syslog("Assignment_Helper::unassignUser - Failed: " . $db->lasterror(), LOG_ERR);
            return false;
        }
    }

    /**
     * Get assigned user for predmet
     *
     * @param DoliDB $db Database handler
     * @param int $predmet_id Predmet ID
     * @return object|null User object or null
     */
    public static function getAssignedUser($db, $predmet_id)
    {
        $sql = "SELECT
                    p.fk_user_assigned,
                    p.date_assigned,
                    p.assigned_by,
                    u.rowid,
                    u.firstname,
                    u.lastname,
                    u.email,
                    u.login,
                    u.photo,
                    assigner.firstname as assigned_by_firstname,
                    assigner.lastname as assigned_by_lastname
                FROM " . MAIN_DB_PREFIX . "a_predmet p
                LEFT JOIN " . MAIN_DB_PREFIX . "user u ON p.fk_user_assigned = u.rowid
                LEFT JOIN " . MAIN_DB_PREFIX . "user assigner ON p.assigned_by = assigner.rowid
                WHERE p.ID_predmeta = " . (int)$predmet_id;

        $resql = $db->query($sql);

        if ($resql && $obj = $db->fetch_object($resql)) {
            return $obj;
        }

        return null;
    }

    /**
     * Get all active users for assignment dropdown
     *
     * @param DoliDB $db Database handler
     * @param Conf $conf Dolibarr config
     * @return array Array of user objects
     */
    public static function getActiveUsers($db, $conf)
    {
        $sql = "SELECT
                    rowid,
                    firstname,
                    lastname,
                    email,
                    login,
                    photo,
                    admin
                FROM " . MAIN_DB_PREFIX . "user
                WHERE entity IN (0, " . $conf->entity . ")
                AND statut = 1
                ORDER BY lastname, firstname";

        $resql = $db->query($sql);
        $users = [];

        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $users[] = $obj;
            }
        }

        return $users;
    }

    /**
     * Check if user has access to predmet
     *
     * @param DoliDB $db Database handler
     * @param User $user Dolibarr user object
     * @param int $predmet_id Predmet ID
     * @return bool True if user has access
     */
    public static function userHasAccess($db, $user, $predmet_id)
    {
        // Admin always has access
        if ($user->admin) {
            return true;
        }

        // Check if predmet is assigned to this user
        $sql = "SELECT COUNT(*) as count
                FROM " . MAIN_DB_PREFIX . "a_predmet
                WHERE ID_predmeta = " . (int)$predmet_id . "
                AND fk_user_assigned = " . (int)$user->id;

        $resql = $db->query($sql);

        if ($resql && $obj = $db->fetch_object($resql)) {
            return ($obj->count > 0);
        }

        return false;
    }

    /**
     * Get accessible predmet IDs for user
     * Returns NULL for admin (= see all), array of IDs for regular users
     *
     * @param DoliDB $db Database handler
     * @param User $user Dolibarr user object
     * @return array|null Array of predmet IDs or NULL (for admin)
     */
    public static function getAccessiblePredmetiIds($db, $user)
    {
        // Admin sees all predmeti
        if ($user->admin) {
            return null; // null = no filter needed
        }

        // Get predmeti assigned to this user
        $sql = "SELECT ID_predmeta
                FROM " . MAIN_DB_PREFIX . "a_predmet
                WHERE fk_user_assigned = " . (int)$user->id;

        $resql = $db->query($sql);
        $ids = [];

        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $ids[] = $obj->ID_predmeta;
            }
        }

        return $ids;
    }

    /**
     * Get assignment statistics
     *
     * @param DoliDB $db Database handler
     * @return array Statistics array
     */
    public static function getAssignmentStats($db)
    {
        $stats = [
            'total_predmeti' => 0,
            'assigned_predmeti' => 0,
            'unassigned_predmeti' => 0
        ];

        // Total predmeti
        $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "a_predmet";
        $resql = $db->query($sql);
        if ($resql && $obj = $db->fetch_object($resql)) {
            $stats['total_predmeti'] = $obj->count;
        }

        // Assigned predmeti
        $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "a_predmet
                WHERE fk_user_assigned IS NOT NULL";
        $resql = $db->query($sql);
        if ($resql && $obj = $db->fetch_object($resql)) {
            $stats['assigned_predmeti'] = $obj->count;
        }

        // Unassigned
        $stats['unassigned_predmeti'] = $stats['total_predmeti'] - $stats['assigned_predmeti'];

        return $stats;
    }

    /**
     * Get predmeti assigned to specific user
     *
     * @param DoliDB $db Database handler
     * @param int $user_id User ID
     * @return array Array of predmet objects
     */
    public static function getPredmetiByUser($db, $user_id)
    {
        $sql = "SELECT
                    p.ID_predmeta,
                    p.klasa_br,
                    p.sadrzaj,
                    p.dosje_broj,
                    p.godina,
                    p.predmet_rbr,
                    p.naziv_predmeta,
                    p.date_assigned,
                    DATE_FORMAT(p.tstamp_created, '%d/%m/%Y') as datum_otvaranja
                FROM " . MAIN_DB_PREFIX . "a_predmet p
                WHERE p.fk_user_assigned = " . (int)$user_id . "
                ORDER BY p.date_assigned DESC";

        $resql = $db->query($sql);
        $predmeti = [];

        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $predmeti[] = $obj;
            }
        }

        return $predmeti;
    }
}
