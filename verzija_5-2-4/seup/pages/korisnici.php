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
 * SEUP Module - Popis zaposlenika (Interne Oznake Korisnika)
 * Stranica za upravljanje internim oznakama korisnika
 */

// Učitaj osnove
$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
  $res = include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
  $res = include "../../../main.inc.php";
}
if (!$res) {
  die("Include of main fails");
}

// Potrebne uključene datoteke
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once __DIR__ . '/../lib/seup.lib.php';
require_once __DIR__ . '/../class/interna_oznaka_korisnika_helper.class.php';

// Učitaj prijevode
$langs->loadLangs(array("seup@seup", "admin"));

// Provjera pristupa
if (!$user->rights->seup->korisnici->read) {
  accessforbidden();
}

$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
$confirm = GETPOST('confirm', 'alpha');

// Inicijaliziraj objekte
$form = new Form($db);

// Get ustanove ID
$ID_ustanove = 1; // Default
$sql_ustanova = "SELECT ID FROM " . MAIN_DB_PREFIX . "a_oznaka_ustanove LIMIT 1";
$resql_ustanova = $db->query($sql_ustanova);
if ($resql_ustanova && $obj = $db->fetch_object($resql_ustanova)) {
  $ID_ustanove = (int)$obj->ID;
}

// Load all active users from Dolibarr
$listUsers = [];
$userStatic = new User($db);
$resql = $db->query("SELECT rowid FROM " . MAIN_DB_PREFIX . "user WHERE statut = 1 ORDER BY lastname ASC");
if ($resql) {
  while ($o = $db->fetch_object($resql)) {
    $userStatic->fetch($o->rowid);
    $listUsers[] = clone $userStatic;
  }
}

// === EXPORT TO CSV (GET request) ===
if ($action === 'export_csv') {
  $result = Interna_oznaka_korisnika_helper::exportToCSV($db, $ID_ustanove);

  if ($result['success']) {
    header('Location: ' . $result['download_url']);
    exit;
  } else {
    setEventMessages($result['error'], null, 'errors');
  }
}

// === DELETE OZNAKA (GET request with confirmation) ===
if ($action === 'confirm_delete' && $confirm === 'yes') {
  if (!$user->rights->seup->korisnici->delete) {
    accessforbidden();
  }

  $result = Interna_oznaka_korisnika_helper::deleteInternaOznaka($db, $id);

  if ($result['success']) {
    setEventMessages($result['message'], null, 'mesgs');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  } else {
    setEventMessages($result['error'], null, 'errors');
  }
}

// === AJAX HANDLERS ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Get user data AJAX
  if ($action === 'get_user_data') {
    header('Content-Type: application/json; charset=UTF-8');
    if (function_exists('ob_get_level') && ob_get_level() > 0) { ob_end_clean(); }

    $ime_user = GETPOST('ime_user', 'alphanohtml');
    $result = Interna_oznaka_korisnika_helper::getUserData($db, $ime_user);

    if (!$result['success'] && isset($result['error'])) {
      http_response_code(400);
    }

    echo json_encode($result);
    exit;
  }

  // Autocomplete search AJAX
  if ($action === 'autocomplete') {
    header('Content-Type: application/json; charset=UTF-8');
    if (function_exists('ob_get_level') && ob_get_level() > 0) { ob_end_clean(); }

    $search = GETPOST('search', 'alphanohtml');
    $result = Interna_oznaka_korisnika_helper::searchInterneOznake($db, $search);

    echo json_encode($result);
    exit;
  }

  // Add new oznaka
  if ($action === 'add' && !empty($_POST)) {
    if (!$user->rights->seup->korisnici->write) {
      accessforbidden();
    }

    $ime_user = GETPOST('ime_user', 'alphanohtml');
    $redni_broj = GETPOST('redni_broj', 'int');
    $radno_mjesto = GETPOST('radno_mjesto', 'alphanohtml');

    $result = Interna_oznaka_korisnika_helper::addInternaOznaka($db, $ID_ustanove, $ime_user, $redni_broj, $radno_mjesto);

    if ($result['success']) {
      setEventMessages($result['message'], null, 'mesgs');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    } else {
      setEventMessages($result['error'], null, 'errors');
    }
  }

  // Update oznaka
  if ($action === 'update' && !empty($_POST)) {
    if (!$user->rights->seup->korisnici->write) {
      accessforbidden();
    }

    $ime_user = GETPOST('ime_user', 'alphanohtml');
    $redni_broj = GETPOST('redni_broj', 'int');
    $radno_mjesto = GETPOST('radno_mjesto', 'alphanohtml');

    $result = Interna_oznaka_korisnika_helper::updateInternaOznaka($db, $id, $ime_user, $redni_broj, $radno_mjesto);

    if ($result['success']) {
      setEventMessages($result['message'], null, 'mesgs');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    } else {
      setEventMessages($result['error'], null, 'errors');
    }
  }
}

