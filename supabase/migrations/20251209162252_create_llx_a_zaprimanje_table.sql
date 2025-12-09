/*
  # Kreiranje tablice llx_a_zaprimanje (Zaprimanje Dokumentacije)

  ## Opis
  Tablica za evidenciju zaprimljene dokumentacije od trećih osoba (ustanova, pošiljatelja).
  Ovo je standalone verzija bez Foreign Key constrainta jer parent tablice ne postoje u Supabase okruženju.

  ## Nova tablica
  - `llx_a_zaprimanje`
    - `id_zaprimanja` (SERIAL, PRIMARY KEY) - Jedinstveni ID zaprimanja
    - `id_predmeta` (INTEGER) - Veza na predmet
    - `fk_ecm_file` (INTEGER) - Link na zaprimljeni dokument
    - `tip_dokumenta` (VARCHAR) - Tip dokumenta (novi_akt/prilog_postojecem/nerazvrstan)
    - `fk_posiljatelj` (INTEGER) - Link na pošiljatelja (llx_societe)
    - `posiljatelj_naziv` (VARCHAR) - Naziv pošiljatelja (fallback)
    - `posiljatelj_broj` (VARCHAR) - Broj pošiljke
    - `datum_zaprimanja` (TIMESTAMPTZ) - Datum i vrijeme zaprimanja
    - `nacin_zaprimanja` (VARCHAR) - Način zaprimanja (posta/email/rucno/courier)
    - `fk_akt_za_prilog` (INTEGER) - Link na akt ako je prilog
    - `broj_priloga` (INTEGER) - Broj fizičkih priloga
    - `fk_potvrda_ecm_file` (INTEGER) - Link na potvrdu zaprimanja
    - `opis_zaprimanja` (TEXT) - Kratak opis zaprimljenog sadržaja
    - `napomena` (TEXT) - Interna napomena
    - `fk_user_creat` (INTEGER) - Korisnik koji je zaprimio
    - `datum_kreiranja` (TIMESTAMPTZ) - Datum kreiranja zapisa
    - `entity` (INTEGER) - Entity ID

  ## Sigurnost (RLS)
  - Omogućen RLS za tablicu
  - Politika omogućava authenticated korisnicima pristup svim zapisima (za sada)

  ## Napomene
  - Ova tablica koristi INTEGER tipove za ID umjesto BIGINT jer je kompatibilna sa postojećim Dolibarr strukturama
  - Foreign Key constrainti su izostavljeni jer parent tablice (llx_a_predmet, llx_ecm_files, itd.) ne postoje
*/

-- Kreiranje tablice
CREATE TABLE IF NOT EXISTS llx_a_zaprimanje (
  id_zaprimanja SERIAL PRIMARY KEY,
  
  -- Veza s predmetom
  id_predmeta INTEGER NOT NULL,
  
  -- Veza s dokumentom (akt ili prilog)
  fk_ecm_file INTEGER DEFAULT NULL,
  tip_dokumenta VARCHAR(50) DEFAULT 'nedodjeljeno',
  
  -- Pošiljatelj
  fk_posiljatelj INTEGER DEFAULT NULL,
  posiljatelj_naziv VARCHAR(255) DEFAULT NULL,
  posiljatelj_broj VARCHAR(100) DEFAULT NULL,
  
  -- Datum i način zaprimanja
  datum_zaprimanja TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  nacin_zaprimanja VARCHAR(50) DEFAULT 'posta',
  
  -- Link na akt ako je ovo prilog
  fk_akt_za_prilog INTEGER DEFAULT NULL,
  
  -- Broj priloga
  broj_priloga INTEGER DEFAULT 1,
  
  -- Potvrda zaprimanja (ECM)
  fk_potvrda_ecm_file INTEGER DEFAULT NULL,
  
  -- Opis
  opis_zaprimanja TEXT DEFAULT NULL,
  napomena TEXT DEFAULT NULL,
  
  -- Metapodaci
  fk_user_creat INTEGER NOT NULL,
  datum_kreiranja TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  entity INTEGER NOT NULL DEFAULT 1
);

