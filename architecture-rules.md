# A-Racing Architecture Rules

Detta dokument uttrycker bindande arkitekturregler för projektet.

Vid konflikt gäller:
1. masterplan i `/docs/masterplan`
2. detta dokument
3. `codex.md`
4. övrig dokumentation

## 1. Arkitekturform

A-Racing byggs som en modulär monolit.

Det betyder:
- ett gemensamt system
- gemensam deployment
- gemensam databasbas
- tydligt separerade moduler
- inga microservices i denna fas

Motiv:
- snabbare och säkrare utveckling
- enklare drift på VPS
- tydligare felsökning
- bättre kontroll i ett tidigt växande projekt

## 2. Grundlager

Projektet ska organiseras i följande huvudlager:

### Core
Teknisk kärna:
- bootstrap
- config
- request/response
- routing
- felhantering
- logger
- databasanslutning
- cache/adapters
- CLI-grund

### Shared
Gemensamma byggstenar:
- generella DTO:er
- value objects
- gemensamma contracts
- små hjälpklasser utan affärsläckage

### Modules
Affärsområden:
- Product
- Category
- Brand
- Storefront
- Cart
- Checkout
- Supplier
- Import
- Pricing
- Inventory
- Order
- Dashboard
- flera enligt roadmap

### Presentation
Serverrenderade views/templates och tillhörande assets.

### Persistence/Integration
Databas, importflöden, API-adaptrar och externa integrationer.

## 3. Modulprincip

Varje affärsområde ska byggas som egen modul.

Rekommenderad intern modulstruktur:

```text
ModuleName/
  Controllers/
  Services/
  Repositories/
  Domain/
  DTO/
  Requests/
  Views/
  Providers/
  Tests/
