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

### Produktmedia v1 (admin)

Produktredigering (`/admin/products/{id}/edit`) har nu en mediasektion för:
- uppladdning av en eller flera bilder (multipart/form-data)
- val av primärbild
- sort_order
- alt-text
- borttagning

Bilder sparas lokalt i `public/uploads/product-images/` och refereras via `product_images.image_url`.

Lokal snabbtest:
1. Öppna en produkt i admin.
2. Ladda upp en eller flera bilder i sektionen `Produktmedia v1`.
3. Sätt en primärbild och justera `sort_order`.
4. Verifiera att produktkort i storefront (start/kategori/sök) visar primärbild eller fallback om bild saknas.
5. Verifiera produktsidan visar primärbild tydligt och extra bilder som enkel thumbnail-strip.


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




## Fitment data workflow v1 (admin)

Databas:
- kör även `database/migrations/036_fitment_data_workflow_v1.sql`

Admin:
- `/admin/fitment-workflow` ger operativ kö för fitmentarbete.
- Filter/snabbval:
  - `Saknar fitment`
  - `Har fitment`
  - `Endast universal`
  - `Behöver granskning`
  - valfritt filter på brand/kategori + sök på produkt/SKU
- Vyn visar signaler per produkt:
  - `no_fitment_links`
  - `universal_only`
  - `has_confirmed_fitments`
  - `needs_review`
- Admin kan sätta enkel intern status per produkt (`needs_fitment`, `reviewed`, `handling`) och intern notering.

Produktadmin (`/admin/products/{id}/edit#fitment`):
- visar tydligt antal fordonskopplingar
- stöd för att söka/filtera fordonslistan innan koppling läggs till
- fortsatt stöd för lägga till/ta bort produkt↔fordon-koppling

Lokal testning:
1. Kör migrationer (`php scripts/migrate.php`) och applicera SQL-filer i MariaDB inkl. `035_fitment_ymm_v1.sql` + `036_fitment_data_workflow_v1.sql`.
2. Öppna `/admin/fitment-workflow`, testa köfilter + brand/kategorifilter.
3. Uppdatera status/notering på en produkt och verifiera att värdet sparas.
4. Öppna en produkt och verifiera att fitmentsektionen visar antal kopplingar, fordonssök och add/remove fungerar.

## Order fulfillment / packing workflow v2

Databas:
- kör även `database/migrations/031_order_fulfillment_packing_workflow_v2.sql`

Admin:
- `/admin/orders` har nu operativa köer/snabblägen: `Att behandla`, `Att plocka`, `Att packa`, `Redo att skicka`
- orderlistan visar tydligare operativ signal med antal rader och totalt artiklar
- `/admin/orders/{id}` har tydligare fulfillment-block med plockstart/packad/skickad, stegknappar och interna plock/pack-noteringar
- `/admin/orders/{id}/print` är en utskriftsvänlig plock-/packlista med checkruta och internnoteringar

Fulfillment-actions i v2:
- Starta plock (`unfulfilled` -> `picking`)
- Markera packad (`picking` -> `packed`)
- Markera skickad (`packed` -> `shipped`)
- Markera levererad (`shipped` -> `delivered`)

Lokal manuell test:
1. Öppna `/admin/orders` och växla mellan köerna.
2. Verifiera att orderrader visar statusar + rader/antal.
3. Öppna en order och kör stegvis `Starta plock` -> `Markera packad` -> `Markera skickad`.
4. Spara interna plock-/packnoteringar och verifiera historikrader.
5. Öppna utskriftsvyn och kontrollera att underlaget är läsbart och skrivbart.

## Betalningsförberedelser v1 (utan extern provider)

Databas:
- kör även `database/migrations/011_order_payment_preparations_v1.sql`

Storefront:
- `/checkout` har nu val av betalmetod (`invoice_request`, `manual_card_phone`, `bank_transfer`).
- orderbekräftelse och `/order-status` visar betalmetod, betalstatus och nästa steg.

Admin:
- `/admin/orders` visar betalmetod tydligt och har filter på betalmetod.
- `/admin/orders/{id}` visar betalningsöversikt och separat formulär för manuell uppföljning av `payment_status`, `payment_reference` och `payment_note`.
- orderhistorik loggar events när betalstatus eller betalreferens ändras.

Lokal manuell test:
1. Lägg produkter i kundvagn och slutför checkout med olika betalmetoder.
2. Verifiera orderbekräftelse + `/order-status` för vald metod och betalstatus.
3. Öppna `/admin/orders`, filtrera på betalmetod och kontrollera resultat.
4. Öppna en order i admin och uppdatera betalstatus/referens/notering.
5. Verifiera att eventhistoriken innehåller rader för ändrad betalstatus och betalreferens.

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
- kör även `database/migrations/032_purchase_intake_restock_v1.sql` (liten manuell restockstatus/anteckning)

Admin:
- `/admin/purchasing` är nu en restock-/replenishment-vy för operativ granskning
- vyn visar produkt, SKU, stock_quantity, stock_status, backorder_allowed och tydliga restock-signaler
- signaler i v1: `out_of_stock`, `low_stock`, `backorder_enabled`, `missing_supplier_link`
- supplier item-data visas som beslutsstöd där koppling finns (leverantör, leverantörs-SKU, senast snapshot-pris/lager)
- produkter utan supplier-koppling kan fortfarande visas när lagersituationen kräver restockgranskning
- manuell hantering finns direkt i listan: intern notering + status (`Ny`, `Granskad`, `Hanteras`)
- snabbfilter finns för signal, leverantör och manuell status
- `/admin/purchase-order-drafts` listar inköpsutkast med filter på status (`draft`, `exported`, `cancelled`)
- `/admin/purchase-order-drafts/{id}` visar detalj med snapshot-rader, intern notering och kvantitetsjustering per rad
- `/admin/purchase-order-drafts/{id}/print` ger enkel utskrifts-/exportvänlig supplier order-vy
- restock-vyn kan skapa flera inköpsutkast i ett steg, grupperat per leverantör
- produkter utan leverantörskoppling exkluderas från skapandet i v1 och rapporteras i återkopplingen

Lokal manuell test:
1. Säkerställ att det finns aktiva produkter i olika lagerlägen (slut/låg nivå/backorder), gärna hos minst två leverantörer.
2. Öppna `/admin/purchasing`, markera flera restock-rader och klicka `Skapa inköpsutkast per leverantör`.
3. Verifiera att ett eller flera utkast skapades och att varje utkast innehåller rader för en leverantör.
4. Öppna `/admin/purchase-order-drafts/{id}` och justera `quantity`, ta bort en rad och uppdatera intern notering.
5. Öppna `/admin/purchase-order-drafts/{id}/print` och verifiera att supplier order-underlag är tydligt och utskriftsvänligt.
6. Markera utkast som `exported` eller `cancelled` och verifiera att fortsatt redigering blockeras.


