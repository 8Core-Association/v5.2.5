<?php
/**
 * SEUP - Instalacija tablice Zaprimanja
 * Ova stranica kreira tablicu llx_a_zaprimanje
 */

echo "<h1>SEUP - Instalacija Tablice Zaprimanja</h1>";
echo "<pre>";

// Prikaži upute za pokretanje
echo "=== UPUTE ZA INSTALACIJU ===\n\n";
echo "Za instalaciju tablice llx_a_zaprimanje imate 2 opcije:\n\n";

echo "OPCIJA 1 - AUTOMATSKA INSTALACIJA (PREPORUČENO):\n";
echo "1. Idi u Dolibarr → Setup → Modules\n";
echo "2. Deaktiviraj SEUP modul\n";
echo "3. Ponovno aktiviraj SEUP modul\n";
echo "   (To će automatski pokrenuti sve SQL skripte iz /seup/sql/)\n\n";

echo "OPCIJA 2 - RUČNA INSTALACIJA:\n";
echo "1. Pokreni SQL skriptu na MariaDB bazi:\n";
echo "   mysql -u [korisnik] -p [baza] < " . __DIR__ . "/sql/llx_a_zaprimanje.sql\n\n";

echo "2. ALTERNATIVNO - kopiraj i izvrši SQL direktno u phpMyAdmin:\n\n";

// Pročitaj i prikaži SQL
$sql_file = __DIR__ . '/sql/llx_a_zaprimanje.sql';
if (file_exists($sql_file)) {
    echo "--- SQL KOD ZA IZVRŠITI ---\n\n";
    $sql = file_get_contents($sql_file);
    echo $sql;
    echo "\n\n";
}

echo "=== PROVJERA ===\n\n";
echo "Nakon instalacije, provjeri da li tablica postoji:\n";
echo "  SHOW TABLES LIKE 'llx_a_zaprimanje';\n";
echo "  DESCRIBE llx_a_zaprimanje;\n\n";

echo "=== DATOTEKE SUSTAVA ===\n\n";

$files = [
    'SQL migracija' => 'sql/llx_a_zaprimanje.sql',
    'Helper klasa' => 'class/zaprimanje_helper.class.php',
    'Stranica' => 'pages/zaprimanja.php',
    'JavaScript' => 'js/zaprimanja.js',
    'CSS' => 'css/zaprimanja.css'
];

foreach ($files as $name => $path) {
    $full_path = __DIR__ . '/' . $path;
    $exists = file_exists($full_path) ? '✓ POSTOJI' : '✗ NE POSTOJI';
    $size = file_exists($full_path) ? ' (' . number_format(filesize($full_path)) . ' bytes)' : '';
    echo sprintf("%-20s: %s %s%s\n", $name, $exists, $path, $size);
}

echo "\n=== STRUKTURA TABLICE ===\n\n";

echo "llx_a_zaprimanje:\n";
echo "  - ID_zaprimanja (PK, AUTO_INCREMENT)\n";
echo "  - ID_predmeta (FK → llx_a_predmet)\n";
echo "  - fk_ecm_file (FK → llx_ecm_files)\n";
echo "  - tip_dokumenta (akt|prilog|nedodjeljeni)\n";
echo "  - fk_posiljatelj (FK → llx_societe)\n";
echo "  - posiljatelj_naziv\n";
echo "  - datum_zaprimanja (DATETIME)\n";
echo "  - nacin_zaprimanja (posta|email|rucno|fax|web|sluzben_put)\n";
echo "  - broj_priloga\n";
echo "  - fk_potvrda_ecm_file (FK → llx_ecm_files)\n";
echo "  - opis_zaprimanja\n";
echo "  - napomena\n";
echo "  - fk_user_zaprimio (FK → llx_user)\n";
echo "  - datum_kreiranja\n";
echo "  - entity\n\n";

echo "</pre>";

echo "<h2>Sljedeći koraci:</h2>";
echo "<ol>";
echo "<li><strong>Preporučeno:</strong> Deaktiviraj i ponovno aktiviraj SEUP modul</li>";
echo "<li>Ili: Kopiraj SQL kod iznad i izvrši ga u MariaDB bazi</li>";
echo "<li>Provjeri da li tablica llx_a_zaprimanje postoji</li>";
echo "<li>Pristupite stranici /custom/seup/pages/zaprimanja.php</li>";
echo "</ol>";
?>
