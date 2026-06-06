# Raaminzicht — project-instructies

Klant-website opgezet met de `new-website`-skill (Laravel 13 + Filament v5 +
TALL-stack). De architectuur- en conventie-context staat in de skill en in de
globale website-context; hieronder enkel wat projectspecifiek is.

## Project-keuzes

- **Admin-UI taal:** Nederlands.
- **Meertalig:** nee — één locale (`nl`).
- **Klant-accounts:** nee — Filament (`/admin`) is het enige login-systeem.
- **Database:** MySQL (lokaal via Herd op `127.0.0.1`, user `root`, geen
  wachtwoord). Dev-DB `raaminzicht`, test-DB `raaminzicht_test`.
- **Merkkleur/lettertype ("Patrijspoort"-palet):** afgeleid van het scheeps­
  patrijspoort-logo. `primary` = diep **petrol/zeeglas-blauw** (`#286872` = 600,
  glaskern), `accent` = **messing/goud** (`#cda43c` = 400, poortring, spaarzaam),
  plus warme `sand`-neutralen — alle in `resources/css/app.css` (`@theme`).
  Filament panel-kleur (`AdminPanelProvider::colors`) staat op `#286872`.
  Koppen in **Fraunces** (serif), tekst in **Inter** (via Google Fonts in
  `layouts/site.blade.php`). `SectionBackground` is op deze schaal afgestemd.
- **Hosting / deploy-target:** nog niet bepaald (geen automatische deploy).

### Placeholders nog te vervangen door echt materiaal
- **Foto's** in `public/images/placeholders/` (rechtenvrij) → eigen projectfoto's
  via de media-library. Zwakste: zonwering-kaart + enkele realisaties.
- **Partnerlogo's** in `public/images/placeholders/logos/*.svg` zijn tekst-
  placeholders (Reynaers, Renson, Schüco, Velux, Aliplast) → echte logo's, en
  bevestig of dit de juiste merken zijn.
- **Formulier-mail**: `LeadForm` mailt naar `info@raaminzicht.be` (uit de
  Footer-settings) en slaat elke inzending op in de `leads`-tabel. Zet in prod
  een echte `MAIL_MAILER` (nu `smtp` met dummy-from); mailfouten worden enkel
  gelogd, de lead gaat nooit verloren.

## Stack & structuur

- Admin op `/admin` (Filament), sidebar-groep **Website**: Pagina's, Media,
  Menu's, Redirects, Header, Footer.
- Publieke site: Blade + Livewire + Alpine, server-side gerenderd, catch-all
  route → `PublicPageController`.
- Pagina-builder: secties als herordenbare blokken. Een **nieuw sectietype** =
  drie plekken:
  1. `resources/views/components/site/sections/<type-met-streepjes>.blade.php`
  2. `app/Filament/Schemas/Sections/<Type>Fields.php` (`static make(): array`)
  3. een `Block::make('<type_snake_case>')` in `PageSectionsBuilder::blocks()`
- **Sectietypes:** hero (met `height` groot/compact + `highlights`-chips),
  partners (logo-strip), text_media, cards (icon óf image), gallery (met
  Alpine-lightbox), reviews (testimonials + score), faq, formulier, cta.
- **Gedeelde frontend-primitives:** `<x-site.picture>` (WebP+JPG via
  `WebsiteMedia` of lokale sibling-detectie), `<x-site.btn>` (primary/secondary/
  ghost), `<x-site.section-heading>` (eyebrow/titel/intro).
- **Formulier** = `formulier`-sectie (`FormulierFields`: type offerte/contact/
  beide + onderwerpen + zijbalk) die de Livewire-component `App\Livewire\LeadForm`
  rendert (validatie NL, opslaan in `leads`, mailen via `LeadReceived`).

## Eerste admin-user

- E-mail: `pieter@dewebgoeroe.be` — rol **Admin**. Tijdelijk wachtwoord is bij
  de scaffolding meegedeeld in de chat; wijzig het na de eerste login.

## Volgende stappen

- Placeholders vervangen (zie hierboven: foto's, partnerlogo's, mail/SMTP).
- Hosting/deploy-target bepalen (deploy-stap komt ná stap 9 van de "klaar"-flow).
- Optioneel: kaart-embed op de contactpagina, echte Google-reviews koppelen.

## Lokaal draaien

```bash
php artisan serve         # of via Herd op het .test-domein
npm run dev               # Vite dev-server (hot reload)
./vendor/bin/pest         # tests (draaien tegen raaminzicht_test)
```
