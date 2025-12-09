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
 * U skladu sa Zakonom o autorskom pravu i srodnim pravima 
 * (NN 167/03, 79/07, 80/11, 125/17), a osobito člancima 32. (pravo na umnožavanje), 35. 
 * (pravo na preradu i distribuciju) i 76. (kaznene odredbe), 
 * svako neovlašteno umnožavanje ili prerada ovog softvera smatra se prekršajem. 
 * Prema Kaznenom zakonu (NN 125/11, 144/12, 56/15), članak 228., stavak 1., 
 * prekršitelj se može kazniti novčanom kaznom ili zatvorom do jedne godine, 
 * a sud može izreći i dodatne mjere oduzimanja protivpravne imovinske koristi.
 * Bilo kakve izmjene, prijevodi, integracije ili dijeljenje koda bez izričitog pismenog 
 * odobrenja autora smatraju se kršenjem ugovora i zakona te će se pravno sankcionirati. 
 * Za sva pitanja, zahtjeve za licenciranjem ili dodatne informacije obratite se na info@8core.hr.
 */
/**
 *    \file       seup/pages/postavke.php
 *    \ingroup    seup
 *    \brief      Postavke SEUP sustava
 */

// === Dolibarr bootstrap (bez filozofije) ===
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
if (!$res) {
  $tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
  $tmp2 = realpath(__FILE__);
  $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
  while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
  if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
  if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
  if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
  if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
  if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
}
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

// Učitaj prijevode
$langs->loadLangs(array("seup@seup"));

// Klase modula
require_once __DIR__ . '/../class/klasifikacijska_oznaka.class.php';
require_once __DIR__ . '/../class/oznaka_ustanove.class.php';
require_once __DIR__ . '/../class/interna_oznaka_korisnika.class.php';
require_once __DIR__ . '/../class/interna_oznaka_korisnika_helper.class.php';
require_once __DIR__ . '/../class/changelog_sistem.class.php';

// === Helpers ===
function seup_db_prefix($db) {
  if (method_exists($db, 'prefix')) return $db->prefix();
  if (defined('MAIN_DB_PREFIX')) return MAIN_DB_PREFIX;
  return '';
}
$TABLE_POS = seup_db_prefix($db) . 'a_posiljatelji';

// === Create-if-not-exists tablica za Treće Osobe (a_posiljatelji) ===
$db->query("CREATE TABLE IF NOT EXISTS `".$db->escape($TABLE_POS)."`(
  `rowid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `naziv` VARCHAR(255) NOT NULL,
  `adresa` VARCHAR(255) DEFAULT NULL,
  `oib` VARCHAR(32) DEFAULT NULL,
  `telefon` VARCHAR(64) DEFAULT NULL,
  `kontakt_osoba` VARCHAR(255) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `datec` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tms` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY(`rowid`),
  UNIQUE KEY `uq_oib` (`oib`)
) ENGINE=InnoDB");

// === Create-if-not-exists tablica za Vrste Arhivskog Gradiva (a_arhivska_gradiva) ===
$TABLE_ARH = seup_db_prefix($db) . 'a_arhivska_gradiva';
$db->query("CREATE TABLE IF NOT EXISTS `".$db->escape($TABLE_ARH)."`(
  `rowid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `oznaka` VARCHAR(100) NOT NULL,
  `vrsta_gradiva` VARCHAR(255) NOT NULL,
  `opisi_napomene` TEXT DEFAULT NULL,
  `datec` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tms` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY(`rowid`),
  UNIQUE KEY `uq_oznaka` (`oznaka`)
) ENGINE=InnoDB");

// === Osn. varijable ===
$action = GETPOST('action', 'aZ09');
$form = new Form($db);
$formfile = new FormFile($db);
$now = dol_now();
ob_start();

// Sigurnost thirdparty ograničenja
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) { $action=''; $socid=$user->socid; }

// Header i assets
llxHeader("", "SEUP - Postavke", '', '', 0, 0, '', '', '', 'mod-seup page-postavke');
print '<meta name="viewport" content="width=device-width, initial-scale=1">';
print '<link rel="preconnect" href="https://fonts.googleapis.com">';
print '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
print '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
print '<link href="/custom/seup/css/seup-modern.css" rel="stylesheet">';
print '<script src="' . DOL_URL_ROOT . '/custom/seup/js/messages.js"></script>';

// === Data: Oznaka ustanove (load) ===
$podaci_postoje = null;
$sql = "SELECT ID_ustanove, singleton, code_ustanova, name_ustanova FROM " . MAIN_DB_PREFIX . "a_oznaka_ustanove WHERE singleton = 1 LIMIT 1";
$resql = $db->query($sql);
$ID_ustanove = 0;
if ($resql && $db->num_rows($resql) > 0) {
  $podaci_postoje = $db->fetch_object($resql);
  $ID_ustanove = $podaci_postoje->ID_ustanove;
}

// === Data: svi aktivni korisnici (za interne oznake) ===
$listUsers = [];
$userStatic = new User($db);
$resql = $db->query("SELECT rowid FROM " . MAIN_DB_PREFIX . "user WHERE statut = 1 ORDER BY lastname ASC");
if ($resql) { while ($o = $db->fetch_object($resql)) { $userStatic->fetch($o->rowid); $listUsers[] = clone $userStatic; } }

/*************************************
 *  POST SUBMIT HANDLERS
 *************************************/