## Leverantörsflöde v2: importgranskning + manuell matchningskö

Databas:
- kör även `database/migrations/009_supplier_item_review_v2.sql`

Admin:
- `/admin/supplier-item-review` visar importgranskning för `supplier_items`
- filtrering: SKU, titel, leverantör, import run, matchningsstatus och databrister
- manuella åtgärder: matcha/byta matchning mot befintlig produkt, rensa matchning, markera granskad

Lokal manuell test:
1. Kör en CSV-import via `/admin/import-runs`.
2. Öppna `/admin/supplier-item-review` och filtrera fram omatchade rader.
3. Matcha en leverantörsartikel till en befintlig produkt och verifiera status `Kopplad`.
4. Byt matchning till en annan produkt och verifiera att kopplingen flyttas tydligt.
5. Rensa matchning och verifiera status `Behöver granskning`.


## Produktupprättning från supplier item + artikelvårdskö v1

Admin:
- `/admin/supplier-item-review` har nu action `Skapa ny produkt` för omatchade leverantörsartiklar.
- `/admin/products/create?supplier_item_id={id}` öppnar produktformulär med konservativ förifyllning från supplier item (namn/SKU, tom description, inget auto-satt sale_price/lagerstatus).
- vid sparning med primär leverantörskoppling skapas produkt + explicit `product_supplier_link`, snapshots uppdateras och supplier item markeras som länkad/granskad.
- `/admin/products/article-care` visar artikelvårdskö för databrister med filter/sök för namn, SKU, aktiv/inaktiv, bristtyp och med/utan leverantörskoppling.

Lokal manuell test:
1. Öppna `/admin/supplier-item-review` och klicka `Skapa ny produkt` på en omatchad rad.
2. Verifiera att formuläret är förifyllt från leverantörsdata och att källa/databrister visas tydligt.
3. Spara produkten och verifiera i `/admin/supplier-item-review` att artikeln inte längre är omatchad.
4. Öppna `/admin/products/article-care`, filtrera på t.ex. `Saknar sale_price` eller `Saknar leverantörskoppling` och verifiera att rätt produkter visas.
5. Använd snabblänk `Redigera`, komplettera data och kontrollera att produkten försvinner från valda bristfilter.

## CMS light + startsida/landningsytor v1

Databas:
- kör även `database/migrations/010_cms_light_v1.sql`

Admin:
- `/admin/cms/pages` listar CMS-sidor
- `/admin/cms/pages/create` skapar ny sida (page/legal/info)
- `/admin/cms/pages/{id}/edit` redigerar titel, slug, meta och HTML-innehåll

Storefront:
- `/pages/{slug}` renderar aktiva informationssidor

Lokal manuell test:
1. Skapa en sida i `/admin/cms/pages` (t.ex. slug `kopvillkor`) och sätt den aktiv.
2. Öppna `/pages/kopvillkor` och verifiera innehåll.

## Homepage merchandising / featured collections v1

Databas:
- kör även `database/migrations/030_homepage_merchandising_v1.sql`

Admin:
- `/admin/homepage-sections` hanterar startsidessektioner och items
- stöd för sektionstyper: `featured_products`, `featured_category`, `mixed_manual`
- varje sektion har `key`, titel, underrubrik, sortering, max_items, aktiv/inaktiv och valfri CTA (`label` + `url`)
- varje sektion kan manuellt få rader av typen `product` eller `category` med egen sortering och aktiv-flagga

Storefront:
- `/` hämtar aktiva startsidessektioner i `sort_order`
- sektionerna renderar manuellt kopplade produkter/kategorier och respekterar `max_items`
- produkter filtreras med samma publika regel som övrig storefront (`is_active=1`, inte sökdold)
- kategorier utan giltig slug filtreras bort defensivt
- startsidan tål saknade/dolda kopplingar och visar färre kort utan att krascha

Lokal testning:
1. Kör migrationen ovan.
2. Öppna `/admin/homepage-sections` och skapa en sektion.
3. Lägg till några item-rader med produkt-ID och/eller kategori-ID.
4. Öppna `/` och verifiera ordning, max_items, CTA och att endast publika/synliga objekt renderas.
5. Inaktivera en produkt eller item-rad och verifiera att den försvinner från startsidan utan fel.

## Storefront sök + filtrering v2 (filter UX polish)

Ny route:
- `/search`

Stöd i v2:
- sök på produktnamn, SKU, varumärke och kategori
- filter på kategori, varumärke, min/max-pris och lagerstatus
- tydlig aktiv filter-sammanfattning med möjlighet att ta bort enstaka filter
- `Rensa alla`-länk som behåller relevant basstate (t.ex. sortering)
- sortering och filter samexisterar i stabil URL-state (query params)
- defensiv normalisering av query-params i `CatalogService`:
  - ogiltig sortering fallbackar till `curated` eller `relevance` (vid sökfras)
  - ogiltig lagerstatus ignoreras
  - prisvalidering accepterar numeriska värden, sanerar negativa värden och byter plats på min/max vid omvänt intervall
- facet-värden för varumärke/kategori/lagerstatus visas kontextuellt med träffantal (utan extern sökplattform)
- kategori- och söksidor använder samma centrala listningslogik i service/repository

Konsekvent regel för prislösa produkter i listning:
- produkter utan `sale_price` visas i listning med texten `Pris visas vid förfrågan`
- endast aktiva produkter (`is_active = 1`) visas i storefront-listningar

Lokal manuell test:
1. Starta servern med `composer serve`.
2. Öppna `/search` och verifiera att träffantal, aktiva filter och filterpanelen renderas.
3. Testa filterkombinationer (kategori, brand, pris, lagerstatus) och verifiera att URL uppdateras konsekvent.
4. Byt sortering och verifiera att filterstate inte tappas bort.
5. Klicka bort enstaka filter-chip samt `Rensa alla` och verifiera att listan återställs korrekt.
6. Öppna `/category/{slug}` och verifiera motsvarande beteende inom låst kategorikontext.

## Kundförtroende-sidor + storefront polish v1

Storefronten visar nu informationssidor i både header och footer via CMS light.
Skapa/uppdatera sidor i admin under `/admin/cms/pages` med följande slugs för att fylla trust-navigationen:
- `kontakt`
- `kopvillkor`
- `retur-reklamation`
- `fraktinfo`
- `om-oss`

Snabb lokal verifiering:
1. Aktivera/uppdatera ovan sidor i CMS.
2. Kontrollera att länkar syns i storefront header/footer.
3. Kontrollera trust-block på start-, kategori-, produkt-, kundvagn- och checkout-sidor.
4. Kontrollera checkout med fel (t.ex. tom e-post) för tydlig feltext och hjälpruta.

