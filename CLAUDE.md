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
- **Hosting / deploy:** Combell shared hosting, SSH `raaminzichtbe@176.62.165.220`
  (níet ssh.raaminzicht.be, dat is de oude one.com-server). App staat in
  `~/raaminzicht` (= `/data/sites/web/raaminzichtbe/raaminzicht`), docroot `~/www`
  is een echte map met symlinks naar `public/*`. Het deploy-script staat buiten
  git in `~/deploy.sh` (git pull, composer, npm ci + build via NVM, migrate,
  optimize). Preview-URL: https://raaminzichtbe.webhosting.be (het subdomein
  raaminzicht.dewebgoeroe.be geeft 404/403 en is niet meer gekoppeld); het echte
  domein www.raaminzicht.be wijst nog naar de oude WordPress-site bij one.com.
  Deploy-stap voor "klaar en deploy" (SSH vanuit Claude wordt geblokkeerd, dus de
  gebruiker draait dit zelf met `!`-prefix):
  `ssh raaminzichtbe@176.62.165.220 'bash ~/deploy.sh'` — en na content-
  migraties de bijhorende seeder(s) met `--force` (zie hieronder).
  Let op: de server heeft ooit gerebasede commits gehad; bij "diverged" eerst
  `git diff --stat` tussen de equivalente commits, dan `git reset --hard origin/main`.

### Placeholders nog te vervangen door echt materiaal
- **Projectfoto's (echt):** de echte foto's staan in
  `public/images/realisaties/<project>/` (per gemeente, 1440px WebP+JPG, EXIF/GPS
  gestript) en leven sinds 04/09/2026 in het **realisaties-post-type** (zie
  hieronder), niet meer in de galerij-secties zelf. Nieuwe projecten voegt de
  klant toe via Website → Realisaties (upload of media-library); de datafile
  `database/data/realisaties.php` is enkel nog de eenmalige import-bron.
- **Overige foto's** in `public/images/placeholders/` (rechtenvrij: hero home,
  productkaarten, over-ons) → nog te vervangen door eigen beeldmateriaal.
- **Partnerlogo's** in `public/images/placeholders/logos/*.svg` zijn tekst-
  placeholders (Aluprof, Drutex, Frager, Renson, Somfy, Soprofen, Wilms) →
  vervangen door de echte merklogo's (officiële brand-kits van de fabrikanten,
  met toestemming/volgens huisstijlrichtlijnen).
- **Contactpersonen (telefoon):** Tim (0469 79 22 40) is het hoofdnummer en staat
  overal (topbalk, footer met naam, formulier-/afspraak-zijbalken, "Bel …"-CTA's).
  Werner (zaakvoerder, 0473 52 43 49) staat enkel op de contactpagina: de
  formulier-sectie heeft daar "Alle contactpersonen tonen" aan, waardoor beide
  nummers mét naam verschijnen. Beheer in Footer-instellingen (`phone`,
  `phone_name`, `phone_2`, `phone_2_name`). De site zegt nergens meer "rechtstreeks
  met de zaakvoerder" of "geplaatst door de zaakvoerder zelf" (Werner plaatst
  niet zelf; zijn team wel, onder zijn toezicht). Eenmalige migratie van bestaande
  content: `php artisan db:seed --class=ContactInfoSeeder --force` (ook op prod).
- **Formulier-mail**: `LeadForm` mailt naar `info@raaminzicht.be` (uit de
  Footer-settings) en slaat elke inzending op in de `leads`-tabel. Zet in prod
  een echte `MAIL_MAILER` (nu `smtp` met dummy-from); mailfouten worden enkel
  gelogd, de lead gaat nooit verloren.

## Realisaties (eigen post-type)

Realisaties zijn een apart post-type i.p.v. losse foto's per galerij-sectie, zodat
één project op meerdere pagina's kan verschijnen en de klant het op één plek beheert.

- **Website → Realisaties** (`RealisatieResource`): titel, plaats, categorieën,
  optionele omschrijving, zichtbaarheid en de foto's. De rijen in de lijst zijn
  sleepbaar (`position`) — díe volgorde is meteen de volgorde op de site.
- **Foto's = één drag-and-drop galerij-veld** (`GalleryUploadField`), geen rij
  per foto: sleep een hele reeks in één keer naar binnen, herorden de miniaturen,
  eerste = cover. Elke upload gaat door `WebsiteMediaService` (WebP + JPG, max
  2400px, in de media-library); de veld-state is een platte lijst URL's.
  Let op bij wijzigen: `fetchFileInformation(false)` is nodig omdat de waarden
  URL's zijn en geen paden op de Filament-disk — zonder dat gooit Filament bij het
  laden alles weg wat hij op die disk niet vindt.