// 1) INTERNA OZNAKA KORISNIKA (ADD/UPDATE je već u tvojem kodu – ostavljeno)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // 1.a Add interna oznaka
  if (isset($_POST['action_oznaka']) && $_POST['action_oznaka'] === 'add') {
    $ime_user = GETPOST('ime_user', 'alphanohtml');
    $redni_broj = GETPOST('redni_broj', 'int');
    $radno_mjesto = GETPOST('radno_mjesto_korisnika', 'alphanohtml');

    $result = Interna_oznaka_korisnika_helper::addInternaOznaka($db, $ID_ustanove, $ime_user, $redni_broj, $radno_mjesto);

    if ($result['success']) {
      setEventMessages($result['message'], null, 'mesgs');
    } else {
      setEventMessages($result['error'], null, 'errors');
    }
  }

  // 1.b UPDATE interna oznaka
  if (isset($_POST['action_oznaka']) && $_POST['action_oznaka'] === 'update') {
    $id_oznake = GETPOST('id_oznake', 'int');
    $ime_user = GETPOST('ime_user_edit', 'alphanohtml');
    $rbr = GETPOST('redni_broj_edit', 'int');
    $naziv = GETPOST('radno_mjesto_korisnika_edit', 'alphanohtml');

    $result = Interna_oznaka_korisnika_helper::updateInternaOznaka($db, $id_oznake, $ime_user, $rbr, $naziv);

    if ($result['success']) {
      setEventMessages($result['message'], null, 'mesgs');
    } else {
      setEventMessages($result['error'], null, 'errors');
    }
  }

  // 1.c DELETE interna oznaka
  if (isset($_POST['action_oznaka']) && $_POST['action_oznaka'] === 'delete') {
    $id_oznake = GETPOST('id_oznake', 'int');

    $result = Interna_oznaka_korisnika_helper::deleteInternaOznaka($db, $id_oznake);

    if ($result['success']) {
      setEventMessages($result['message'], null, 'mesgs');
    } else {
      setEventMessages($result['error'], null, 'errors');
    }
  }

  // 1.d AJAX autocomplete za postojece unose (min 3 slova)
  if (isset($_POST['action_oznaka']) && $_POST['action_oznaka'] === 'autocomplete_postojeci') {
    header('Content-Type: application/json; charset=UTF-8');
    if (function_exists('ob_get_level') && ob_get_level() > 0) { ob_end_clean(); }

    $search = GETPOST('search', 'alphanohtml');
    $result = Interna_oznaka_korisnika_helper::searchInterneOznake($db, $search);

    echo json_encode($result);
    exit;
  }

  // 1.e AJAX endpoint za dohvat podataka internih oznaka korisnika (OLD - keep for compatibility)
  if (isset($_POST['action_oznaka']) && $_POST['action_oznaka'] === 'get_user_data') {
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

  // 2) OZNAKA USTANOVE (AJAX JSON kao u tvom kodu)
  if (isset($_POST['action_ustanova'])) {
    header('Content-Type: application/json; charset=UTF-8');
    if (function_exists('ob_get_level') && ob_get_level() > 0) { ob_end_clean(); }

    $oznaka_ustanove = new Oznaka_ustanove();
    try {
      $db->begin();
      if ($podaci_postoje) $oznaka_ustanove->setID_oznaka_ustanove($podaci_postoje->singleton);
      $oznaka_ustanove->setOznaka_ustanove(GETPOST('code_ustanova', 'alphanohtml'));
      if (!preg_match('/^\d{4}-\d-\d$/', $oznaka_ustanove->getOznaka_ustanove())) throw new Exception($langs->trans("Neispravan format Oznake Ustanove"));
      $oznaka_ustanove->setNaziv_ustanove(GETPOST('name_ustanova', 'alphanohtml'));
      $act = GETPOST('action_ustanova', 'alpha');
      if ($act === 'add' && !$podaci_postoje) {
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "a_oznaka_ustanove (code_ustanova, name_ustanova) VALUES ('".$db->escape($oznaka_ustanove->getOznaka_ustanove())."','".$db->escape($oznaka_ustanove->getNaziv_ustanove())."')";
      } else {
        if (!is_object($podaci_postoje) || empty($podaci_postoje->singleton)) throw new Exception($langs->trans('RecordNotFound'));
        $oznaka_ustanove->setID_oznaka_ustanove($podaci_postoje->singleton);
        $sql = "UPDATE " . MAIN_DB_PREFIX . "a_oznaka_ustanove SET code_ustanova='".$db->escape($oznaka_ustanove->getOznaka_ustanove())."', name_ustanova='".$db->escape($oznaka_ustanove->getNaziv_ustanove())."' WHERE ID_ustanove='".$db->escape($oznaka_ustanove->getID_oznaka_ustanove())."'";
      }
      if (!$db->query($sql)) throw new Exception($db->lasterror());
      $db->commit();
      echo json_encode(['success'=>true,'message'=>$langs->trans($act==='add'?'Oznaka Ustanove Uspjesno dodana':'Oznaka Ustanove uspjesno azurirana'),'data'=>['code_ustanova'=>$oznaka_ustanove->getOznaka_ustanove(),'name_ustanova'=>$oznaka_ustanove->getNaziv_ustanove()]]); exit;
    } catch (Exception $e) {
      $db->rollback(); http_response_code(500); echo json_encode(['success'=>false,'error'=>$e->getMessage()]); exit;
    }
  }

  // 3) KLASIFIKACIJSKA OZNAKA (ostavljeno prema tvom kodu – skraćeno, bez izmjena logike)
  if (isset($_POST['action_klasifikacija'])) {
    $klasifikacijska_oznaka = new Klasifikacijska_oznaka();
    $klasifikacijska_oznaka->setKlasa_br(GETPOST('klasa_br', 'int'));
    if (!preg_match('/^\d{3}$/', (string)$klasifikacijska_oznaka->getKlasa_br())) { setEventMessages($langs->trans("ErrorKlasaBrFormat"), null, 'errors'); $error++; }
    $klasifikacijska_oznaka->setSadrzaj(GETPOST('sadrzaj', 'int'));
    if (!preg_match('/^\d{2}$/', (string)$klasifikacijska_oznaka->getSadrzaj()) || $klasifikacijska_oznaka->getSadrzaj() > 99) { setEventMessages($langs->trans("ErrorSadrzajFormat"), null, 'errors'); $error++; }
    $klasifikacijska_oznaka->setDosjeBroj(GETPOST('dosje_br', 'int'));
    if (!preg_match('/^\d{2}$/', (string)$klasifikacijska_oznaka->getDosjeBroj()) || $klasifikacijska_oznaka->getDosjeBroj() > 50) { setEventMessages($langs->trans("ErrorDosjeBrojFormat"), null, 'errors'); $error++; }
    $klasifikacijska_oznaka->setVrijemeCuvanja($klasifikacijska_oznaka->CastVrijemeCuvanjaToInt(GETPOST('vrijeme_cuvanja', 'int')));
    if (!preg_match('/^\d{1,2}$/', (string)$klasifikacijska_oznaka->getVrijemeCuvanja()) || $klasifikacijska_oznaka->getVrijemeCuvanja() > 10) { setEventMessages($langs->trans("ErrorVrijemeCuvanjaFormat"), null, 'errors'); $error++; }
    $klasifikacijska_oznaka->setOpisKlasifikacijskeOznake(GETPOST('opis_klasifikacije', 'alphanohtml'));

    if ($_POST['action_klasifikacija'] === 'add') {
      $klasa_br = $db->escape($klasifikacijska_oznaka->getKlasa_br()); $sadrzaj=$db->escape($klasifikacijska_oznaka->getSadrzaj()); $dosje_br=$db->escape($klasifikacijska_oznaka->getDosjeBroj());
      $rez = $db->query("SELECT ID_klasifikacijske_oznake FROM " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka WHERE klasa_broj='$klasa_br' AND sadrzaj='$sadrzaj' AND dosje_broj='$dosje_br'");
      if ($rez && $db->num_rows($rez)>0) { setEventMessages($langs->trans("KombinacijaKlaseSadrzajaDosjeaVecPostoji"), null, 'errors'); $error++; }
      else {
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka (ID_ustanove, klasa_broj, sadrzaj, dosje_broj, vrijeme_cuvanja, opis_klasifikacijske_oznake) VALUES ("
            . (int)$ID_ustanove . ", '".$db->escape($klasifikacijska_oznaka->getKlasa_br())."','".$db->escape($klasifikacijska_oznaka->getSadrzaj())."','".$db->escape($klasifikacijska_oznaka->getDosjeBroj())."','".$db->escape($klasifikacijska_oznaka->getVrijemeCuvanja())."','".$db->escape($klasifikacijska_oznaka->getOpisKlasifikacijskeOznake())."')";
        if (!$db->query($sql)) setEventMessages($langs->trans("ErrorDatabase") . ": " . $db->lasterror(), null, 'errors'); else setEventMessages($langs->trans("Uspjesno pohranjena klasifikacijska oznaka"), null, 'mesgs');
      }
    } elseif ($_POST['action_klasifikacija'] === 'delete') {
      $id_oznake = GETPOST('id_klasifikacijske_oznake', 'int');
      if (!$id_oznake) setEventMessages($langs->trans("ErrorMissingRecordID"), null, 'errors');
      else {
        $db->begin();
        $ok=$db->query("DELETE FROM " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka WHERE ID_klasifikacijske_oznake=".(int)$id_oznake);
        if ($ok) { $db->commit(); setEventMessages($langs->trans("KlasifikacijskaOznakaUspjesnoObrisana"), null, 'mesgs'); header('Location: '.$_SERVER['PHP_SELF']); exit; }
        else { $db->rollback(); setEventMessages($langs->trans("ErrorDeleteFailed") . ": " . $db->lasterror(), null, 'errors'); }
      }
    }
  }

  // 4) TREĆE OSOBE – NOVI HANDLER (a_posiljatelji, hard delete, + email)
  if (isset($_POST['action_treca_osoba'])) {
    $act = GETPOST('action_treca_osoba','alpha'); // add|update|delete
    $rowid = (int) GETPOST('rowid','int');
    $naziv = trim(GETPOST('to_naziv','restricthtml'));
    $adresa = trim(GETPOST('to_adresa','restricthtml'));
    $oib = trim(GETPOST('to_oib','alphanohtml'));
    $telefon = trim(GETPOST('to_telefon','alphanohtml'));
    $kontakt_osoba = trim(GETPOST('to_kontakt_osoba','restricthtml'));
    $email = trim(GETPOST('to_email','alphanohtml'));

    // Validacije
    $errs = array();
    if ($act==='add' || $act==='update') {
      if ($naziv==='') $errs[] = "Naziv je obavezan.";
      if ($email!=='' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errs[] = "E-mail nije valjan.";
    }
    if (empty($errs)) {
      if ($act==='add') {
        $db->begin();
        if ($oib!=='') {
          $chk = $db->query("SELECT rowid FROM `".$db->escape($TABLE_POS)."` WHERE oib='".$db->escape($oib)."' LIMIT 1");
          if ($chk && $db->num_rows($chk)>0) { $db->rollback(); setEventMessages("Postoji zapis s istim OIB-om.", null, 'errors'); }
          else {
            $sql = "INSERT INTO `".$db->escape($TABLE_POS)."` (naziv,adresa,oib,telefon,kontakt_osoba,email,datec) VALUES ('".$db->escape($naziv)."','".$db->escape($adresa)."',".($oib!==''?"'".$db->escape($oib)."'":"NULL").",'".$db->escape($telefon)."','".$db->escape($kontakt_osoba)."',".($email!==''?"'".$db->escape($email)."'":"NULL").",NOW())";
            $ok = $db->query($sql);
            if ($ok) { $db->commit(); header("Location: ".$_SERVER['PHP_SELF']."?tab=trece_osobe&msg=created"); exit; }
            else { $db->rollback(); setEventMessages("Greška pri spremanju.", null, 'errors'); }
          }
        } else {
          $sql = "INSERT INTO `".$db->escape($TABLE_POS)."` (naziv,adresa,oib,telefon,kontakt_osoba,email,datec) VALUES ('".$db->escape($naziv)."','".$db->escape($adresa)."',NULL,'".$db->escape($telefon)."','".$db->escape($kontakt_osoba)."',".($email!==''?"'".$db->escape($email)."'":"NULL").",NOW())";
          $ok = $db->query($sql);
          if ($ok) { $db->commit(); header("Location: ".$_SERVER['PHP_SELF']."?tab=trece_osobe&msg=created"); exit; }
          else { $db->rollback(); setEventMessages("Greška pri spremanju.", null, 'errors'); }
        }
      } elseif ($act==='update') {
        if ($rowid<=0) setEventMessages("Nedostaje ID zapisa.", null, 'errors');
        else {
          $db->begin();
          if ($oib!=='') {
            $chk = $db->query("SELECT rowid FROM `".$db->escape($TABLE_POS)."` WHERE oib='".$db->escape($oib)."' AND rowid!=".(int)$rowid." LIMIT 1");
            if ($chk && $db->num_rows($chk)>0) { $db->rollback(); setEventMessages("OIB već postoji na drugom zapisu.", null, 'errors'); $rowid=0; }
          }
          if ($rowid>0) {
            $sql = "UPDATE `".$db->escape($TABLE_POS)."` SET
                    naziv='".$db->escape($naziv)."',
                    adresa='".$db->escape($adresa)."',
                    oib=".($oib!==''?"'".$db->escape($oib)."'":"NULL").",
                    telefon='".$db->escape($telefon)."',
                    kontakt_osoba='".$db->escape($kontakt_osoba)."',
                    email=".($email!==''?"'".$db->escape($email)."'":"NULL")."
                    WHERE rowid=".(int)$rowid." LIMIT 1";
            $ok = $db->query($sql);
            if ($ok) { $db->commit(); header("Location: ".$_SERVER['PHP_SELF']."?tab=trece_osobe&msg=updated"); exit; }
            else { $db->rollback(); setEventMessages("Greška pri ažuriranju.", null, 'errors'); }
          }
        }
      } elseif ($act==='delete') {
        $id = (int) GETPOST('id','int');
        if ($id>0) {
          $db->begin();
          $ok = $db->query("DELETE FROM `".$db->escape($TABLE_POS)."` WHERE rowid=".$id." LIMIT 1");
          if ($ok) { $db->commit(); header("Location: ".$_SERVER['PHP_SELF']."?tab=trece_osobe&msg=deleted"); exit; }
          else { $db->rollback(); setEventMessages("Brisanje nije uspjelo.", null, 'errors'); }
        } else setEventMessages("Nedostaje ID za brisanje.", null, 'errors');
      }
    } else {
      setEventMessages(implode(' ',$errs), null, 'errors');
    }
  }

  // 5) VRSTE ARHIVSKOG GRADIVA – NOVI HANDLER
  if (isset($_POST['action_arhivska_gradiva'])) {
    $act = GETPOST('action_arhivska_gradiva','alpha'); // add|update|delete
    $rowid = (int) GETPOST('rowid','int');
    $oznaka = trim(GETPOST('ag_oznaka','alphanohtml'));
    $vrsta_gradiva = trim(GETPOST('ag_vrsta_gradiva','restricthtml'));
    $opisi_napomene = trim(GETPOST('ag_opisi_napomene','restricthtml'));

    // Validacije
    $errs = array();
    if ($act==='add' || $act==='update') {
      if ($oznaka==='') $errs[] = "Oznaka je obavezna.";
      if ($vrsta_gradiva==='') $errs[] = "Vrsta gradiva je obavezna.";
    }
    if (empty($errs)) {
      if ($act==='add') {
        $db->begin();
        $chk = $db->query("SELECT rowid FROM `".$db->escape($TABLE_ARH)."` WHERE oznaka='".$db->escape($oznaka)."' LIMIT 1");
        if ($chk && $db->num_rows($chk)>0) { $db->rollback(); setEventMessages("Postoji zapis s istom oznakom.", null, 'errors'); }
        else {
          $sql = "INSERT INTO `".$db->escape($TABLE_ARH)."` (oznaka,vrsta_gradiva,opisi_napomene,datec) VALUES ('".$db->escape($oznaka)."','".$db->escape($vrsta_gradiva)."',".($opisi_napomene!==''?"'".$db->escape($opisi_napomene)."'":"NULL").",NOW())";
          $ok = $db->query($sql);
          if ($ok) { $db->commit(); header("Location: ".$_SERVER['PHP_SELF']."?tab=arhivska_gradiva&msg=created"); exit; }
          else { $db->rollback(); setEventMessages("Greška pri spremanju.", null, 'errors'); }
        }
      } elseif ($act==='update') {
        if ($rowid<=0) setEventMessages("Nedostaje ID zapisa.", null, 'errors');
        else {
          $db->begin();
          $chk = $db->query("SELECT rowid FROM `".$db->escape($TABLE_ARH)."` WHERE oznaka='".$db->escape($oznaka)."' AND rowid!=".(int)$rowid." LIMIT 1");
          if ($chk && $db->num_rows($chk)>0) { $db->rollback(); setEventMessages("Oznaka već postoji na drugom zapisu.", null, 'errors'); $rowid=0; }
          if ($rowid>0) {
            $sql = "UPDATE `".$db->escape($TABLE_ARH)."` SET
                    oznaka='".$db->escape($oznaka)."',
                    vrsta_gradiva='".$db->escape($vrsta_gradiva)."',
                    opisi_napomene=".($opisi_napomene!==''?"'".$db->escape($opisi_napomene)."'":"NULL")."
                    WHERE rowid=".(int)$rowid." LIMIT 1";
            $ok = $db->query($sql);
            if ($ok) { $db->commit(); header("Location: ".$_SERVER['PHP_SELF']."?tab=arhivska_gradiva&msg=updated"); exit; }
            else { $db->rollback(); setEventMessages("Greška pri ažuriranju.", null, 'errors'); }
          }
        }
      } elseif ($act==='delete') {
        $id = (int) GETPOST('id','int');
        if ($id>0) {
          $db->begin();
          $ok = $db->query("DELETE FROM `".$db->escape($TABLE_ARH)."` WHERE rowid=".$id." LIMIT 1");
          if ($ok) { $db->commit(); header("Location: ".$_SERVER['PHP_SELF']."?tab=arhivska_gradiva&msg=deleted"); exit; }
          else { $db->rollback(); setEventMessages("Brisanje nije uspjelo.", null, 'errors'); }
        } else setEventMessages("Nedostaje ID za brisanje.", null, 'errors');
      }
    } else {
      setEventMessages(implode(' ',$errs), null, 'errors');
    }
  }
}

// Flash poruke – veži na aktivni tab
$tab   = GETPOST('tab','alphanohtml');
$flash = GETPOST('msg','alphanohtml');

if ($tab === 'trece_osobe') {
    if ($flash === 'created') setEventMessages('Zapis je dodan.', null, 'mesgs');
    elseif ($flash === 'updated') setEventMessages('Zapis je ažuriran.', null, 'mesgs');
    elseif ($flash === 'deleted') setEventMessages('Zapis je obrisan.', null, 'mesgs');
}

if ($tab === 'arhivska_gradiva') {
    if ($flash === 'created') setEventMessages('Vrsta arhivskog gradiva je dodana.', null, 'mesgs');
    elseif ($flash === 'updated') setEventMessages('Vrsta arhivskog gradiva je ažurirana.', null, 'mesgs');
    elseif ($flash === 'deleted') setEventMessages('Vrsta arhivskog gradiva je obrisana.', null, 'mesgs');
}
// === UI ===
print '<main class="seup-settings-hero">';
print '<div class="seup-floating-elements">'; for ($i=1;$i<=5;$i++) print '<div class="seup-floating-element"></div>'; print '</div>';
print '<div class="seup-settings-content">';
print '<div class="seup-settings-header">';
print '<h1 class="seup-settings-title">Postavke Sustava</h1>';
print '<p class="seup-settings-subtitle">Konfigurirajte osnovne parametre, korisničke oznake i klasifikacijski sustav</p>';
print '</div>';

// Settings Cards Grid - 4 columns, 2 rows
print '<div class="seup-settings-cards-grid">';

// Card 1: Klasifikacijske oznake
print '<div class="seup-settings-card-trigger" data-modal="klasifikacijskeOznakeModal">';
print '<div class="seup-card-icon"><i class="fas fa-sitemap"></i></div>';
print '<div class="seup-card-content">';
print '<h3 class="seup-card-title">Klasifikacijske Oznake</h3>';
print '<p class="seup-card-description">Upravljanje sustavom klasifikacije dokumentacije</p>';
print '</div>';
print '<i class="fas fa-chevron-right seup-card-arrow"></i>';
print '</div>';

// Card 2: Interne oznake korisnika
print '<div class="seup-settings-card-trigger" data-modal="interneOznakeModal">';
print '<div class="seup-card-icon"><i class="fas fa-users"></i></div>';
print '<div class="seup-card-content">';
print '<h3 class="seup-card-title">Interne Oznake Korisnika</h3>';
print '<p class="seup-card-description">Upravljanje korisničkim oznakama i radnim mjestima</p>';
print '</div>';
print '<i class="fas fa-chevron-right seup-card-arrow"></i>';
print '</div>';

// Card 3: Oznaka ustanove
print '<div class="seup-settings-card-trigger" data-modal="oznakaUstanoveModal">';
print '<div class="seup-card-icon"><i class="fas fa-building"></i></div>';
print '<div class="seup-card-content">';
print '<h3 class="seup-card-title">Oznaka Ustanove</h3>';
print '<p class="seup-card-description">Osnovni podaci o ustanovi i identifikacijska oznaka</p>';
print '</div>';
print '<i class="fas fa-chevron-right seup-card-arrow"></i>';
print '</div>';

// Card 4: Treće Osobe
print '<div class="seup-settings-card-trigger" data-modal="treceOsobeModal">';
print '<div class="seup-card-icon"><i class="fas fa-handshake"></i></div>';
print '<div class="seup-card-content">';
print '<h3 class="seup-card-title">Treće Osobe</h3>';
print '<p class="seup-card-description">Suradnici i vanjski partneri</p>';
print '</div>';
print '<i class="fas fa-chevron-right seup-card-arrow"></i>';
print '</div>';

// Card 5: Vrste Arhivskog Gradiva
print '<div class="seup-settings-card-trigger" data-modal="arhivskaGradivaModal">';
print '<div class="seup-card-icon"><i class="fas fa-archive"></i></div>';
print '<div class="seup-card-content">';
print '<h3 class="seup-card-title">Vrste Arhivskog Gradiva</h3>';
print '<p class="seup-card-description">Upravljanje vrstama arhivskog gradiva</p>';
print '</div>';
print '<i class="fas fa-chevron-right seup-card-arrow"></i>';
print '</div>';

// Card 6: Zaposlenici (Admin)
print '<a href="' . DOL_URL_ROOT . '/user/list.php" class="seup-settings-card-trigger">';
print '<div class="seup-card-icon"><i class="fas fa-users-cog"></i></div>';
print '<div class="seup-card-content">';
print '<h3 class="seup-card-title">Zaposlenici (Admin)</h3>';
print '<p class="seup-card-description">Upravljanje zaposlenicima ustanove</p>';
print '</div>';
print '<i class="fas fa-chevron-right seup-card-arrow"></i>';
print '</a>';

// Card 7: Podaci o Ustanovi (Admin)
print '<a href="' . DOL_URL_ROOT . '/admin/company.php" class="seup-settings-card-trigger">';
print '<div class="seup-card-icon"><i class="fas fa-building-shield"></i></div>';
print '<div class="seup-card-content">';
print '<h3 class="seup-card-title">Podaci o Ustanovi (Admin)</h3>';
print '<p class="seup-card-description">Osnovni podaci pravne osobe</p>';
print '</div>';
print '<i class="fas fa-chevron-right seup-card-arrow"></i>';
print '</a>';

// Card 8: Backup i Izvoz (Admin)
print '<a href="' . DOL_URL_ROOT . '/admin/tools/dolibarr_export.php" class="seup-settings-card-trigger">';
print '<div class="seup-card-icon"><i class="fas fa-download"></i></div>';
print '<div class="seup-card-content">';
print '<h3 class="seup-card-title">Backup i Izvoz (Admin)</h3>';
print '<p class="seup-card-description">Izvoz i sigurnosne kopije podataka</p>';
print '</div>';
print '<i class="fas fa-chevron-right seup-card-arrow"></i>';
print '</a>';

print '</div>'; // seup-settings-cards-grid

// Modal 1: Klasifikacijske oznake
print '<div class="seup-modal" id="klasifikacijskeOznakeModal">';
print '<div class="seup-modal-content seup-modal-large">';
print '<div class="seup-modal-header">';
print '<h5 class="seup-modal-title"><i class="fas fa-sitemap me-2"></i>Klasifikacijske Oznake</h5>';
print '<button type="button" class="seup-modal-close">&times;</button>';
print '</div>';
print '<div class="seup-modal-body">';
print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" class="seup-form">';
print '<input type="hidden" id="hidden_id_klasifikacijske_oznake" name="id_klasifikacijske_oznake" value="">';
print '<div class="seup-form-grid seup-grid-3">';
print '<div class="seup-form-group seup-autocomplete-container"><label class="seup-label">Klasa broj (000)</label><input type="text" id="klasa_br" name="klasa_br" class="seup-input" pattern="\\d{3}" maxlength="3" placeholder="000" autocomplete="off"><div id="autocomplete-results" class="seup-autocomplete-dropdown"></div></div>';
print '<div class="seup-form-group"><label class="seup-label">Sadržaj (00)</label><input type="text" id="sadrzaj" name="sadrzaj" class="seup-input" pattern="\\d{2}" maxlength="2" placeholder="00"></div>';
print '<div class="seup-form-group"><label class="seup-label">Dosje broj</label><select id="dosje_br" name="dosje_br" class="seup-select" required><option value="">Odaberite dosje</option>';
for ($i=1;$i<=50;$i++){ $val=sprintf('%02d',$i); print '<option value="'.$val.'">'.$val.'</option>'; }
print '</select></div></div>';
print '<div class="seup-form-grid"><div class="seup-form-group"><label class="seup-label">Vrijeme čuvanja</label><select id="vrijeme_cuvanja" name="vrijeme_cuvanja" class="seup-select" required><option value="permanent">Trajno</option>';
for ($g=1;$g<=10;$g++) print '<option value="'.$g.'">'.$g.' godina</option>';
print '</select></div><div class="seup-form-group"><label class="seup-label">Opis klasifikacije</label><textarea id="opis_klasifikacije" name="opis_klasifikacije" class="seup-textarea" rows="3"></textarea></div></div>';
print '<div class="seup-form-actions"><button type="submit" name="action_klasifikacija" value="add" class="seup-btn seup-btn-primary"><i class="fas fa-plus"></i> Dodaj</button><button type="submit" name="action_klasifikacija" value="update" class="seup-btn seup-btn-secondary"><i class="fas fa-edit"></i> Ažuriraj</button><button type="submit" name="action_klasifikacija" value="delete" class="seup-btn seup-btn-danger"><i class="fas fa-trash"></i> Obriši</button></div>';
print '</form>';
print '</div>';
print '<div class="seup-modal-footer">';
print '<button type="button" class="seup-btn seup-btn-secondary seup-modal-close">Zatvori</button>';
print '</div>';
print '</div>';
print '</div>';

// Modal 2: Interne oznake korisnika (REDESIGNED with TABS)
print '<div class="seup-modal" id="interneOznakeModal">';
print '<div class="seup-modal-content">';
print '<div class="seup-modal-header">';
print '<h5 class="seup-modal-title"><i class="fas fa-users me-2"></i>Interne Oznake Korisnika</h5>';
print '<button type="button" class="seup-modal-close">&times;</button>';
print '</div>';

// Tab Navigation
print '<div class="seup-tab-navigation">';
print '<button type="button" class="seup-tab-btn active" data-tab="novi-unos"><i class="fas fa-plus-circle"></i> Novi Unos</button>';
print '<button type="button" class="seup-tab-btn" data-tab="uredi-postojeci"><i class="fas fa-edit"></i> Uredi Postojeći</button>';
print '</div>';

print '<div class="seup-modal-body">';

// TAB 1: Novi Unos
print '<div class="seup-tab-content active" id="tab-novi-unos">';
print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" class="seup-form" id="form-novi-unos">';
print '<div class="seup-form-grid"><div class="seup-form-group"><label class="seup-label">Korisnik</label><input type="text" name="ime_user" id="ime_user_novi" class="seup-input" list="user-list" placeholder="Unesite ime korisnika" required autocomplete="off"><datalist id="user-list">';
foreach ($listUsers as $u) { print '<option value="'.htmlspecialchars($u->getFullName($langs)).'"></option>'; }
print '</datalist></div><div class="seup-form-group"><label class="seup-label">Redni broj (0-99)</label><input type="number" name="redni_broj" id="redni_broj_novi" class="seup-input" min="0" max="99" required></div></div>';
print '<div class="seup-form-group"><label class="seup-label">Radno mjesto</label><input type="text" name="radno_mjesto_korisnika" id="radno_mjesto_korisnika_novi" class="seup-input" required></div>';
print '<div class="seup-form-actions"><button type="submit" name="action_oznaka" value="add" class="seup-btn seup-btn-primary"><i class="fas fa-plus"></i> Dodaj Novi Unos</button></div>';
print '</form>';
print '</div>';

// TAB 2: Uredi Postojeći (with autocomplete)
print '<div class="seup-tab-content" id="tab-uredi-postojeci">';
print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" class="seup-form" id="form-uredi-postojeci">';
print '<input type="hidden" name="id_oznake" id="hidden_id_oznake" value="">';
print '<div class="seup-form-grid">';
print '<div class="seup-form-group seup-autocomplete-container-interne">';
print '<label class="seup-label">Pretraži korisnike (min 3 slova)</label>';
print '<input type="text" name="ime_user_edit" id="ime_user_edit" class="seup-input" placeholder="Počnite pisati ime..." autocomplete="off">';
print '<div id="autocomplete-interne-results" class="seup-autocomplete-dropdown"></div>';
print '</div>';
print '<div class="seup-form-group"><label class="seup-label">Redni broj (0-99)</label><input type="number" name="redni_broj_edit" id="redni_broj_edit" class="seup-input" min="0" max="99" required></div>';
print '</div>';
print '<div class="seup-form-group"><label class="seup-label">Radno mjesto</label><input type="text" name="radno_mjesto_korisnika_edit" id="radno_mjesto_korisnika_edit" class="seup-input" required></div>';
print '<div class="seup-form-actions">';
print '<button type="submit" name="action_oznaka" value="update" class="seup-btn seup-btn-secondary" id="btn-update-oznaka" disabled><i class="fas fa-edit"></i> Ažuriraj</button>';
print '<button type="submit" name="action_oznaka" value="delete" class="seup-btn seup-btn-danger" id="btn-delete-oznaka" disabled><i class="fas fa-trash"></i> Obriši</button>';
print '</div>';
print '</form>';
print '</div>';

print '</div>';
print '<div class="seup-modal-footer">';
print '<button type="button" class="seup-btn seup-btn-secondary seup-modal-close">Zatvori</button>';
print '</div>';
print '</div>';
print '</div>';

// Modal 3: Oznaka ustanove
print '<div class="seup-modal" id="oznakaUstanoveModal">';
print '<div class="seup-modal-content">';
print '<div class="seup-modal-header">';
print '<h5 class="seup-modal-title"><i class="fas fa-building me-2"></i>Oznaka Ustanove</h5>';
print '<button type="button" class="seup-modal-close">&times;</button>';
print '</div>';
print '<div class="seup-modal-body">';
print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" id="ustanova-form" class="seup-form">';
print '<input type="hidden" name="action_ustanova" id="form-action" value="'.($podaci_postoje ? 'update' : 'add').'">';
print '<div id="messageDiv" class="seup-alert d-none" role="alert"></div>';
print '<div class="seup-form-grid"><div class="seup-form-group"><label class="seup-label">Oznaka (format: 0000-0-0)</label><input type="text" id="code_ustanova" name="code_ustanova" class="seup-input" pattern="^\\d{4}-\\d-\\d$" placeholder="0000-0-0" required value="'.($podaci_postoje?htmlspecialchars($podaci_postoje->code_ustanova):'').'"></div>';
print '<div class="seup-form-group"><label class="seup-label">Naziv ustanove</label><input type="text" id="name_ustanova" name="name_ustanova" class="seup-input" placeholder="Unesite naziv ustanove" required value="'.($podaci_postoje?htmlspecialchars($podaci_postoje->name_ustanova):'').'"></div></div>';
print '<div class="seup-form-actions"><button type="submit" id="ustanova-submit" class="seup-btn seup-btn-primary"><i class="fas fa-'.($podaci_postoje?'edit':'plus').'"></i> '.($podaci_postoje?'Ažuriraj':'Dodaj').'</button></div>';
print '</form>';
print '</div>';
print '<div class="seup-modal-footer">';
print '<button type="button" class="seup-btn seup-btn-secondary seup-modal-close">Zatvori</button>';
print '</div>';
print '</div>';
print '</div>';

// Modal 4: Treće Osobe
print '<div class="seup-modal" id="treceOsobeModal">';
print '<div class="seup-modal-content seup-modal-large">';
print '<div class="seup-modal-header">';
print '<h5 class="seup-modal-title"><i class="fas fa-handshake me-2"></i>Treće Osobe</h5>';
print '<button type="button" class="seup-modal-close">&times;</button>';
print '</div>';
print '<div class="seup-modal-body">';

// Edit fetch
$E = null; $edit = (int) GETPOST('edit','int');
if ($edit>0) { $res=$db->query("SELECT * FROM `".$db->escape($TABLE_POS)."` WHERE rowid=".$edit." LIMIT 1"); if ($res) $E=$db->fetch_object($res); }
$V = function($x){ return $x?htmlspecialchars($x,ENT_QUOTES,'UTF-8'):''; };

print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" class="seup-form">';
if ($E) print '<input type="hidden" name="rowid" value="'.(int)$E->rowid.'">';
print '<div class="seup-form-grid seup-grid-2">';
print '<div class="seup-form-group"><label class="seup-label">Naziv / Ime i prezime *</label><input type="text" name="to_naziv" class="seup-input" required value="'.$V($E?$E->naziv:'').'"></div>';
print '<div class="seup-form-group"><label class="seup-label">OIB</label><input type="text" name="to_oib" class="seup-input" pattern="\\d{11}" maxlength="11" value="'.$V($E?$E->oib:'').'"></div>';
print '<div class="seup-form-group"><label class="seup-label">Adresa</label><input type="text" name="to_adresa" class="seup-input" value="'.$V($E?$E->adresa:'').'"></div>';
print '<div class="seup-form-group"><label class="seup-label">Kontakt osoba</label><input type="text" name="to_kontakt_osoba" class="seup-input" value="'.$V($E?$E->kontakt_osoba:'').'"></div>';
print '<div class="seup-form-group"><label class="seup-label">Kontakt telefon</label><input type="text" name="to_telefon" class="seup-input" value="'.$V($E?$E->telefon:'').'"></div>';
print '<div class="seup-form-group"><label class="seup-label">E-mail</label><input type="email" name="to_email" class="seup-input" value="'.$V($E?$E->email:'').'"></div>';
print '</div>';
print '<div class="seup-form-actions">';
print '<button type="submit" name="action_treca_osoba" value="'.($E?'update':'add').'" class="seup-btn seup-btn-'.($E?'secondary':'primary').'"><i class="fas fa-'.($E?'edit':'plus').'"></i> '.($E?'Ažuriraj':'Dodaj').'</button>'; 
print ' <button type="reset" class="seup-btn seup-btn-secondary" id="btnPonisti">Poništi</button>';
if ($E) print ' <a class="seup-btn" href="'.$_SERVER['PHP_SELF'].'?tab=trece_osobe">Odustani</a>';
print '</div>';
print '</form>';
print '</div>';
print '<div class="seup-modal-footer">';
print '<button type="button" class="seup-btn seup-btn-secondary seup-modal-close">Zatvori</button>';
print '</div>';
print '</div>';
print '</div>';

// Modal 5: Vrste Arhivskog Gradiva
print '<div class="seup-modal" id="arhivskaGradivaModal">';
print '<div class="seup-modal-content seup-modal-large">';
print '<div class="seup-modal-header">';
print '<h5 class="seup-modal-title"><i class="fas fa-archive me-2"></i>Vrste Arhivskog Gradiva</h5>';
print '<button type="button" class="seup-modal-close">&times;</button>';
print '</div>';
print '<div class="seup-modal-body">';

// Edit fetch za arhivska gradiva
$A = null; $edit_arh = (int) GETPOST('edit_arh','int');
if ($edit_arh>0) { $res=$db->query("SELECT * FROM `".$db->escape($TABLE_ARH)."` WHERE rowid=".$edit_arh." LIMIT 1"); if ($res) $A=$db->fetch_object($res); }

print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" class="seup-form">';
if ($A) print '<input type="hidden" name="rowid" value="'.(int)$A->rowid.'">';
print '<div class="seup-form-grid seup-grid-2">';
print '<div class="seup-form-group"><label class="seup-label">Oznaka *</label><input type="text" name="ag_oznaka" class="seup-input" required value="'.$V($A?$A->oznaka:'').'" placeholder="Unesite oznaku"></div>';
print '<div class="seup-form-group"><label class="seup-label">Vrsta Gradiva *</label><input type="text" name="ag_vrsta_gradiva" class="seup-input" required value="'.$V($A?$A->vrsta_gradiva:'').'" placeholder="Unesite vrstu gradiva"></div>';
print '</div>';
print '<div class="seup-form-group"><label class="seup-label">Opisi/Napomene</label><textarea name="ag_opisi_napomene" class="seup-textarea" rows="4" placeholder="Unesite opise ili napomene...">'.$V($A?$A->opisi_napomene:'').'</textarea></div>';
print '<div class="seup-form-actions">';
print '<button type="submit" name="action_arhivska_gradiva" value="'.($A?'update':'add').'" class="seup-btn seup-btn-'.($A?'secondary':'primary').'"><i class="fas fa-'.($A?'edit':'plus').'"></i> '.($A?'Ažuriraj':'Dodaj').'</button>';
print ' <button type="reset" class="seup-btn seup-btn-secondary" id="btnPonistiArh">Poništi</button>';
if ($A) print ' <a class="seup-btn" href="'.$_SERVER['PHP_SELF'].'?tab=arhivska_gradiva">Odustani</a>';
print '</div>';
print '</form>';
print '</div>';
print '<div class="seup-modal-footer">';
print '<button type="button" class="seup-btn seup-btn-secondary seup-modal-close">Zatvori</button>';
print '</div>';
print '</div>';
print '</div>';

print '</div>'; // content

// Copyright footer
print '<footer class="seup-footer">';
print '<div class="seup-footer-content">';
print '<div class="seup-footer-left">';
print '<p>Sva prava pridržana © <a href="https://8core.hr" target="_blank" rel="noopener">8Core Association</a> 2014 - ' . date('Y') . '</p>';
print '</div>';
print '<div class="seup-footer-right">';
print '<p class="seup-version">' . Changelog_Sistem::getVersion() . '</p>';
print '</div>';
print '</div>';
print '</footer>';

// JS
print '<script src="/custom/seup/js/seup-modern.js"></script>';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Modal functionality
  const modalTriggers = document.querySelectorAll('.seup-settings-card-trigger');
  const modals = document.querySelectorAll('.seup-modal');
  const modalCloses = document.querySelectorAll('.seup-modal-close');

  // Open modal
  modalTriggers.forEach(trigger => {
    trigger.addEventListener('click', function() {
      const modalId = this.getAttribute('data-modal');
      const modal = document.getElementById(modalId);
      if (modal) {
        modal.classList.add('show');
      }
    });
  });

  // Close modal
  modalCloses.forEach(closeBtn => {
    closeBtn.addEventListener('click', function() {
      const modal = this.closest('.seup-modal');
      if (modal) {
        modal.classList.remove('show');
      }
    });
  });

  // Close modal when clicking outside
  modals.forEach(modal => {
    modal.addEventListener('click', function(e) {
      if (e.target === this) {
        this.classList.remove('show');
      }
    });
  });

  // Auto-open modal based on hash or tab parameter
  const urlParams = new URLSearchParams(window.location.search);
  const tab = urlParams.get('tab');
  const hash = window.location.hash.substring(1);

  if (tab === 'trece_osobe' || hash === 'trece_osobe') {
    const modal = document.getElementById('treceOsobeModal');
    if (modal) {
      modal.classList.add('show');
    }
  } else if (tab === 'arhivska_gradiva' || hash === 'arhivska_gradiva') {
    const modal = document.getElementById('arhivskaGradivaModal');
    if (modal) {
      modal.classList.add('show');
    }
  } else if (tab === 'interne_oznake' || hash === 'interne_oznake') {
    const modal = document.getElementById('interneOznakeModal');
    if (modal) {
      modal.classList.add('show');
    }
  }

  // Ustanova AJAX
  const form = document.getElementById('ustanova-form');
  const actionField = document.getElementById('form-action');
  const btnSubmit = document.getElementById('ustanova-submit');
  if (form && btnSubmit) {
    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      btnSubmit.classList.add('seup-loading'); btnSubmit.disabled = true;
      const formData = new FormData(this);
      formData.append('action_ustanova', btnSubmit.textContent.includes('Dodaj') ? 'add' : 'update');
      try {
        const response = await fetch('<?php echo $_SERVER["PHP_SELF"]; ?>', { method:'POST', body:formData, headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'} });
        if (!response.ok) throw new Error('HTTP '+response.status);
        const result = await response.json();
        if (result.success) {
          actionField.value = 'update';
          btnSubmit.innerHTML = '<i class="fas fa-edit"></i> Ažuriraj';
          btnSubmit.classList.remove('seup-btn-primary'); btnSubmit.classList.add('seup-btn-secondary');
          document.getElementById('code_ustanova').value = result.data.code_ustanova;
          document.getElementById('name_ustanova').value = result.data.name_ustanova;
          showMessage(result.message, 'success');
        } else { showMessage(result.error || 'Greška pri spremanju', 'error'); }
      } catch (e) { showMessage('Došlo je do greške: '+e.message, 'error'); }
      finally { btnSubmit.classList.remove('seup-loading'); btnSubmit.disabled = false; }
    });
  }

  // Autocomplete minimal (placeholder)
  const input = document.getElementById('klasa_br');
  const resultsContainer = document.getElementById('autocomplete-results');
  if (input && resultsContainer) {
    function clearResults(){ resultsContainer.innerHTML=''; resultsContainer.style.display='none'; }
    document.addEventListener('click', function(e){ if(!e.target.closest('.seup-autocomplete-container')) clearResults(); });
  }

  // Interne oznake korisnika - load existing data
  const userInput = document.getElementById('ime_user');
  const redniBrojInput = document.getElementById('redni_broj');
  const radnoMjestoInput = document.getElementById('radno_mjesto_korisnika');

  if (userInput && redniBrojInput && radnoMjestoInput) {
    // Trigger on blur (when user leaves the field)
    userInput.addEventListener('blur', async function() {
      const selectedUser = this.value.trim();

      if (!selectedUser) {
        redniBrojInput.value = '';
        radnoMjestoInput.value = '';
        return;
      }

      try {
        const formData = new FormData();
        formData.append('action_oznaka', 'get_user_data');
        formData.append('ime_user', selectedUser);

        const response = await fetch('<?php echo $_SERVER["PHP_SELF"]; ?>', {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          }
        });

        if (!response.ok) {
          throw new Error('HTTP ' + response.status);
        }

        const result = await response.json();

        if (result.success && result.data) {
          redniBrojInput.value = result.data.redni_broj;
          radnoMjestoInput.value = result.data.radno_mjesto;
          showMessage('Podaci učitani', 'success', 2000);
        } else {
          redniBrojInput.value = '';
          radnoMjestoInput.value = '';
          if (result.message) {
            showMessage(result.message, 'info', 2000);
          }
        }
      } catch (error) {
        console.error('Greška pri dohvaćanju podataka:', error);
        showMessage('Greška pri učitavanju podataka', 'error');
      }
    });

    // Also trigger on datalist selection (input event)
    userInput.addEventListener('input', function() {
      // Debounce to avoid too many requests
      clearTimeout(userInput.loadTimeout);
      userInput.loadTimeout = setTimeout(async () => {
        const selectedUser = userInput.value.trim();
        if (!selectedUser) return;

        try {
          const formData = new FormData();
          formData.append('action_oznaka', 'get_user_data');
          formData.append('ime_user', selectedUser);

          const response = await fetch('<?php echo $_SERVER["PHP_SELF"]; ?>', {
            method: 'POST',
            body: formData,
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'application/json'
            }
          });

          if (response.ok) {
            const result = await response.json();
            if (result.success && result.data) {
              redniBrojInput.value = result.data.redni_broj;
              radnoMjestoInput.value = result.data.radno_mjesto;
              showMessage('Podaci učitani', 'success', 2000);
            }
          }
        } catch (error) {
          // Silent fail on input event
        }
      }, 500);
    });
  }

  // Toast poruke
  window.showMessage = function(message, type='success', duration=5000){
    let el = document.querySelector('.seup-message-toast');
    if (!el) { el = document.createElement('div'); el.className='seup-message-toast'; document.body.appendChild(el); }
    el.className = `seup-message-toast seup-message-${type} show`;
    el.innerHTML = `<i class="fas fa-${type==='success'?'check-circle':'exclamation-triangle'}"></i> ${message}`;
    setTimeout(()=>{ el.classList.remove('show'); }, duration);
  };

  // ============================================
  // TAB SWITCHING for Interne Oznake Modal
  // ============================================
  const tabButtons = document.querySelectorAll('.seup-tab-btn');
  const tabContents = document.querySelectorAll('.seup-tab-content');

  console.log('Tab buttons found:', tabButtons.length);
  console.log('Tab contents found:', tabContents.length);

  tabButtons.forEach(button => {
    button.addEventListener('click', function() {
      const targetTab = this.getAttribute('data-tab');
      console.log('Switching to tab:', targetTab);

      tabButtons.forEach(btn => btn.classList.remove('active'));
      tabContents.forEach(content => content.classList.remove('active'));

      this.classList.add('active');

      if (targetTab === 'novi-unos') {
        document.getElementById('tab-novi-unos').classList.add('active');
      } else if (targetTab === 'uredi-postojeci') {
        document.getElementById('tab-uredi-postojeci').classList.add('active');
      }
    });
  });

  // ============================================
  // AUTOCOMPLETE for Existing Records (Uredi Postojeći tab)
  // ============================================
  const editInput = document.getElementById('ime_user_edit');
  const autocompleteResults = document.getElementById('autocomplete-interne-results');
  const hiddenIdField = document.getElementById('hidden_id_oznake');
  const editRbrInput = document.getElementById('redni_broj_edit');
  const editNazivInput = document.getElementById('radno_mjesto_korisnika_edit');
  const btnUpdate = document.getElementById('btn-update-oznaka');
  const btnDelete = document.getElementById('btn-delete-oznaka');

  console.log('Autocomplete elements:', {
    editInput: !!editInput,
    autocompleteResults: !!autocompleteResults,
    hiddenIdField: !!hiddenIdField,
    editRbrInput: !!editRbrInput,
    editNazivInput: !!editNazivInput,
    btnUpdate: !!btnUpdate,
    btnDelete: !!btnDelete
  });

  let autocompleteTimeout = null;

  if (editInput && autocompleteResults) {
    editInput.addEventListener('input', function() {
      const query = this.value.trim();

      clearTimeout(autocompleteTimeout);

      if (query.length >= 3) {
        autocompleteTimeout = setTimeout(() => {
          fetchAutocomplete(query);
        }, 300);
      } else {
        clearAutocompleteResults();
        disableEditButtons();
      }
    });

    async function fetchAutocomplete(search) {
      try {
        const formData = new FormData();
        formData.append('action_oznaka', 'autocomplete_postojeci');
        formData.append('search', search);

        const response = await fetch('<?php echo $_SERVER["PHP_SELF"]; ?>', {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          }
        });

        if (!response.ok) {
          throw new Error('HTTP ' + response.status);
        }

        const result = await response.json();

        if (result.success && result.results.length > 0) {
          displayAutocompleteResults(result.results);
        } else {
          autocompleteResults.innerHTML = '<div class="seup-autocomplete-item seup-autocomplete-empty">Nema rezultata</div>';
          autocompleteResults.style.display = 'block';
        }
      } catch (error) {
        console.error('Greška pri dohvaćanju autocomplete:', error);
        autocompleteResults.innerHTML = '<div class="seup-autocomplete-item seup-autocomplete-empty">Greška pri pretraživanju</div>';
        autocompleteResults.style.display = 'block';
      }
    }

    function displayAutocompleteResults(results) {
      autocompleteResults.innerHTML = '';

      results.forEach(item => {
        const div = document.createElement('div');
        div.className = 'seup-autocomplete-item';
        div.innerHTML = `
          <div class="seup-autocomplete-item-content">
            <strong>${item.ime}</strong>
            <span class="seup-autocomplete-badge">[${String(item.rbr).padStart(2, '0')}]</span>
            <span class="seup-autocomplete-subtitle">${item.naziv}</span>
          </div>
        `;

        // Use mousedown instead of click to fire before blur event
        div.addEventListener('mousedown', (e) => {
          e.preventDefault();
          selectAutocompleteItem(item);
        });

        // Keep click as fallback
        div.addEventListener('click', (e) => {
          e.preventDefault();
          selectAutocompleteItem(item);
        });

        autocompleteResults.appendChild(div);
      });

      autocompleteResults.style.display = 'block';
    }

    function selectAutocompleteItem(item) {
      console.log('Selecting autocomplete item:', item);

      if (hiddenIdField) hiddenIdField.value = item.id;
      if (editInput) editInput.value = item.ime;
      if (editRbrInput) editRbrInput.value = item.rbr;
      if (editNazivInput) editNazivInput.value = item.naziv;

      clearAutocompleteResults();

      if (btnUpdate) {
        btnUpdate.disabled = false;
        console.log('Update button enabled');
      }
      if (btnDelete) {
        btnDelete.disabled = false;
        console.log('Delete button enabled');
      }

      if (typeof showMessage === 'function') {
        showMessage('Učitani podaci za: ' + item.ime, 'success', 2000);
      }
    }

    function clearAutocompleteResults() {
      autocompleteResults.innerHTML = '';
      autocompleteResults.style.display = 'none';
    }

    function disableEditButtons() {
      hiddenIdField.value = '';
      btnUpdate.disabled = true;
      btnDelete.disabled = true;
    }

    document.addEventListener('click', function(e) {
      if (!e.target.closest('.seup-autocomplete-container-interne')) {
        clearAutocompleteResults();
      }
    });

    editInput.addEventListener('blur', function() {
      setTimeout(() => {
        clearAutocompleteResults();
      }, 300);
    });
  }
});
</script>