## Order lifecycle + fulfillment v1 (utan fraktintegration)

Databas:
- kör även `database/migrations/012_order_lifecycle_fulfillment_v1.sql`

Lokal snabbtest:
1. Lägg en order via checkout.
2. Öppna `/admin/orders/{id}` och använd actions för orderstatus (`placed -> confirmed -> processing -> completed` eller `cancelled`).
3. Använd fulfillment-actions (`unfulfilled -> picking -> packed -> shipped -> delivered` eller `cancelled`).
4. Uppdatera `carrier_name`, `tracking_number`, `tracking_url`, `shipped_at` manuellt och verifiera att timeline uppdateras.
5. Verifiera storefront på `/checkout/confirmation` och `/order-status` visar `order_status`, `payment_status`, `fulfillment_status` samt leverans-/trackinginfo.

## Transactional email v1 för orderflöde (utan extern ESP)

Databas:
- kör även `database/migrations/013_transactional_email_v1.sql`

Lokal snabbtest:
1. Säkerställ att servern kan skicka via PHP `mail()` i din miljö (alternativt verifiera failed-logg i admin om lokal mailserver saknas).
2. Lägg en order via checkout och verifiera att orderbekräftelse loggas i `email_messages`.
3. Markera ordern som `shipped` i admin och verifiera loggrad för `order_shipped`.
4. Annullera ordern via orderstatus eller fulfillmentstatus och verifiera loggrad för `order_cancelled`.
5. Öppna `/admin/orders/{id}` och kontrollera sektionen `E-posthistorik` (typ, mottagare, ämne, status, sent_at och feltext).

## Inventory availability + stock model v1

Databas:
- kör även `database/migrations/014_inventory_availability_v1.sql`

Snabbtest lokalt:
1. Öppna `/admin/products/{id}/edit` och uppdatera `stock_quantity`, `stock_status`, `backorder_allowed`.
2. Kör manuell lagerjustering i samma vy (set eller plus/minus) och verifiera att historiken uppdateras.
3. Öppna produkten i storefront och verifiera tydlig status (`I lager`, `Tillfälligt slut`, `Beställningsvara`) samt köpbarhet.
4. Försök lägga ej köpbar produkt i kundvagn/checkout och verifiera att server-side validering blockerar flödet.

## Fraktmetoder + fraktkostnad v1

Databas:
- kör även `database/migrations/015_shipping_methods_cost_model_v1.sql`

Admin:
- `/admin/shipping-methods` för att skapa/redigera/aktivera/sortera fraktmetoder

Snabbtest lokalt:
1. Öppna `/admin/shipping-methods` och verifiera att `standard`, `express`, `pickup` finns och är aktiva.
2. Lägg produkt i kundvagn och gå till `/checkout`.
3. Välj fraktmetod och skapa order.
4. Verifiera i orderbekräftelse + `/order-status` att fraktmetod, fraktkostnad och grand total visas.
5. Öppna ordern i `/admin/orders/{id}` och verifiera snapshot-fälten för fraktmetod + kostnad.


## Discounts / promotions v1 (kampanjkoder)

Databas:
- kör även `database/migrations/016_discounts_promotions_v1.sql`

Storefront:
- kundvagn (`/cart`) har fält för kampanjkod + ta bort kod
- checkout (`/checkout`) återanvänder samma rabattberäkning och visar rabatt i totalsammanfattning

Admin:
- `/admin/discount-codes` för att skapa/redigera/aktivera kampanjkoder

Snabbtest:
1. Lägg produkt i kundvagn.
2. Ange kod `RACE10` i kundvagn och verifiera rabattpost i totalsumman.
3. Gå till checkout och verifiera att rabatt följer med.
4. Slutför order och verifiera rabatt på orderbekräftelse, orderstatus och i admin orderdetalj.

## Payment provider integration v1 (Stripe Checkout)

Databas:
- kör även `database/migrations/017_payment_provider_integration_v1.sql`

Nya env-nycklar:
- `STRIPE_PUBLISHABLE_KEY`
- `STRIPE_SECRET_KEY`
- `STRIPE_WEBHOOK_SECRET`

Flöde i v1:
- checkout-metoden `Kort / direktbetalning (Stripe)` initierar Stripe Checkout-session
- ordern sparar providerfält (`payment_provider`, `payment_provider_reference`, `payment_provider_session_id`, `payment_provider_status`)
- return endpoint: `GET /checkout/payment/return`
- webhook endpoint: `POST /webhooks/stripe`
- alla providerhändelser loggas i `payment_events`

Lokal verifiering (test/sandbox):
1. Sätt Stripe testnycklar i `.env`.
2. Lägg order med Stripe-metoden via `/checkout`.
3. Verifiera redirect till Stripe och återkomst till `/order-status`.
4. Skicka webhook (Stripe CLI eller dashboard) till `/webhooks/stripe` och verifiera att `payment_status` samt `payment_events` uppdateras.

## Customer accounts v1

Databas:
- kör även `database/migrations/018_customer_accounts_v1.sql`

Storefront:
- registrering: `/register`
- login: `/login`
- Mina sidor: `/account`
- orderhistorik: `/account/orders`
- profil: `/account/profile`
- adress: `/account/address`

Adresslager v1:
- användare kan spara en enkel standardadress direkt på `users` (ingen separat address-book i v1)
- checkout (`/checkout`) förifyller faktura- och leveransadress från inloggad kundprofil
- checkout är fortsatt redigerbar och ordern sparar alltid adresssnapshot i orderfält
- gästcheckout fungerar oförändrat och kräver inget kundkonto

Snabbtest lokalt:
1. Registrera nytt konto via `/register`.
2. Spara adress via `/account/address`.
3. Gå till `/checkout` i inloggat läge och verifiera att adressfälten är förifyllda.
4. Ändra adress i checkout och lägg order.
5. Verifiera att ordern syns under `/account/orders` och att orderdetalj fungerar.
6. Uppdatera profil under `/account/profile`.

## Returns / RMA v1

Databas:
- kör även `database/migrations/020_returns_rma_v1.sql`

Storefront (inloggad kund):
- `/account/returns` listar kundens returärenden
- från `/account/orders/{id}` kan kund skapa retur via `/account/orders/{id}/returns/create`

Admin:
- `/admin/returns` listar returärenden med statusfilter
- `/admin/returns/{id}` visar detalj, historik, statusactions och adminnotering

Snabbtest lokalt:
1. Logga in som kund med en order.
2. Öppna orderdetalj och skapa retur med minst en orderrad.
3. Verifiera att retur syns i `/account/returns` och i `/admin/returns`.
4. Uppdatera status i admin och verifiera historikrader.

