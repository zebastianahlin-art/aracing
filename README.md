# A-Racing

Egenbyggd, mörk och modulär motorsport-webshop i modern PHP + MariaDB.

Detta repo är grunden för A-Racing-plattformen och ska byggas stegvis enligt projektets masterplan. Plattformen är serverrenderad, körs på egen VPS och ska prioritera snabbhet, robust drift, tydlig produktdata, tydlig lagerkommunikation och en adminyta som fungerar som kontrollcenter för en ensam operatör.

## Styrande dokument

Det överordnade styrdokumentet finns i `/docs/masterplan/` och ska alltid ha företräde framför lösa idéer, gamla chattförslag eller spontan omarkitektur.

Om något i kod eller implementation avviker från masterplanen ska masterplanen följas.

## Teknikbeslut

- Backend: PHP
- Databas: MariaDB
- Cache/stöd för batch och köer: Redis
- Webbserver: Nginx
- Frontend: serverrenderad storefront
- Drift: egen VPS
- Språk från start: svenska och engelska

## Produktmål

A-Racing ska lösa dessa kärnproblem:

- produktimport
- artikelvård
- pris- och lageruppdatering
- orderstöd
- på sikt YMM/fitment
- på sikt AI-stöd för kvalitet, SEO och arbetsbesparing

MVP ska kunna ersätta nuvarande butik i praktiken.

## MVP-scope

MVP måste innehålla:

- produkter
- kategorier
- varumärken
- sök
- kundvagn
- checkout
- betalning
- Fraktjakt
- leverantörsregister
- CSV-import
- pris/lagerlogik
- dashboard
- tabellredigering i admin

MVP ska inte innehålla i första lanserbara versionen:

- AI-URL-import
- full YMM
- AI-fitment
- sociala medier-utkast
- leverantörsbevakning

## Roadmap

Projektet byggs i följande ordning:

1. Arkitekturlåsning och underlag
2. Kärnplattform: katalog, storefront, checkout, betalning, frakt
3. Leverantör och artikelhantering: import, pris, lager, dashboard, bulk edit
4. Orderlogik: inköpsförslag, restorder, delleveranser
5. Fitment / YMM
6. AI som sparar tid
7. Tillväxt och automation

Bygg inte senare roadmap-faser innan tidigare faser fungerar stabilt.

## Repo-struktur

```text
/docs        Masterplan, modulkrav, wireframes, datamodell, ADR:er, backlog
/app         Applikationskod uppdelad per modul/domän
/config      Miljö- och integrationskonfiguration
/database    Migrationer, seeders och referensdata
/public      Publika assets och storefront-entry
/tests       Automatiserade tester, fixtures och importexempel
/scripts     CLI-kommandon för import, indexering, cache och batchjobb
/storage     Importfiler, loggar, genererade bilder och temporära filer
```

## Lokal uppstart (foundation)

1. Installera beroenden:
   ```bash
   composer install
   ```
2. Skapa miljöfil:
   ```bash
   cp .env.example .env
   ```
3. Starta lokal server:
   ```bash
   composer serve
   ```
4. Öppna i webbläsare:
   - `http://127.0.0.1:8000/`
   - `http://127.0.0.1:8000/admin`

För att se migrationer som ska köras manuellt i MariaDB:

```bash
php scripts/migrate.php
```


## Katalogblock (nuvarande status)

Följande finns nu i foundation:
- verklig katalogkoppling för storefront (`/`, `/category/{slug}`, `/product/{slug}`)
- enkel serverrenderad katalogadmin för brands/categories/products
- stöd för product attributes och product images i adminformulär

Exempel admin-URL:er:
- `/admin/brands`
- `/admin/categories`
- `/admin/products`

Databas:
- kör även `database/migrations/002_catalog_foundation.sql`
- valfritt: kör `database/seeders/001_catalog_demo.sql` för demo-data


## Leverantörsregister och CSV-import v1

Databas:
- kör även `database/migrations/003_supplier_import_v1.sql`

Admin-URL:er:
- `/admin/suppliers`
- `/admin/import-profiles`
- `/admin/import-runs`

Lokal test av CSV-import:
1. Skapa en leverantör i `/admin/suppliers`.
2. Skapa en importprofil i `/admin/import-profiles` och ange JSON mapping (t.ex. `supplier_sku`, `supplier_title`, `price`, `stock_qty`).
3. Öppna `/admin/import-runs`, välj profil och ladda upp en CSV-fil.
4. Öppna körningsdetaljen för att granska radstatus, felmeddelanden, rådata och mappad data.


## Pris/lager + produkt-leverantörskoppling v1

Databas:
- kör även `database/migrations/004_product_supplier_links_v1.sql`

Admin:
- `/admin/products` visar nu länkstatus mot leverantörsartikel samt enkel pris/lagerstatus.
- `/admin/products/{id}/edit` har filterbar supplier_item-lista och sparar primär produktkoppling med snapshots.