// Get data for edit mode
$editData = null;
if ($action === 'edit' && $id > 0) {
  $result = Interna_oznaka_korisnika_helper::getInternaOznakaDetails($db, $id);
  if ($result['success']) {
    $editData = $result['oznaka'];
  } else {
    setEventMessages($result['error'], null, 'errors');
    $action = '';
  }
}

// Get all oznake
$result = Interna_oznaka_korisnika_helper::getAllInterneOznake($db, $ID_ustanove);
$oznake = $result['success'] ? $result['oznake'] : [];

// Get statistics
$statsResult = Interna_oznaka_korisnika_helper::getStatistics($db, $ID_ustanove);
$stats = $statsResult['success'] ? $statsResult['stats'] : [];

// === PAGE HEADER ===
$morehead = '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/custom/seup/css/korisnici.css">';
$morehead .= '<script type="text/javascript" src="' . DOL_URL_ROOT . '/custom/seup/js/korisnici.js"></script>';
llxHeader($morehead, 'Popis zaposlenika - Interne Oznake', '');

// Page header
print load_fiche_titre('Popis zaposlenika - Interne Oznake Korisnika', '', 'user');

// Statistics Cards
if (!empty($stats)) {
  print '<div class="korisnici-stats-container" style="display: flex; gap: 20px; margin-bottom: 30px;">';

  print '<div class="korisnici-stat-card" style="flex: 1; background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #0084d4;">';
  print '<div style="font-size: 14px; color: #666; margin-bottom: 5px;">Ukupno Korisnika</div>';
  print '<div style="font-size: 32px; font-weight: bold; color: #0084d4;">' . $stats['total'] . '</div>';
  print '</div>';

  print '<div class="korisnici-stat-card" style="flex: 1; background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745;">';
  print '<div style="font-size: 14px; color: #666; margin-bottom: 5px;">S Radnim Mjestom</div>';
  print '<div style="font-size: 32px; font-weight: bold; color: #28a745;">' . $stats['with_radno_mjesto'] . '</div>';
  print '</div>';

  if ($stats['total'] > 0) {
    print '<div class="korisnici-stat-card" style="flex: 1; background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #ffc107;">';
    print '<div style="font-size: 14px; color: #666; margin-bottom: 5px;">Raspon RBR</div>';
    print '<div style="font-size: 32px; font-weight: bold; color: #ffc107;">' . sprintf('%02d', $stats['min_rbr']) . ' - ' . sprintf('%02d', $stats['max_rbr']) . '</div>';
    print '</div>';
  }

  print '</div>';
}

// Action buttons
print '<div class="korisnici-actions" style="margin-bottom: 20px;">';
if ($action !== 'add' && $action !== 'edit') {
  if ($user->rights->seup->korisnici->write) {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=add" class="butAction">Nova interna oznaka</a>';
  }
  if ($stats['total'] > 0) {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=export_csv" class="butAction" style="margin-left: 10px;">Izvezi u CSV</a>';
  }
}
print '</div>';

// Confirmation dialog for delete
if ($action === 'delete' && $id > 0) {
  if (!$user->rights->seup->korisnici->delete) {
    accessforbidden();
  }

  $formconfirm = $form->formconfirm(
    $_SERVER['PHP_SELF'] . '?id=' . $id . '&action=confirm_delete',
    'Brisanje Interne Oznake Zaposlenika',
    'Jeste li sigurni da želite obrisati ovu internu oznaku zaposlenika?',
    'confirm_delete',
    '',
    0,
    1
  );
  print $formconfirm;
}