## Support / contact cases v1

Databas:
- kör även `database/migrations/021_support_contact_cases_v1.sql`

Storefront:
- kontaktformulär: `/contact`
- mina supportärenden: `/account/support-cases`
- skapa ärende via konto: `/account/support-cases/create`
- skapa orderkopplat ärende från orderdetalj: `/account/orders/{id}/support/create`

Admin:
- `/admin/support-cases` listar ärenden med filter på status/källa
- `/admin/support-cases/{id}` visar detalj, historik, status-actions, prioritet och intern adminnotering
- `/admin/orders/{id}` visar kopplade supportärenden för ordern

Snabbtest lokalt:
1. Öppna `/contact` och skapa ett gästärende.
2. Logga in som kund och skapa ärende via `/account/support-cases/create`.
3. Skapa ett orderkopplat ärende från `/account/orders/{id}`.
4. Verifiera att kunden ser egna ärenden under `/account/support-cases`.
5. Öppna `/admin/support-cases`, filtrera på status, uppdatera status/prioritet och spara adminnotering.
6. Verifiera historikrader på admin-detaljen samt att adminnotering inte visas på kundens case-detalj.

## B2B light / company fields v1

Databas:
- kör även `database/migrations/022_b2b_light_company_fields_v1.sql`

Lokal manuell test:
1. Logga in som kund och öppna `/account/profile`.
2. Spara `Företagsnamn`, `Organisationsnummer` och `VAT-nummer`.
3. Gå till `/checkout` och verifiera att företagsfälten är förifyllda för inloggad kund men valfria.
4. Lägg order och verifiera i `/checkout/confirmation` eller `/order-status` att företagsuppgifter visas när de finns.
5. Öppna `/admin/orders/{id}` och verifiera att företagsuppgifter visas i kundsektionen.
6. Uppdatera kundprofilens företagsuppgifter och verifiera att redan lagd order behåller tidigare snapshot-värden.

## Search relevance / merchandising v1

Databas:
- kör även `database/migrations/023_search_merchandising_v1.sql`

Nya produktfält i v1:
- `is_search_hidden` (dölj i publik sök/listning)
- `is_featured` (lätt prioriteringssignal)
- `search_boost` (manuell sökboost)
- `sort_priority` (manuell listprioritet)

Storefront:
- publik synlighet är centraliserad till aktiva + ej dolda produkter
- sök använder enkel förklarbar relevansmodell (namn/SKU/brand/kategori + merchandising-signaler)
- kategori/listning använder kuraterad standardsortering via `sort_priority`, `is_featured` och `search_boost`
- befintlig manuell sortering (namn/pris/senaste) fungerar fortsatt

Admin:
- `/admin/products/{id}/edit` har sektion för synlighet & merchandising
- `/admin/products` visar featured/hidden/boost/prioritet och kan filtrera på featured/hidden

Lokal manuell test:
1. Kör migration `023_search_merchandising_v1.sql`.
2. Öppna `/admin/products/{id}/edit` och sätt olika värden för featured/hidden/boost/prioritet.
3. Verifiera att produkter med `is_search_hidden=1` inte visas på `/search` eller i kategori-listning.
4. Sök på produktnamn/brand och verifiera att relevansordning påverkas av exact/prefix/partial match samt boost/featured.
5. Öppna en kategori och verifiera att standardordning känns kuraterad (prioritet/featured) samt att valbar sortering fortfarande fungerar.

## SEO / content scaling v1

Databas:
- kör även `database/migrations/024_seo_content_scaling_v1.sql`

Detta steg lägger till grundläggande SEO-fält för produkter, kategorier och CMS-sidor:
- `seo_title` / `meta_title`
- `seo_description` / `meta_description`
- `canonical_url`
- `meta_robots`
- `is_indexable`

Storefront använder nu central SEO-byggare med fallback-regler:
- title: explicit SEO-titel -> naturlig sidtitel -> sitenamn
- description: explicit SEO-beskrivning -> kort sanerad fallback från innehåll -> tom
- canonical: explicit canonical_url -> sidans normala URL
- robots: explicit meta_robots -> `noindex,follow` när `is_indexable=0` -> annars `index,follow`

Enkel indexeringsstrategi i v1:
- produktsidor, kategorisidor och CMS-sidor följer sina SEO-inställningar
- söksidor renderas med `noindex,follow`
- kategorisidor med sekundära filterkombinationer renderas med `noindex,follow`

Lokal snabbtest:
1. Öppna admin för produkt, kategori och CMS-sida och fyll i SEO-fält.
2. Öppna respektive storefront-sida och verifiera `<title>`, `meta description`, `canonical` och `meta robots` i sidkällan.
3. Testa en söksida (`/search?q=test`) och en filtrerad kategori, verifiera `noindex,follow`.

## Redirects / URL hygiene v1

Databas:
- kör även `database/migrations/025_redirects_url_hygiene_v1.sql`

Storefront:
- publika requests (GET/HEAD) passerar ett centralt redirect-lager tidigt i `public/index.php`.
- redirects matchas på exakt normaliserad intern path (`/foo/bar`), med 301 som standard.
- vid träff uppdateras `hit_count` och `last_hit_at`.
- om ingen aktiv redirect finns fortsätter normal routing/404.

Admin:
- `/admin/redirects` listar redirects och stöder enkel aktiv/inaktiv-filtrering.
- `/admin/redirects/create` och `/admin/redirects/{id}/edit` hanterar manuell redirectskapning/redigering.

Slug-ändringar:
- när slug ändras i produkt, kategori eller CMS-sida skapas en automatisk 301 från gammal till ny path.
- auto-redirect använder samma tabell och validering som manuella redirects.

Lokal snabbtest:
1. Kör migration `025_redirects_url_hygiene_v1.sql`.
2. Skapa en redirect i `/admin/redirects` (t.ex. `/gammal-sida` -> `/pages/om-oss`).
3. Öppna gamla URL:en och verifiera 301 till målsidan.
4. Verifiera att `hit_count` och `last_hit_at` uppdateras i adminlistan.
5. Ändra slug på produkt/kategori/CMS-sida och verifiera att gamla slug-URL:en redirectar till nya URL:en.

## Sitemap / crawl control v1

Storefront exponerar nu tekniska SEO-endpoints:

- `/robots.txt`
- `/sitemap.xml` (sitemap-index)
- `/sitemaps/products.xml`
- `/sitemaps/categories.xml`
- `/sitemaps/pages.xml`