<style>
.seup-message-toast{position:fixed;top:20px;right:20px;padding:12px 18px;border-radius:10px;color:#fff;font-weight:600;box-shadow:0 10px 30px rgba(0,0,0,.15);transform:translateX(400px);transition:transform .25s;z-index:9999;max-width:400px}
.seup-message-toast.show{transform:translateX(0)}
.seup-message-success{background:linear-gradient(135deg,#16a34a,#15803d)}
.seup-message-error{background:linear-gradient(135deg,#ef4444,#dc2626)}
.seup-message-info{background:linear-gradient(135deg,#3b82f6,#2563eb)}
.seup-btn.seup-loading{position:relative;color:transparent}
.seup-btn.seup-loading::after{content:'';position:absolute;top:50%;left:50%;width:16px;height:16px;margin:-8px 0 0 -8px;border:2px solid transparent;border-top:2px solid currentColor;border-radius:50%;animation:spin 1s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

/* ===================================== */
/* TAB NAVIGATION STYLES */
/* ===================================== */
.seup-tab-navigation {
  display: flex;
  gap: 0;
  background: #f8fafc;
  padding: 1.5rem 2.5rem 0 2.5rem;
  border-bottom: 2px solid #e2e8f0;
}

.seup-tab-btn {
  flex: 1;
  padding: 1rem 1.5rem;
  background: transparent;
  border: none;
  border-bottom: 3px solid transparent;
  color: #64748b;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  position: relative;
  margin-bottom: -2px;
}

.seup-tab-btn i {
  font-size: 1.1rem;
  transition: transform 0.3s ease;
}

.seup-tab-btn:hover {
  color: #4f46e5;
  background: rgba(79, 70, 229, 0.05);
}

.seup-tab-btn:hover i {
  transform: scale(1.1);
}

.seup-tab-btn.active {
  color: #4f46e5;
  border-bottom-color: #4f46e5;
  background: #ffffff;
}

.seup-tab-btn.active::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #4f46e5, #7c3aed);
  border-radius: 3px 3px 0 0;
}

/* ===================================== */
/* TAB CONTENT STYLES */
/* ===================================== */
.seup-tab-content {
  display: none;
  animation: fadeIn 0.3s ease-in-out;
}

.seup-tab-content.active {
  display: block;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* ===================================== */
/* AUTOCOMPLETE DROPDOWN STYLES */
/* ===================================== */
.seup-autocomplete-container-interne {
  position: relative;
}

.seup-autocomplete-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: #ffffff;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
  max-height: 280px;
  overflow-y: auto;
  z-index: 1000;
  margin-top: 0.5rem;
  display: none;
}

.seup-autocomplete-item {
  padding: 1rem 1.25rem;
  cursor: pointer;
  transition: all 0.2s ease;
  border-bottom: 1px solid #f1f5f9;
}

.seup-autocomplete-item:last-child {
  border-bottom: none;
}

.seup-autocomplete-item:hover {
  background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
  border-left: 3px solid #4f46e5;
  padding-left: calc(1.25rem - 3px);
}

.seup-autocomplete-item-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.seup-autocomplete-item-content strong {
  font-weight: 600;
  color: #1e293b;
  font-size: 1rem;
}

.seup-autocomplete-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.6rem;
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  color: white;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.5px;
}

.seup-autocomplete-subtitle {
  color: #64748b;
  font-size: 0.9rem;
  font-style: italic;
}

.seup-autocomplete-empty {
  text-align: center;
  color: #94a3b8;
  font-style: italic;
  cursor: default;
}

.seup-autocomplete-empty:hover {
  background: transparent;
  border-left: none;
  padding-left: 1.25rem;
}

/* ===================================== */
/* DISABLED BUTTON STYLES */
/* ===================================== */
.seup-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none !important;
}

.seup-btn:disabled:hover {
  box-shadow: none !important;
  transform: none !important;
}

.seup-btn-danger {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: white;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.seup-btn-danger:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
}

/* ===================================== */
/* RESPONSIVE DESIGN */
/* ===================================== */
@media (max-width: 768px) {
  .seup-tab-navigation {
    padding: 1rem 1.5rem 0 1.5rem;
  }

  .seup-tab-btn {
    padding: 0.75rem 1rem;
    font-size: 0.85rem;
  }

  .seup-tab-btn i {
    font-size: 1rem;
  }

  .seup-autocomplete-item {
    padding: 0.75rem 1rem;
  }

  .seup-autocomplete-item-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
}
</style>

<?php llxFooter(); $db->close(); ?>