// === ADD/EDIT FORM ===
if ($action === 'add' || $action === 'edit') {
  if (!$user->rights->seup->korisnici->write) {
    accessforbidden();
  }

  print '<div class="korisnici-form-container" style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 800px;">';

  print '<h3>' . ($action === 'add' ? 'Nova interna oznaka' : 'Uredi oznaku zaposlenika') . '</h3>';

  print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . ($action === 'edit' ? '?id=' . $id : '') . '" class="korisnici-form">';
  print '<input type="hidden" name="token" value="' . newToken() . '">';
  print '<input type="hidden" name="action" value="' . ($action === 'edit' ? 'update' : $action) . '">';

  // Ime i prezime field with datalist (Dolibarr users)
  print '<div class="form-group" style="margin-bottom: 20px;">';
  print '<label for="ime_user" style="display: block; margin-bottom: 8px; font-weight: 500;">Ime i Prezime *</label>';
  print '<input type="text"
          id="ime_user"
          name="ime_user"
          class="flat minwidth300"
          list="user-list-korisnici"
          value="' . ($editData ? htmlspecialchars($editData->ime_prezime) : '') . '"
          required
          autocomplete="off"
          placeholder="Odaberite korisnika iz popisa..."
          style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; width: 100%;">';
  print '<datalist id="user-list-korisnici">';
  foreach ($listUsers as $u) {
    print '<option value="' . htmlspecialchars($u->getFullName($langs)) . '"></option>';
  }
  print '</datalist>';
  print '</div>';

  // Redni broj field
  print '<div class="form-group" style="margin-bottom: 20px;">';
  print '<label for="redni_broj" style="display: block; margin-bottom: 8px; font-weight: 500;">Interna oznaka *</label>';
  print '<input type="number"
          id="redni_broj"
          name="redni_broj"
          class="flat"
          value="' . ($editData ? (int)$editData->rbr : '') . '"
          min="0"
          max="99"
          required
          placeholder="npr. 15"
          style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; width: 200px;">';
  print '<small style="display: block; margin-top: 5px; color: #666;">Vrijednosti moraju biti između 0 i 99</small>';
  print '</div>';

  // Radno mjesto field
  print '<div class="form-group" style="margin-bottom: 20px;">';
  print '<label for="radno_mjesto" style="display: block; margin-bottom: 8px; font-weight: 500;">Radno Mjesto *</label>';
  print '<input type="text"
          id="radno_mjesto"
          name="radno_mjesto"
          class="flat minwidth400"
          value="' . ($editData ? htmlspecialchars($editData->naziv) : '') . '"
          required
          placeholder="npr. Voditelj odjela"
          style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; width: 100%;">';
  print '</div>';

  // Form buttons
  print '<div class="form-actions" style="margin-top: 30px; display: flex; gap: 10px;">';
  print '<button type="submit" class="button" style="padding: 10px 20px; background: #0084d4; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 500;">';
  print $action === 'add' ? 'Dodaj Korisnika' : 'Spremi Izmjene';
  print '</button>';
  print '<a href="' . $_SERVER['PHP_SELF'] . '" class="button button-cancel" style="padding: 10px 20px; background: #f0f0f0; color: #333; text-decoration: none; border-radius: 4px; display: inline-block; font-weight: 500;">Odustani</a>';
  print '</div>';

  print '</form>';
  print '</div>';
}

// === LIST VIEW ===
if ($action !== 'add' && $action !== 'edit') {
  print '<div class="korisnici-list-container" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';

  if (empty($oznake)) {
    print '<div class="opacitymedium" style="text-align: center; padding: 40px;">Nema unesenih zaposlenika. Kliknite "Dodaj Novog Zaposlenika" za unos prvog zaposlenika.</div>';
  } else {
    print '<table class="noborder centpercent korisnici-table">';
    print '<thead>';
    print '<tr class="liste_titre">';
    print '<th style="padding: 12px;">Rb.</th>';
    print '<th style="padding: 12px;">Ime i Prezime</th>';
    print '<th style="padding: 12px; text-align: center;">Interna oznaka</th>';
    print '<th style="padding: 12px;">Radno Mjesto</th>';
    print '<th style="padding: 12px; text-align: right; width: 150px;">Akcije</th>';
    print '</tr>';
    print '</thead>';
    print '<tbody>';

    $i = 0;
    foreach ($oznake as $oznaka) {
      $i++;
      print '<tr class="oddeven" style="' . ($i % 2 === 0 ? 'background: #f9f9f9;' : '') . '">';

      print '<td style="padding: 12px;">' . $i . '</td>';
      print '<td style="padding: 12px; font-weight: 500;">' . htmlspecialchars($oznaka->ime_prezime) . '</td>';
      print '<td style="padding: 12px; text-align: center; font-family: monospace; background: #f0f7ff; font-weight: 600; color: #0084d4;">' . sprintf('%02d', $oznaka->rbr) . '</td>';
      print '<td style="padding: 12px;">' . htmlspecialchars($oznaka->naziv) . '</td>';

      print '<td style="padding: 12px; text-align: right;">';

      if ($user->rights->seup->korisnici->write) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=edit&id=' . $oznaka->ID . '" class="editfielda" title="Uredi" style="margin-right: 10px;">';
        print img_edit();
        print '</a>';
      }

      if ($user->rights->seup->korisnici->delete) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=delete&id=' . $oznaka->ID . '" class="deletefielda" title="Obriši">';
        print img_delete();
        print '</a>';
      }

      print '</td>';

      print '</tr>';
    }

    print '</tbody>';
    print '</table>';
  }

  print '</div>';
}

// Page footer
print '<style>
  .korisnici-form input:focus {
    outline: none;
    border-color: #0084d4;
    box-shadow: 0 0 0 3px rgba(0, 132, 212, 0.1);
  }
  .korisnici-form .button:hover {
    opacity: 0.9;
    transform: translateY(-1px);
    transition: all 0.2s;
  }
  .korisnici-table tr:hover {
    background: #f5f5f5 !important;
  }
  .korisnici-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: all 0.2s;
  }
</style>';

llxFooter();
$db->close();