Inkluderas i sitemap:
- publika, indexerbara produkter (`is_active = 1`, `is_search_hidden = 0`, `is_indexable = 1`, ej `noindex`)
- indexerbara kategorier (`is_indexable = 1`, ej `noindex`)
- aktiva och indexerbara CMS-sidor (`is_active = 1`, `is_indexable = 1`, ej `noindex`)
- startsidan inkluderas i `pages.xml`

Exkluderas i sitemap:
- sidor markerade som `noindex` eller `is_indexable = 0`
- sökresultat och filtrerade/parameteriserade vyer
- redirect-källor (endast kanoniska publika routes listas)

`robots.txt` hålls enkel i v1 och:
- pekar ut `Sitemap: <base>/sitemap.xml`
- blockerar tydligt sekundära flöden (`/admin`, `/search`, `/cart`, `/checkout`, `/account`)

### Lokal testning

1. Starta appen:
   ```bash
   composer serve
   ```
2. Verifiera robots:
   ```bash
   curl -i http://127.0.0.1:8000/robots.txt
   ```
3. Verifiera sitemap-index:
   ```bash
   curl -i http://127.0.0.1:8000/sitemap.xml
   ```
4. Verifiera del-sitemaps:
   ```bash
   curl -i http://127.0.0.1:8000/sitemaps/products.xml
   curl -i http://127.0.0.1:8000/sitemaps/categories.xml
   curl -i http://127.0.0.1:8000/sitemaps/pages.xml
   ```

## Reviews / ratings v1

Databas:
- kör även `database/migrations/026_reviews_ratings_v1.sql`

Storefront:
- produktsidan visar nu snittbetyg och antal recensioner baserat på `approved`
- endast inloggad kund kan lämna recension
- ny recension skapas alltid som `pending`
- recension markeras som `is_verified_purchase = 1` endast när kunden har en verklig orderrad på produkten

Admin:
- `/admin/reviews` listar recensioner med filtrering på status och produkt-id
- `/admin/reviews/{id}` visar detalj och låter admin sätta status till:
  - `pending`
  - `approved`
  - `rejected`
  - `hidden`
- endast `approved` visas publikt i storefront

Summering:
- `products.review_count` och `products.average_rating` uppdateras centralt i review-servicen när recension skapas/modereras
- summeringen räknas alltid på `approved` recensioner

Lokal testning:
1. Logga in som kund.
2. Öppna en produkt och skicka recension via formuläret.
3. Verifiera att kunden ser meddelandet "inväntar granskning".
4. Öppna admin `/admin/reviews`, godkänn recensionen.
5. Gå tillbaka till produktsidan och verifiera att recension samt uppdaterat snittbetyg visas.

## Related products / cross-sell v1

Databas:
- kör även `database/migrations/027_related_products_cross_sell_v1.sql`

Relationstyper i v1:
- `related`
- `cross_sell`

Admin:
- hanteras i produktens editvy (`/admin/products/{id}/edit`) i sektionen `Relaterade produkter / cross-sell`
- admin kan lägga till koppling genom att välja produkt, relationstyp, sortering och aktiv/inaktiv
- admin kan uppdatera relationstyp/sortering/aktiv-status eller ta bort koppling

Storefront:
- produktsidan visar `Relaterade produkter` och `Passar bra med`
- manuella kopplingar prioriteras
- fallback används för att fylla ut upp till rimligt antal när manuella kopplingar saknas

Fallbacklogik i v1:
- exkluderar aktuell produkt
- använder endast publika/synliga produkter (`is_active = 1`, `is_search_hidden = 0`)
- prioriterar först samma kategori + brand
- fyller vid behov med samma kategori
- använder enkel, stabil sortering med befintliga signals (`stock_status`, `sort_priority`, `is_featured`, `search_boost`, `updated_at`)

Lokal testning:
1. Kör migration `027_related_products_cross_sell_v1.sql`.
2. Öppna en produkt i admin och lägg till minst en `related` och en `cross_sell`.
3. Verifiera på produktsidan att sektionerna visas och att produkter är klickbara.
4. Inaktivera eller ta bort manuella kopplingar och verifiera att fallback visas.
5. Markera en relaterad produkt som inaktiv eller sökdold och verifiera att den inte visas i storefront.

## Wishlist / saved products v1

Databas:
- kör även `database/migrations/028_wishlist_saved_products_v1.sql`

Storefront:
- endast inloggad kund kan spara produkter i wishlist
- produktsidan visar nu tydlig action för `Spara produkt` / `Ta bort från sparade`
- om kunden inte är inloggad visas länk till login med retur till aktuell produktsida
- wishlist hanteras serverrenderat via POST-actions (`/wishlist/items` och `/wishlist/items/remove`)

Mina sidor:
- ny sida: `/account/wishlist` (Mina sparade produkter)
- dashboard i Mina sidor har nu en tydlig länk till sparade produkter
- wishlist-vyn visar endast publika/synliga produkter (`is_active = 1` och `is_search_hidden = 0`)
- dolda/inaktiva produkter ligger kvar i databasen men exponeras inte i kundvyn i v1

Dubbletter:
- tabellen har unik constraint på `(user_id, product_id)`
- add-flödet använder central wishlist-service/repository och skapar inte dubbletter

Lokal testning:
1. Kör migration `028_wishlist_saved_products_v1.sql`.
2. Logga in som kund och öppna en produktsida.
3. Klicka `Spara produkt` och verifiera att status ändras till `Ta bort från sparade`.
4. Öppna `/account/wishlist` och verifiera att produkten visas.
5. Klicka `Ta bort från sparade` i produktsida eller wishlist-sida och verifiera att produkten försvinner.
6. Markera en sparad produkt som inaktiv eller sökdold i admin och verifiera att den inte längre visas i `/account/wishlist`.

## Stock alerts / back-in-stock v1

Databas:
- kör även `database/migrations/029_stock_alerts_back_in_stock_v1.sql`

Storefront:
- produktsida visar nu "Bevaka produkt" när en produkt inte är köpbar
- både gäst och inloggad kund kan registrera e-post för bevakning
- inloggad kund får e-post förifylld
- aktiv dubblett för samma `product + email` stoppas centralt

Notifiering:
- när produkt går från ej köpbar till köpbar triggas utskick via befintligt transactional email-lager
- utskick loggas i `email_messages` med `related_type=stock_alert_subscription` och `email_type=stock_alert_back_in_stock`
- subscription markeras `notified` först efter lyckat utskick
- misslyckat mail lämnar subscription som `active` för senare försök

Statusar:
- `active` = bevakning väntar på att produkt blir köpbar
- `notified` = notifieringsmail skickat
- `unsubscribed` = avslutad av kund

Mina sidor:
- `/account/stock-alerts` visar kundens bevakningar och status
- aktiv bevakning kan avslutas manuellt

