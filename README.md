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