Storefront:
- produktkort på start och kategori visar pris (om satt) och lagerstatus.
- produktsida visar `sale_price` + `currency_code` samt enkel lagerstatus.

## Kundvagn + checkout + order v1

Databas:
- kör även `database/migrations/005_cart_checkout_order_v1.sql`

Storefront-flöde:
- produktsida har nu "Lägg i kundvagn" (blockeras om `stock_status = out_of_stock`)
- kundvagn finns på `/cart` med uppdatera/ta bort
- checkout finns på `/checkout`
- orderbekräftelse visas på `/checkout/confirmation`

Prisstrategi i v1:
- add-to-cart använder `products.sale_price`
- om `sale_price` saknas blockeras köp tydligt

Orderadmin v1:
- `/admin/orders` listar ordrar
- `/admin/orders/{id}` visar orderdetalj + manuell statusuppdatering

## Orderadmin v2 (operativ hantering)

Databas:
- kör även `database/migrations/006_order_admin_v2.sql`

Admin:
- `/admin/orders` har nu sök + filter (status, betalstatus, leveransstatus)
- `/admin/orders/{id}` har intern referens, interna anteckningar, historik samt knappar för packad/skickad
- `/admin/orders/{id}/print` är en utskriftsvänlig plock-/ordervy

Lokal manuell test:
1. Skapa en order via storefront checkout.
2. Öppna `/admin/orders` och testa sök/filter.
3. Öppna orderdetalj och uppdatera statusfält + intern referens.
4. Lägg till intern anteckning och verifiera att historikrad skapas.
5. Klicka `Markera packad` och `Markera skickad` och verifiera tidsstämplar + historik.
6. Öppna utskriftsvyn och kontrollera att sidan är ren för vanlig webbutskrift.

## Pris/lager v2 + operativ produktöversikt (admin)

Admin:
- `/admin/products` fungerar nu som operativ arbetsyta med sök/filter för namn, SKU, aktiv-status, leverantörskoppling, avvikelse och lagerstatus.
- tabellen visar publicerad produktdata sida vid sida med leverantörssnapshot och tydliga avvikelseflaggor.
- manuella åtgärder finns per rad: synka snapshot, kopiera leverantörspris, kopiera leverantörslager, sätt lagerstatus från publicerat antal, snabb växling aktiv/inaktiv.
- enkel bulkåtgärd finns för markerade produkter.

Lokal manuell test:
1. Öppna `/admin/products`.
2. Testa sök/filter-kombinationer och verifiera att listan uppdateras.
3. Kör `Synka snapshot` på en produkt med primär leverantörslänk och verifiera uppdaterade snapshot-fält.
4. Kör `Kopiera pris` och kontrollera i produktens redigeringsvy att `sale_price` uppdaterats.
5. Kör `Kopiera lager` och `Sätt lagerstatus` och verifiera publicerad `stock_quantity` + `stock_status`.
6. Markera flera rader och kör en bulkåtgärd (t.ex. `Markera inaktiv`).

## Operativ orderhantering v3 (plock + manuell försändelse)

Databas:
- kör även `database/migrations/007_order_operations_v3.sql`

Admin:
- `/admin/orders` visar nu operativ status med `processing`, `packed`, `shipped` samt tracking/fraktmetod i listan.
- `/admin/orders/{id}` har plockvänligare radlista, tydligare kund/leveransinfo och formulär för manuell försändelseinfo.
- operativa actions finns för `Markera processing`, `Markera packad`, `Markera skickad`.

Historik:
- event loggas vid processing/packed/shipped.
- event loggas när trackingnummer, fraktmetod eller försändelsenotering uppdateras.

Kundvy:
- orderbekräftelsen visar status + eventuell försändelseinfo.
- enkel orderstatussida finns på `/order-status`.

Lokal manuell test:
1. Skapa en order via `/checkout`.
2. Öppna `/admin/orders/{id}` och klicka `Markera processing`, sedan `Markera packad`, sedan `Markera skickad`.
3. Fyll i och spara `tracking_number`, `shipping_method`, `shipped_by_name`, `shipment_note`.
4. Verifiera att historiken visar separata events för status och försändelsefält.
5. Öppna `/order-status?order_number={ORDERNUMMER}` och verifiera kundsynlig status.

## Inköp light + lagerpåfyllnad v1

Databas:
- kör även `database/migrations/008_purchasing_light_v1.sql`

Admin:
- `/admin/purchasing` visar produkter med påfyllnadsbehov
- `/admin/purchase-lists` listar manuella inköpsunderlag
- `/admin/purchase-lists/{id}` visar detalj med rader, status, anteckning och vald kvantitet

Lokal manuell test:
1. Säkerställ att produkter har primär leverantörskoppling och lågt/null lager.
2. Öppna `/admin/purchasing`, markera rader och skapa ett underlag.
3. Öppna underlaget via `/admin/purchase-lists` och justera `selected_quantity` per rad.
4. Uppdatera status (`draft/reviewed/exported`) samt anteckning i detaljvyn.