-- Kreiranje indeksa za bolje performanse
CREATE INDEX IF NOT EXISTS idx_zaprimanje_predmet ON llx_a_zaprimanje(id_predmeta);
CREATE INDEX IF NOT EXISTS idx_zaprimanje_posiljatelj ON llx_a_zaprimanje(fk_posiljatelj);
CREATE INDEX IF NOT EXISTS idx_zaprimanje_ecm_file ON llx_a_zaprimanje(fk_ecm_file);
CREATE INDEX IF NOT EXISTS idx_zaprimanje_datum ON llx_a_zaprimanje(datum_zaprimanja);
CREATE INDEX IF NOT EXISTS idx_zaprimanje_user ON llx_a_zaprimanje(fk_user_creat);
CREATE INDEX IF NOT EXISTS idx_zaprimanje_potvrda ON llx_a_zaprimanje(fk_potvrda_ecm_file);
CREATE INDEX IF NOT EXISTS idx_zaprimanje_akt ON llx_a_zaprimanje(fk_akt_za_prilog);

-- Dodavanje komentara na tablicu
COMMENT ON TABLE llx_a_zaprimanje IS 'Evidencija zaprimljene dokumentacije od trećih osoba';

-- Dodavanje komentara na stupce
COMMENT ON COLUMN llx_a_zaprimanje.id_zaprimanja IS 'Jedinstveni ID zaprimanja';
COMMENT ON COLUMN llx_a_zaprimanje.id_predmeta IS 'Veza na a_predmet';
COMMENT ON COLUMN llx_a_zaprimanje.fk_ecm_file IS 'Link na zaprimljeni dokument (akt/prilog)';
COMMENT ON COLUMN llx_a_zaprimanje.tip_dokumenta IS 'Tip zaprimljenog dokumenta';
COMMENT ON COLUMN llx_a_zaprimanje.fk_posiljatelj IS 'Link na llx_societe';
COMMENT ON COLUMN llx_a_zaprimanje.posiljatelj_naziv IS 'Naziv pošiljatelja (fallback)';
COMMENT ON COLUMN llx_a_zaprimanje.posiljatelj_broj IS 'Broj pošiljke';
COMMENT ON COLUMN llx_a_zaprimanje.datum_zaprimanja IS 'Datum i vrijeme zaprimanja';
COMMENT ON COLUMN llx_a_zaprimanje.nacin_zaprimanja IS 'Način zaprimanja (posta/email/rucno/courier)';
COMMENT ON COLUMN llx_a_zaprimanje.fk_akt_za_prilog IS 'Link na akt ako je prilog';
COMMENT ON COLUMN llx_a_zaprimanje.broj_priloga IS 'Broj fizičkih priloga';
COMMENT ON COLUMN llx_a_zaprimanje.fk_potvrda_ecm_file IS 'Link na potvrdu zaprimanja (povratnica, potpis)';
COMMENT ON COLUMN llx_a_zaprimanje.opis_zaprimanja IS 'Kratak opis zaprimljenog sadržaja';
COMMENT ON COLUMN llx_a_zaprimanje.napomena IS 'Interna napomena';
COMMENT ON COLUMN llx_a_zaprimanje.fk_user_creat IS 'Korisnik koji je zaprimio';
COMMENT ON COLUMN llx_a_zaprimanje.datum_kreiranja IS 'Datum kreiranja zapisa';
COMMENT ON COLUMN llx_a_zaprimanje.entity IS 'Entity ID za multi-company podršku';

-- Omogućavanje Row Level Security (RLS)
ALTER TABLE llx_a_zaprimanje ENABLE ROW LEVEL SECURITY;

-- RLS Politike za tablicu
-- Politika 1: Authenticated korisnici mogu pregledavati sve zapise
CREATE POLICY "Authenticated users can view all zaprimanja"
  ON llx_a_zaprimanje
  FOR SELECT
  TO authenticated
  USING (true);

-- Politika 2: Authenticated korisnici mogu unositi nove zapise
CREATE POLICY "Authenticated users can insert zaprimanja"
  ON llx_a_zaprimanje
  FOR INSERT
  TO authenticated
  WITH CHECK (true);

-- Politika 3: Authenticated korisnici mogu ažurirati zapise
CREATE POLICY "Authenticated users can update zaprimanja"
  ON llx_a_zaprimanje
  FOR UPDATE
  TO authenticated
  USING (true)
  WITH CHECK (true);

-- Politika 4: Authenticated korisnici mogu brisati zapise
CREATE POLICY "Authenticated users can delete zaprimanja"
  ON llx_a_zaprimanje
  FOR DELETE
  TO authenticated
  USING (true);
