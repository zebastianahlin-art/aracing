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

Admin:
- `/admin/purchasing` visar produkter med påfyllnadsbehov
- `/admin/purchase-lists` listar manuella inköpsunderlag
- `/admin/purchase-lists/{id}` visar detalj med rader, status, anteckning och vald kvantitet

Lokal manuell test:
1. Säkerställ att produkter har primär leverantörskoppling och lågt/null lager.
2. Öppna `/admin/purchasing`, markera rader och skapa ett underlag.
3. Öppna underlaget via `/admin/purchase-lists` och justera `selected_quantity` per rad.
4. Uppdatera status (`draft/reviewed/exported`) samt anteckning i detaljvyn.


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
- `/admin/cms/home` styr startsidans sektioner (hero, intro, featured products, featured categories, info)

Storefront:
- `/` renderar startsidessektioner från CMS-data
- `/pages/{slug}` renderar aktiva informationssidor

Lokal manuell test:
1. Skapa en sida i `/admin/cms/pages` (t.ex. slug `kopvillkor`) och sätt den aktiv.
2. Öppna `/pages/kopvillkor` och verifiera innehåll.
3. Öppna `/admin/cms/home` och aktivera `hero` + `intro` + `featured_products`.
4. Ange produkt-ID:n i `featured_products` som kommaseparerad lista och spara.
5. Öppna `/` och verifiera att sektionerna visas enligt admininställning.

## Storefront sök + filtrering v1

Ny route:
- `/search`

Stöd i v1:
- sök på produktnamn, SKU och varumärke
- filter på kategori, varumärke, min/max-pris och lagerstatus
- sortering: senaste, namn A-Ö/Ö-A, pris stigande/fallande
- kategori-sidor använder samma listningslogik med filter/sortering

Konsekvent regel för prislösa produkter i listning:
- produkter utan `sale_price` visas i listning med texten `Pris visas vid förfrågan`
- endast aktiva produkter (`is_active = 1`) visas i storefront-listningar

Lokal manuell test:
1. Starta servern med `composer serve`.
2. Öppna `/search` och sök på namn, SKU och varumärke.
3. Testa filterkombinationer (kategori, brand, pris, lagerstatus) samt sortering.
4. Öppna en `/category/{slug}` och verifiera träffräknare, filter/sortering och tomt-resultat-läge.

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