Lokal testning (manuell):
1. Sätt en produkt till ej köpbar (t.ex. `out_of_stock` eller `stock_quantity=0`).
2. Öppna produktsidan och skapa bevakning med e-post.
3. Verifiera att ny rad finns i `stock_alert_subscriptions` med `status=active`.
4. Ändra lagret så produkten blir köpbar igen via adminflödet.
5. Verifiera att mailrad skapas i `email_messages` och att subscription går till `notified` vid lyckat utskick.
6. Testa att samma subscription inte skickas igen utan ny/återaktiverad bevakning.

## Recently viewed products v1

Storefront:
- produktsidan (`/product/{slug}`) registrerar nu visad produkt i en central `RecentViewedService`
- historiken är sessionbaserad (gäller både gäst och inloggad användare i samma session)
- lagringen är en ordnad lista av `product_id` med senaste först, deduplicering och max 12 poster
- sektionen **"Nyligen visade produkter"** visas på produktsidan när det finns underlag
- aktuell produkt exkluderas från sektionen på sin egen produktsida
- endast publika produkter visas (`is_active = 1` och `is_search_hidden = 0`)

Lokal testning:
1. Starta applikationen (`composer serve`).
2. Öppna flera produktsidor i följd.
3. Gå tillbaka till en produktsida och verifiera att sektionen "Nyligen visade produkter" visas.
4. Verifiera att aktuell produkt inte visas i sin egen lista.
5. Verifiera att inaktiva/sökdolda produkter inte visas i listan även om de tidigare besökts.

## Compare products v1

Storefront:
- jämförelse är nu sessionbaserad för både gäster och inloggade kunder (ingen kontosynk i v1)
- central logik finns i `CompareService` med:
  - add/remove/contains/list
  - deduplicering
  - stabil ordning enligt inläggningsordning
  - maxgräns `4` produkter
  - filtrering till publika/synliga produkter vid rendering
- produktsidan visar `Jämför produkt` eller `Ta bort från jämförelse` beroende på compare-state
- compare-sidan finns på `/compare` och visar produkter sida vid sida
- compare-vyn visar grundfält från befintlig produktmodell:
  - namn
  - bild
  - pris
  - varumärke
  - lager/köpbarhet
  - betyg + antal recensioner
  - kort beskrivning

Rensning/filtrering:
- produkter som inte längre är publika/synliga filtreras bort automatiskt från compare-vyn
- compare-vyn kraschar inte om en produkt i sessionen inte längre kan visas

Lokal testning:
1. Starta applikationen (`composer serve`).
2. Öppna en produktsida och klicka `Jämför produkt`.
3. Verifiera att knappen byts till `Ta bort från jämförelse` och att länken till `/compare` visar uppdaterat antal.
4. Öppna `/compare` och verifiera sida-vid-sida-tabellen.
5. Lägg till upp till fyra produkter och verifiera att ordningen följer inläggningsordning.
6. Försök lägga till en femte produkt och verifiera tydligt felmeddelande.
7. Ta bort en produkt från `/compare` och verifiera att tabellen uppdateras.
8. Markera en jämförd produkt som inaktiv eller sökdold i admin och verifiera att den inte längre visas i `/compare`.

## Inbound receiving / stock receipt v1

Databas:
- kör även `database/migrations/034_inbound_receiving_stock_receipt_v1.sql`

Admin:
- `/admin/purchase-order-drafts` visar nu både utkaststatus och mottagningsstatus
- filter finns för `receiving_status` (`not_received`, `partially_received`, `received`, `cancelled`)
- draftdetaljen visar per rad: beställt, mottaget, kvar och radstatus
- inleverans registreras på exporterade utkast via formulär på draftdetaljen

Receiving-flöde i v1:
1. Skapa inköpsutkast från restock-behov.
2. Markera utkastet som exporterat.
3. Öppna utkastet och ange mottaget antal per rad (0 tillåtet för rader som inte levererats ännu).
4. Spara inleveransen.

Vad systemet gör:
- validerar att mottagna kvantiteter är heltal >= 0
- blockerar övermottagning mot beställt antal
- uppdaterar `received_quantity` och `last_received_at` på draft-rader
- uppdaterar draftens `receiving_status` och vid full mottagning även `received_at`
- skapar central lageruppdatering i `products.stock_quantity`
- loggar lagerrörelse i `inventory_stock_movements` med `movement_type = purchase_receipt`
- sparar mottagningshändelser i `purchase_order_receipts` + `purchase_order_receipt_items`

Lokal manuell test:
1. Kör migrationer inkl. `034_inbound_receiving_stock_receipt_v1.sql`.
2. Skapa ett inköpsutkast med minst en rad som har produktkoppling.
3. Markera utkastet som exporterat.
4. Registrera en delmottagning och verifiera:
   - draft får `receiving_status = partially_received`
   - raden får uppdaterad `received_quantity`
   - lagersaldo ökar
   - `inventory_stock_movements` får en `purchase_receipt`.
5. Registrera återstående kvantitet och verifiera:
   - draft får `receiving_status = received`
   - `received_at` sätts.

## Fitment / YMM v1

Databas:
- kör även `database/migrations/035_fitment_ymm_v1.sql`

Detta ingår i v1:
- `vehicles` för enkel fordonsmodell (make, model, generation, engine, årsintervall, aktiv/sortering)
- `product_fitments` för produkt ↔ fordon-koppling med `fitment_type` (`confirmed`, `universal`, `unknown`)
- sessionbaserad vald bil i storefront (`selected_vehicle_id`)
- serverrenderad YMM-väljare i storefront-header
- katalogfilter med toggle: "Visa bara produkter som passar vald bil"
- fitment-signal på produktsidan (passar / universell / ej bekräftad)
- admin för fordon på `/admin/vehicles`
- produktadmin för fordonskopplingar på `/admin/products/{id}/edit#fitment`

Lokal snabbtest:
1. Kör migration `035_fitment_ymm_v1.sql`.
2. Skapa ett eller flera fordon i `/admin/vehicles`.
3. Öppna en produkt i admin och lägg till fitment-rader under sektionen `Fitment / Fordonskopplingar`.
4. I storefront, välj bil i YMM-sektionen högst upp.
5. Verifiera i `/search` eller kategori att toggle för passande produkter fungerar.
6. Öppna en produkt och kontrollera fitment-signalen för vald bil.

## Supplier fitment intake / review v1

Databas:
- kör även `database/migrations/037_supplier_fitment_intake_review_v1.sql`
- kör även `database/migrations/039_fitment_import_mapping_v1.sql`

Admin:
- `/admin/supplier-fitment-review`

