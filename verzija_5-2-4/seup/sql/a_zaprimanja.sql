-- =====================================================
-- SEUP Modul - Tablica za Zaprimanje Dokumentacije
-- =====================================================
-- Tablica za evidenciju zaprimljene dokumentacije
-- od trećih osoba (ustanova, pošiljatelja)
-- =====================================================

CREATE TABLE IF NOT EXISTS llx_a_zaprimanje (
  ID_zaprimanja INT(11) NOT NULL AUTO_INCREMENT,

  -- Veza s predmetom
  ID_predmeta INT(11) NOT NULL COMMENT 'Veza na a_predmet',

  -- Veza s dokumentom (akt ili prilog)
  fk_ecm_file INT(11) DEFAULT NULL COMMENT 'Link na zaprimljeni dokument (akt/prilog)',
  tip_dokumenta VARCHAR(50) DEFAULT 'nedodjeljeno' COMMENT 'Tip zaprimljenog dokumenta',

  -- Pošiljatelj (veza na llx_societe)
  fk_posiljatelj INT(11) DEFAULT NULL COMMENT 'Link na llx_societe',
  posiljatelj_naziv VARCHAR(255) DEFAULT NULL COMMENT 'Naziv pošiljatelja (fallback)',
  posiljatelj_broj VARCHAR(100) DEFAULT NULL COMMENT 'Broj pošiljke',

  -- Datum i način zaprimanja
  datum_zaprimanja DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Datum i vrijeme zaprimanja',
  nacin_zaprimanja VARCHAR(50) DEFAULT 'posta' COMMENT 'Način zaprimanja',

  -- Link na akt ako je ovo prilog
  fk_akt_za_prilog INT(11) DEFAULT NULL COMMENT 'Link na akt ako je prilog',

  -- Broj priloga
  broj_priloga INT(11) DEFAULT 1 COMMENT 'Broj fizičkih priloga',

  -- Potvrda zaprimanja (ECM)
  fk_potvrda_ecm_file INT(11) DEFAULT NULL COMMENT 'Link na potvrdu zaprimanja (povratnica, potpis)',

  -- Opis
  opis_zaprimanja TEXT COMMENT 'Kratak opis zaprimljenog sadržaja',
  napomena TEXT COMMENT 'Interna napomena',

  -- Metapodaci
  fk_user_creat INT(11) NOT NULL COMMENT 'Korisnik koji je zaprimio',
  datum_kreiranja DATETIME DEFAULT CURRENT_TIMESTAMP,
  entity INT(11) NOT NULL DEFAULT 1,

  PRIMARY KEY (ID_zaprimanja),
  KEY idx_predmet (ID_predmeta),
  KEY idx_posiljatelj (fk_posiljatelj),
  KEY idx_ecm_file (fk_ecm_file),
  KEY idx_datum (datum_zaprimanja),
  KEY fk_user (fk_user_creat),
  KEY fk_potvrda (fk_potvrda_ecm_file),
  KEY fk_akt (fk_akt_za_prilog),

  CONSTRAINT fk_zaprimanja_predmet FOREIGN KEY (ID_predmeta)
    REFERENCES llx_a_predmet(ID_predmeta) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_zaprimanja_ecm FOREIGN KEY (fk_ecm_file)
    REFERENCES llx_ecm_files(rowid) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_zaprimanja_posiljatelj FOREIGN KEY (fk_posiljatelj)
    REFERENCES llx_societe(rowid) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_zaprimanja_user FOREIGN KEY (fk_user_creat)
    REFERENCES llx_user(rowid) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_zaprimanja_potvrda FOREIGN KEY (fk_potvrda_ecm_file)
    REFERENCES llx_ecm_files(rowid) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_zaprimanja_akt FOREIGN KEY (fk_akt_za_prilog)
    REFERENCES llx_a_akti(ID_akta) ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Evidencija zaprimljene dokumentacije';
