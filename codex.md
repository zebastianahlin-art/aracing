
## `codex.md`

```md
# Codex-regler för A-Racing

Detta dokument styr hur Codex ska arbeta i detta repo.

Följ alltid dessa regler strikt. Vid konflikt gäller masterplanen i `/docs/masterplan` först.

## 1. Roll

Du agerar som kodmotor för A-Racing.

Du ska:
- implementera enligt spec
- respektera modulgränser
- leverera kompletta, fungerande byggblock
- undvika spontan omarkitektur
- prioritera robust kärna före smarta extrafunktioner

Du ska inte:
- hitta på ny produktstrategi
- blanda in externa affärssystem
- bygga framtida roadmap-faser för tidigt
- byta teknikspår utan uttryckligt beslut

## 2. Projektets fasta riktning

A-Racing är:

- en egenbyggd webshopplattform
- byggd i modern PHP + MariaDB
- serverrenderad i storefront
- mörk, snabb och modulär
- designad för motorsportartiklar
- byggd stegvis enligt roadmap

Admin ska fungera som ett kontrollcenter för en ensam operatör.

## 3. Teknik och drift som ska respekteras

- PHP backend
- MariaDB
- Redis
- Nginx
- serverrenderad storefront
- staging först
- produktion först när stagingblock är verifierat

Anta inte andra ramverk eller externa plattformar om det inte uttryckligen beslutas.

## 4. Prioriteringsordning

Bygg alltid i denna ordning:

1. Arkitekturskelett
2. Katalog
3. Storefront
4. Kundvagn / checkout
5. Leverantörsregister och import
6. Pris / lager / dashboard
7. Orderlogik
8. Fitment / YMM
9. AI / kvalitet / automation

Bygg inte steg 8 eller 9 innan tidigare steg fungerar.

## 5. MVP-regel

Första lanserbara versionen måste innehålla:

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
- tabellredigering

Första lanserbara versionen får inte innehålla:

- AI-URL-import
- full YMM
- AI-fitment
- sociala medier-utkast
- leverantörsbevakning

Om en uppgift riskerar att dra in dessa delar för tidigt, stoppa och håll dig till MVP.

## 6. Arkitekturregler

Bygg modulärt.

Separera:
- domänlogik
- presentation
- integrationer
- persistence

Använd denna grundprincip:

- Controllers: tunna
- Services: use cases och affärsflöden
- Repositories: datalager
- Views/templates: presentation
- Support/Shared: generiska hjälpfunktioner utan affärsläckage

Lägg inte affärslogik i:
- `public/index.php`
- routes-filer
- vyer/templates
- hjälpfunktioner som borde vara modulservices

## 7. Modulgränser

Varje större affärsområde ska ligga i egen modul under `/app/Modules`.

Exempel:
- Catalog
- Product
- Category
- Brand
- Supplier
- Import
- Pricing
- Inventory
- Order
- Dashboard

En modul får använda:
- `App\Core`
- `App\Shared`
- tydliga publika kontrakt från andra moduler

En modul får inte:
- använda annan moduls interna implementation direkt
- skriva cross-module SQL-hacks
- skapa cirkulära beroenden
- stoppa in affärslogik i generisk supportkod

## 8. Datamodellregler

Dessa regler är fasta:

- leverantörens rådata ska vara separerad från publicerad produktdata
- produkt och leverantörsartikel är olika objekt
- produktdata ska byggas som hybrid:
  - fasta kärnfält
  - attributmodell för varierande tekniska specifikationer
- import måste kunna spåras på körning och radnivå
- pris- och lagerändringar ska loggas
- framtida fitment ska vara relationsmodell, inte fri text

Bygg inte allt i en enda `products`-tabell som försöker bära hela systemet.

## 9. Admin-regel

Admin är central.

Admin får inte byggas som en passiv eftertanke.
Produktvård, lagerstatus, importöversikt och senare inköpsförslag ska optimeras för snabb vardagsanvändning.

När du bygger admin:
- prioritera läsbar datatäthet
- prioritera snabb scanning
- prioritera tydliga statusar
- prioritera bulk-arbete där det behövs
- prioritera faktisk arbetsnytta framför dekor

## 10. UI-regel

Storefront och admin ska följa denna riktning:

- mörkt tema
- racingröd accent
- tydlig lagerkommunikation
- produktdata först
- snabb och robust känsla
- mobil användbarhet från start

Designförfining får inte prioriteras före:
- import
- lager
- order
- produktvård

## 11. Spårbarhet och loggning

Inga svarta lådor.

Detta ska alltid gälla:
- importkörningar ska loggas
- importfel ska kunna granskas
- automatiska ändringar ska kunna spåras
- AI-relaterade förslag ska senare kunna granskas
- prisavvikelser ska kunna flaggas
- lageruppdateringar ska kunna förstås i efterhand

Om du bygger automation utan spårbarhet, gör om.

## 12. Säkerhets- och kvalitetsregler

- validera all input
- sanera/escapa output där relevant
- bygg säkra standarder för admin
- exponera inte känslig data i felmeddelanden
- logga fel utan att visa intern stack i produktion
- skriv inte osäkra massuppdateringar utan tydlig avgränsning
- skydda viktiga flöden mot datakorruption

## 13. Regler för leveransformat

När du levererar kod ska du:

- ge kompletta filer när större ändringar görs
- tydligt lista skapade/ändrade filer
- hålla naming konsekvent
- använda tydliga namespaces
- hålla koden läsbar och enkel att följa
- undvika halvfärdiga stubbar om inte det uttryckligen efterfrågas

När du föreslår ny kod ska du också:
- förklara syftet kort
- hålla dig till aktuell etapp
- inte dra in nästa roadmap-fas utan skäl

## 14. Regler för migreringar och dataändringar

Migreringar ska vara:
- tydliga
- säkra
- versionsstyrda
- rimligt reversibla där möjligt

När du skapar databasstruktur:
- ange index där det behövs
- ange foreign keys där det är lämpligt
- undvik otydliga statusfält
- använd konsekvent namngivning
- tänk på verkliga import- och orderfall

## 15. Tester och verifiering

Varje större byggblock ska kunna verifieras.

Minimikrav beroende på steg:
- testfall
- fixtures
- importexempel
- tydliga manuella verifieringssteg
- realistiska scenarier där det är möjligt

Importflöden, pris/lager och hybridorder måste testas mot verklighetsnära data.

## 16. Det du uttryckligen inte får göra

Du får inte:

- koppla projektet till ERP/ZYNC
- bygga WooCommerce-baserad lösning
- prioritera AI före kärnplattform
- prioritera full YMM före import, lager och order
- förstöra modulgränser för att “det går snabbare”
- byta designriktning bort från mörk/racing-orienterad baseline
- göra admin sekundär
- bygga svartlådelogik för import eller automation
- införa stora omtag utan att det först motiveras tydligt

## 17. Första konkreta arbetsuppgift

När inget annat sägs ska första huvudspåret vara:

1. sätt upp grundarkitektur
2. bygg routing, konfiguration, miljöhantering och migreringssystem
3. bygg mörkt admin/storefront-skelett
4. bygg katalogmodulen:
   - brands
   - categories
   - products
   - product_images
   - product_attributes
5. bygg enkel admin för listing och redigering
6. bygg storefront:
   - startsida
   - kategorisida
   - produktsida
   - produktkort
7. bygg kundvagn, checkout, ordermodell och grundläggande orderadmin
8. bygg därefter leverantörsregister, importprofiler och CSV-import

Påbörja inte AI eller YMM innan detta är gjort.

## 18. Beslutsregel vid osäkerhet

Om du är osäker:
- välj den enklare lösningen
- håll dig närmare masterplanen
- bygg det som ger verklig nytta först
- prioritera stabil grund framför smart teknik
- fråga inte arkitekturen om lov genom att improvisera i koden

Grundregel:
Hellre robust, tydlig och modulär kärna än tidig “intelligent” funktionalitet.