Vad som ingår i v1:
- nytt reviewbart underlag i `supplier_fitment_candidates` kopplat till `supplier_items`
- kandidater lagrar råfält + normaliserade fält för mappinginsyn (`normalized_make`, `normalized_model`, `normalized_generation`, `normalized_engine`)
- central `SupplierFitmentMappingService` normaliserar raw-fält (trim/case/separatorer) innan lookup
- defensiv safe-match mot aktiva `vehicles` (ingen fuzzy/AI):
  - kräver alltid make + model
  - stödjer exakta kombinationer med generation/engine när de finns
  - stödjer årintervall endast när kandidatens intervall tydligt ryms inom vehicle-postens intervall
  - sätter `matched_vehicle_id` endast när exakt en säker träff återstår
- enkel mappinginsyn sparas via `mapping_source` + `mapping_note`
- confidence label i detta steg: `exact` eller `unknown`
- statusflöde: `pending`, `approved`, `rejected`, `skipped`
- snabb intake-väg i admin (för intern test/debug)
- reviewkö med filter för status, matchat/omatchat fordon, utan produktkoppling och leverantör

Reviewregler i v1:
- godkännande kräver produktkoppling och vehicle-id
- vid godkännande skapas `product_fitments` (typ `confirmed`) först efter review
- dubblett mellan produkt och fordon undviks centralt innan ny fitment skapas
- rejected/skipped skapar inte publika fitments

Lokal snabbtest:
1. Kör migrationer via `php scripts/migrate.php` och applicera `037_supplier_fitment_intake_review_v1.sql` + `039_fitment_import_mapping_v1.sql` i MariaDB.
2. Skapa en kandidat via `/admin/supplier-fitment-review` (sektionen `Snabb intake`).
3. Verifiera att kandidaten syns med status `pending`, normaliserade fält och mappingtext i kön.
4. Verifiera att endast säkra träffar får `matched_vehicle_id` + confidence `exact`; övriga ska visa `unknown`.
5. Sätt/justera `Vehicle ID` och godkänn kandidaten.
6. Öppna produkten i `/admin/products/{id}/edit#fitment` och verifiera att fitmentkopplingen skapats.
7. Testa även `Avvisa`/`Skippa` och verifiera att ingen ny `product_fitments` skapas.

## Fitment-aware storefront polish v1

Detta steg bygger vidare på YMM/fitment-kärnan och gör passform tydligare i hela köpresan utan ny tung frontend eller ny datamodell.

Vad som ingår i v1:
- tydligare aktiv bil i storefront-header med snabb byta/rensa-upplevelse
- tydligare fitmentfilter-kontext i sök- och kategorivyer (inkl. mänsklig filterstatus)
- enkla fitmentbadges på produktkort när aktiv bil finns (`Passar vald bil`, `Universell`, `Passform ej bekräftad`)
- tydligare passformsignal på produktsidan med defensiv text
- lätt koppling till Mina bilar när kund är inloggad
- centraliserad storefront-logik för fitmentsignaler/payload i service-lagret

Lokal snabbtest:
1. Välj aktiv bil i YMM i storefront-header.
2. Öppna `/search` och `/category/{slug}` och verifiera fitmentkontext + filterstatus.
3. Aktivera/inaktivera `Passar vald bil`-filtret och verifiera att aktiv filtersammanfattning uppdateras.
4. Verifiera produktkortens fitmentbadge i listvy (confirmed/universal/unknown).
5. Öppna en produktsida och verifiera tydlig passformsignal för vald bil.
6. Logga in och verifiera att `Mina bilar`-genväg visas när ingen aktiv bil är vald.

## Mina bilar / saved vehicles v1

Databas:
- kör även `database/migrations/038_saved_vehicles_my_garage_v1.sql`

Storefront/kundkonto:
- inloggad kund kan spara aktuell vald YMM-bil via knappen `Spara vald bil` i YMM-sektionen.
- kundens sparade bilar visas på `/account/vehicles`.
- kund kan:
  - använda en sparad bil som aktiv YMM-bil i session
  - sätta en sparad aktiv bil som primär
  - ta bort sparad bil

Regler i v1:
- `saved vehicle` (konto-data) och `aktiv bil` (session-data) hålls separerade.
- samma bil kan inte sparas flera gånger för samma kund (`UNIQUE(user_id, vehicle_id)`).
- endast aktiva fordon kan sparas, användas och sättas som primär.
- om primär bil finns och ingen aktiv bil redan är vald i sessionen används primär bil som enkel default i storefront.

Lokal snabbtest:
1. Logga in som kund.
2. Välj bil i YMM-väljaren och klicka `Spara vald bil`.
3. Öppna `/account/vehicles` och verifiera att bilen syns.
4. Klicka `Sätt som primär` och ladda om en storefront-sida med tom YMM-session för att verifiera defaultval.
5. Klicka `Använd denna bil` för att sätta aktiv bil i session.
6. Klicka `Ta bort` och verifiera att bilen försvinner från listan (utan att fordonet tas bort ur `vehicles`).

## Fitment-driven category entry / vehicle-first navigation v1

Detta steg gör storefronten tydligare för kunder som vill börja handla utifrån vald bil, utan ny tung frontend eller separat navigationsmotor.

Vad som ingår i v1:
- ny serverrenderad sida `/shop-by-vehicle` som listar publika kategorier med aktiv bil i kontext.
- central `VehicleNavigationService` som bygger navigation-payload, kategorilänkar och fallback när ingen bil är vald.
- tydlig CTA `Handla till vald bil` i YMM/header när aktiv bil finns.
- startsidan visar en lätt vehicle-first-entry med kategorigenvägar eller mjuk uppmaning att välja bil.
- `Mina bilar` har genväg för att direkt gå till shoppingflöde för vald/sparad bil.
- kategoriingång återanvänder befintliga query params (`fitment_only`, `fitment_vehicle_id`) och befintlig katalog-/fitmentlogik.

Regler i v1:
- ingen bil vald => storefront fungerar som vanligt och visar endast mjuk prompt.
- endast publika/synliga kategorier och produkter används i entry-listor.
- inga påståenden om bekräftad passform görs utan befintlig fitmentdata.

Lokal snabbtest:
1. Välj bil i YMM och verifiera CTA `Handla till vald bil` i header.
2. Öppna `/shop-by-vehicle` och verifiera att kategorilänkar inkluderar fitmentkontext.
3. Klicka in i en kategori från sidan och verifiera att befintlig fitmentfilter-UI fortsätter fungera.
4. Rensa vald bil och verifiera att `/shop-by-vehicle` visar mjuk prompt istället för blockerande flöde.
5. Logga in, gå till `/account/vehicles`, klicka `Handla till denna bil` och verifiera att bil kontext sätts och att du hamnar i vehicle-first-ingången.

## Fitment coverage visibility / category coverage signals v1