- **Alt-teksten worden niet per foto ingetypt** (dat was net het klikwerk dat
  weg moest). `ManagesRealisatiePhotos` vertaalt tussen het veld en de opslag
  `[{src, alt}]`: een foto die al een alt had houdt die (de handgeschreven
  teksten uit de import blijven dus staan), een nieuwe foto krijgt
  "Plaats — Titel". Nooit een lege alt. Wil de klant alt-teksten per foto kunnen
  bewerken, dan is daar een apart veld voor nodig.
- **Website → Realisatie-categorieën** (`RealisatieCategoryResource`): vrij aan te
  maken (Ramen en deuren, Veranda's en overkappingen, Zonwering, Rolluiken en
  poorten). Many-to-many, dus een project mag in meerdere categorieën. Nieuwe
  categorieën kunnen ook rechtstreeks vanuit het realisatie-formulier.
- **Tabellen:** `realisaties` (foto's als JSON `[{src, alt}]`),
  `realisatie_categories`, pivot `category_realisatie`.
- **Galerij-sectie** heeft een veld **Bron**: "Mijn realisaties" (standaard) of
  "Losse foto's in deze sectie" (het oude gedrag; secties zonder `source` staan
  automatisch op handmatig, dus bestaande content blijft werken). Bij realisaties
  kies je *Alle* (optioneel gefilterd op categorie) of *Zelf kiezen*, met een
  optioneel maximum. `App\Support\GalleryItems::forSection()` zet beide bronnen om
  naar dezelfde itemlijst voor de blade.
- **Productpagina's filteren op categorie**, niet op een vaste lijst: een nieuwe
  realisatie in "Veranda's" verschijnt vanzelf op /verandas.
- **Import/koppeling** (idempotent, ook op prod na een deploy):
  `php artisan db:seed --class=RealisatiesSeeder --force`. Die maakt de categorieën,
  importeert de projecten uit `database/data/realisaties.php` (enkel wat nog niet
  bestaat — admin-bewerkingen worden nooit overschreven), zet de galerij-secties op
  de gemapte pagina's op bron "realisaties" en vervangt de placeholder-hero van
  /realisaties. De pagina→set-mapping staat in `App\Support\Realisaties`.
- Let op: de oude "andere cover per pagina"-truc (`['knokke', 2]`) is weg — de
  cover is nu gewoon de eerste foto van de realisatie, herordenbaar in de admin.
- Tests: `tests/Feature/RealisatiesTest.php`, `tests/Feature/AdminSmokeTest.php`.

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
  Alpine-lightbox; put uit de realisaties of uit losse foto's), reviews (testimonials + score), faq, formulier, cta.
- **Gedeelde frontend-primitives:** `<x-site.picture>` (WebP+JPG via
  `WebsiteMedia` of lokale sibling-detectie), `<x-site.btn>` (primary/secondary/
  ghost), `<x-site.section-heading>` (eyebrow/titel/intro).
- **Formulier** = `formulier`-sectie (`FormulierFields`: type offerte/contact/
  beide + onderwerpen + zijbalk) die de Livewire-component `App\Livewire\LeadForm`
  rendert (validatie NL, opslaan in `leads`, mailen via `LeadReceived`).

## Groei-module (seo-analytics)

Sidebar-groep **Groei**: Overzicht (`SeoDashboard`), Verkeer (`SearchConsole`,
gemeten Google-verkeer via OAuth), Leads (`SeoLeads`, first-party conversies +
nulmeting), Keywords (`SeoKeywordResource`, incl. "Stel keywords voor"), Acties
(`SeoActions`, goedkeuringsdashboard) en Instellingen (`SeoSettings`). De
app-brede AI-config (Anthropic-key, merknaam, omschrijving, "Feiten voor AI")
staat op **Instellingen → Algemeen** (`GeneralSettings`); `.env`-fallback
`ANTHROPIC_API_KEY` via `config('services.anthropic.api_key')`.

- **Wekelijkse AI-briefing staat bewust UIT.** De code (`seo:weekly-report`,
  mail, actie-generatie) is voorzien, maar de cron in `routes/console.php` draait
  enkel als de schakelaar *Wekelijkse AI-briefing actief* op Groei → Instellingen
  aan staat (Setting `seo_weekly_report_enabled`). Manueel blijft alles werken:
  "Ververs cijfers", "Genereer acties nu", "Stel keywords voor".
- **Search Console**: `seo:sync-search-console` dagelijks 6:00 (eerste run =
  16 maanden backfill). Koppelen op Groei → Verkeer (Google Cloud OAuth-client,
  redirect-URI staat op die pagina; app op "In productie" zetten). Routes
  `/admin/search-console/oauth/{redirect,callback}` staan in `routes/web.php`
  vóór de catch-all.
- **Leads-meting**: de bestaande `leads`-tabel ís het conversie-grootboek. De
  migratie `2026_09_04_180000_add_attribution_to_leads_table` voegt kanaal,
  landingspagina, referrer en utm's toe; `Lead::booted()` vult die automatisch
  uit de sessie-first-touch (`Attribution` + middleware `CaptureFirstTouch` in
  de `web`-groep). Formulieren hoeven niets te doen; nieuwe conversiepunten ook
  niet zolang ze een `Lead` aanmaken. Type-labels in `Lead::TYPE_LABELS`.
  Nulmeting-velden (`seo_live_since`, `seo_goal_leads_month`,
  `seo_leads_baseline`) op het Leads-scherm.
- **Sectie-contract** van de actie-applier is afgestemd op dit project:
  `hero` → `prose` (heading + body) → `faq` → `cta`. De gekloonde CTA-knop volgt
  het `CtaLinkSchema`-contract (`link_type` + `page_id` + `href`).
- **Queue via de scheduler** (`QUEUE_CONNECTION=database`): verversen, acties en
  keyword-onderzoek zijn jobs. `routes/console.php` plant elke minuut een
  `queue:work --stop-when-empty`; op Combell staat sinds 04/09/2026 in `~/.crontab`
  een per-minuut-cron voor `schedule:run` (geverifieerd met `QueueHealthCheckJob`:
  dispatch → `Cache::get('queue_health_check')` binnen een minuut). Lokaal:
  `php artisan schedule:work` of `queue:work`.
- Migraties: `2026_06_01_1200xx_create_seo_*` + `create_gsc_*` (9 tabellen) en
  de leads-attributie-migratie — `php artisan migrate` lokaal en op prod.
- Tests: `SeoModuleTest`, `LeadAttributionTest`, `SeoLeadsPageTest`,
  `SearchConsoleTest`, `SeoKeywordSuggestTest` in `tests/Feature/`.

## Redirects van de oude site

De oude WordPress-site (www.raaminzicht.be bij one.com, Yoast-sitemap gescand op
04/09/2026) telt 891 URL's: 15 vaste pagina's en 4 × 219 gegenereerde
locatie-landingspagina's (`/ramen-en-deuren-<gemeente>/`, `/zonwering-…`,
`/verandabouw-…`, `/terrasoverkapping-…`; near-duplicate doorway pages, 8 woorden
verschil op 3380). Die worden **niet** herbouwd; ze redirecten naar de productpagina.