Detta steg lägger ett första, lättviktigt lager för coverage-synlighet i både storefront och admin utan ny tung datamodell eller analyticsplattform.

Vad som ingår i v1:
- central `FitmentCoverageService` för coverage-beräkning och payloadbygge.
- vehicle-first-ingångar (`/shop-by-vehicle` och startsidans fordonssektion) visar kategori-signaler med antal matchande produkter för aktiv bil.
- kategorisida i aktiv bil-kontext (med `fitment_only=1`) visar enkel hjälpsignal med antal matchande produkter i kategorin.
- ny adminyta `/admin/fitment-coverage` med enkel översikt per kategori:
  - antal publika produkter
  - antal med minst en `confirmed`/`universal` fitmentkoppling
  - antal utan sådan fitmentkoppling
  - enkel coverage-procent/signal
- admin kan sortera (sämst/bäst coverage), filtrera på kategorier med saknad fitment och hoppa vidare till fitment-arbetskön.

Coverage-definition i v1:
- Storefront coverage (aktiv bil): antal **publika/synliga** produkter i kategori som matchar
  - `confirmed` mot vald bil, eller
  - `universal`.
- Admin coverage per kategori: antal **publika/synliga** produkter med minst en `confirmed`/`universal`-koppling kontra antal utan sådan koppling.
- Coverage visas som vägledning och lovar inte fullständig fordonskompatibilitet i absolut mening.

Lokal snabbtest:
1. Välj en aktiv bil i YMM.
2. Öppna `/shop-by-vehicle` och verifiera kategori-signaler med matchande produktantal.
3. Öppna startsidan och verifiera att vehicle-first-kategorilänkar visar matchantal i aktiv bil-kontext.
4. Öppna en kategori via vehicle-first-länk och verifiera hjälpsignalen "X produkter matchar vald bil...".
5. Öppna `/admin/fitment-coverage` och verifiera coverage-tabell, sortering/filter och länk till fitment-kön.

## Fitment gap queue / missing coverage worklist v1

Detta steg lägger ett första operativt lager för att göra fitment-gap handlingsbara i admin utan att bygga dashboard- eller task management-plattform.

Vad som ingår i v1:
- ny adminvy `/admin/fitment-gaps` för konkret arbetskö på produktnivå.
- central `FitmentGapService` som bygger kö-payload, filter, signaler, enkel prioritering och totalsiffror.
- återanvändning av befintliga signaler från:
  - `product_fitments`
  - `fitment_flags`
  - `supplier_fitment_candidates`
  - kategori-coverage från befintlig coverage-logik.
- varje produkt kan få flera gap-signaler samtidigt:
  - `no_fitment_links`
  - `universal_only`
  - `pending_supplier_candidates`
  - `needs_review_flag`
  - `category_low_coverage`
- filter i kön för signal, sök, brand och kategori.
- enkel prioritering i vyn: flest gap-signaler först, sedan pending supplier-underlag, sedan lägst fitment count.
- tydliga åtgärdslänkar per rad till produktadmin, fitment workflow och supplier fitment review.

Skillnad mot coverage-vyn:
- `/admin/fitment-coverage` visar coverage per kategori/segment.
- `/admin/fitment-gaps` visar konkreta produkter att arbeta med, inklusive förklarade orsaker.

Lokal snabbtest:
1. Kör migrationer (`php scripts/migrate.php`) och säkerställ att fitment-tabeller från tidigare steg finns.
2. Öppna `/admin/fitment-gaps`.
3. Verifiera totalsiffror och att produkter med saknad fitment/universal-only/pending/needs review visas med tydliga orsaker.
4. Testa filter för gap-signal, brand, kategori och sök.
5. Verifiera åtgärdslänkar till produktadmin, `/admin/fitment-workflow` och `/admin/supplier-fitment-review`.

## AI URL product import v1 + supplier-specific parsers v1 (admin)

Databas:
- kör även `database/migrations/040_ai_url_product_import_v1.sql`
- kör även `database/migrations/041_supplier_specific_product_parsers_v1.sql`
- kör även `database/migrations/042_ai_draft_catalog_handoff_v1.sql`

Admin:
- `/admin/ai-product-import` visar URL-form + lista över importutkast
- `/admin/ai-product-import/{id}` visar detalj/review för utkastet

Flöde i v1:
1. Klistra in en URL i admin.
2. Systemet hämtar sidan och identifierar domän.
3. Om domänen matchar en känd supplier-parser används parser först.
4. Om parsern hittar tillräcklig data skapas utkast via parser (och vid behov komplettering med AI).
5. Om parser saknas eller parsern fallerar går URL:en via befintlig generisk extract + AI-structuring.
6. Resultatet sparas alltid i `ai_product_import_drafts` som granskningsutkast (review-first).
7. Efter manuell review kan admin köra handoff via `Skapa produktutkast` i draft-detaljen.
8. Handoff skapar ett inaktivt produktutkast i befintligt produkt/admin-flöde och markerar draftet som handoffat/imported.
9. Fortsatt artikelvård sker i ordinarie produktflöde (t.ex. `/admin/products/{id}/edit` och `/admin/products/article-care`).

Stödda parserdomäner i denna version:
- `akrapovic.com`
- `garrettmotion.com`
- `hks-power.co.jp`

Spårbarhet i utkast:
- `parser_key`
- `parser_version`
- `extraction_strategy`
- rådata (`import_raw_text`) och AI/parsers payload (`ai_structured_payload`)
- handoffmetadata: `handed_off_at`, `handed_off_by_user_id`, `handoff_target_type`, `handoff_target_id`

Spårbarhet i målflödet:
- produkter skapade via handoff får `products.source_type = ai_url_import`
- `products.source_reference_id` pekar på draft-id
- `products.source_url` sparar käll-URL och visas i produktadmin

Viktigt:
- ingen autopublicering till live-katalog
- ingen autonom produktskapning utan manuell review/handoff
- fallback till generisk pipeline sker defensivt om supplier-parser inte ger tillräckligt underlag
- exakt en handoff per draft i v1 (dubblettskydd)

Lokal snabbtest:
1. Starta appen (`composer serve`).
2. Öppna `/admin/ai-product-import`.
3. Importera en URL från stödd domän och verifiera att strategi/parser visas i detaljvyn.
4. Importera en URL från okänd domän och verifiera att strategin blir generisk AI-import.
5. Markera draft som `reviewed`, kör `Skapa produktutkast` och verifiera att produktutkast skapas.
6. Verifiera i draft-vyn att handofftid/mål visas och att samma draft inte kan handoffas igen.
7. Öppna skapad produkt i `/admin/products/{id}/edit` och verifiera källa (`AI URL-importutkast`) samt fortsatt manuell artikelvård.