- **Patroon-redirects:** een `from` met `*` is een patroon ("alles wat volgt",
  minstens één teken, hoofdletter-ongevoelig). `HandleRedirects` checkt eerst
  exact (O(1) uit cache), dan patronen op aflopende lengte van `from`. Een exacte
  regel voor één gemeente wint dus altijd van `/ramen-en-deuren-*`. Geen extra
  kolom: `Redirect::isPattern()` kijkt naar het jokerteken.
- **Mapping** staat in `database/seeders/RedirectsSeeder.php` (10 exact + 4
  patronen, niet-destructief, herhaalbaar). Op prod na deploy:
  `php artisan db:seed --class=RedirectsSeeder --force`. Slugs die gelijk bleven
  (contact, over-ons, premies, realisaties) hebben geen regel nodig.
- **Productslugs op de root** (beslissing 04/09/2026): `/ramen-en-deuren`,
  `/verandas`, `/zonwering`, `/poorten`; het overzicht `/producten` blijft.
  `database/seeders/ProductSlugsSeeder.php` voert dat herhaalbaar door
  (pagina's hernoemen, hard-gecodeerde hrefs en menu-URL's herschrijven, dode
  `page_id`-links herstellen op titel, 301's van `/producten/<x>`) en roept op
  het einde de RedirectsSeeder aan zodat de oude-site-redirects meteen naar de
  nieuwe slugs wijzen. `App\Support\Realisaties::PAGE_SETS` en de HomepageSeeder
  gebruiken dezelfde root-slugs.
- **Content leeft op de server.** Slugs, teksten en menu's worden in de admin op
  de preview-server bewerkt; de lokale DB is een kopie (laatst gesynct
  04/09/2026 via mysqldump + rsync van `storage/app/public/website-media`).
  Slug-afhankelijke code (seeders, `Realisaties`) daarom tolerant houden en
  na een deploy op de preview-URL controleren, niet enkel lokaal.
- **SSH vanuit Claude** is toegestaan via `.claude/settings.local.json`
  (buiten git): deploys, dumps en seeders op de server hoeven niet meer via de
  gebruiker. Commando's moeten letterlijk met `ssh raaminzichtbe@176.62.165.220`
  beginnen om de permissieregel te matchen.

### Livegang-checklist (DNS www.raaminzicht.be → Combell)

1. `APP_URL=https://www.raaminzicht.be` in de server-`.env` (nu nog
   `raaminzicht.dewebgoeroe.be`, waardoor canonical/og:url naar een dood
   subdomein wijzen) + `php artisan optimize`.
2. `php artisan db:seed --class=LegalPagesSeeder --force` op prod: vult en
   **publiceert** privacyverklaring + cookiebeleid en koppelt ze aan de footer
   (zie "Juridische pagina's" hieronder).
3. `ProductSlugsSeeder` (roept RedirectsSeeder aan) draaien op prod en steekproef nemen.
4. SSL-certificaat voor www.raaminzicht.be activeren in Combell (anders 403).
5. Later, op basis van Search Console-data: eventueel enkele échte regiopagina's
   (gemeenten met realisaties) en die als exacte redirect boven het patroon zetten.

## Juridische pagina's & cookies

- **Privacyverklaring** (`/privacy-policy`) en **cookiebeleid** (`/cookie-policy`)
  worden gevuld door `database/seeders/LegalPagesSeeder.php` (één `prose`-sectie
  per pagina, tekst op maat van wat de site echt doet). Slug-tolerant (kandidaat-
  slugs per pagina) en edit-veilig: een pagina die al secties heeft wordt enkel
  gepubliceerd, de tekst blijft staan. Bedrijfsgegevens komen uit de Footer-
  instellingen op het moment van seeden; de rechtsvorm staat hard als
  "Raaminzicht BV" (oude site zei "bvba"; nakijken in de KBO).
- **Footer → Juridische pagina's** (Setting `footer.legal.privacy_page_id` /
  `cookie_page_id`): de onderste footerbalk en de consent-regel onder de
  formulieren linken ernaar via `SiteFooter::legalPages()` (enkel gepubliceerde
  pagina's, anders geen link).
- **Teksten versioneren.** `LegalPagesSeeder::TEXT_VERSION` verhogen bij elke
  inhoudelijke tekstwijziging; de seeder overschrijft dan enkel tekst die nog
  exact de zijne is (md5-hash in Setting `legal_pages_seeded`), klant-edits in de
  admin blijven staan (met waarschuwing). Huidige tekst = v2 (met Google
  Analytics).
- **Cookiebanner** (`components/site/cookie-consent.blade.php`, in de layout):
  functioneel altijd aan, analytics + marketing met toestemming; keuze in cookie
  `cookie_consent` (180 dagen). Gedragscontract voor tracking-scripts:
  `window.cookieConsent.has('analytics'|'marketing')`, window-events
  `cookie-consent-changed` en `open-cookie-preferences` (de "Cookie-instellingen"-
  knop in de footer). Niet hernoemen.
- **Google Analytics 4** (`components/site/analytics.blade.php`, in `<head>`):
  measurement-ID op Instellingen → Algemeen (Setting `google_analytics_id`, leeg =
  niets). gtag.js wordt pas van Google opgehaald ná analytics-toestemming; bij
  intrekken wordt `ga-disable-<ID>` gezet en worden de `_ga*`-cookies gewist.
  Een Meta-pixel later: zelfde patroon, categorie `marketing` (`meta-tracking`-
  skill), en sectie 2 van het cookiebeleid aanvullen (+ TEXT_VERSION).
- Tests: `tests/Feature/LegalPagesTest.php`, `tests/Feature/CookieConsentTest.php`.

## Eerste admin-user

- E-mail: `pieter@dewebgoeroe.be` — rol **Admin**. Tijdelijk wachtwoord is bij
  de scaffolding meegedeeld in de chat; wijzig het na de eerste login.

## Volgende stappen

- Placeholders vervangen (zie hierboven: foto's, partnerlogo's, mail/SMTP).
- Optioneel: kaart-embed op de contactpagina, echte Google-reviews koppelen.
- Livegang: zie de checklist onder "Redirects van de oude site".

## Lokaal draaien

```bash
php artisan serve         # of via Herd op het .test-domein
npm run dev               # Vite dev-server (hot reload)
./vendor/bin/pest         # tests (draaien tegen raaminzicht_test)
```
