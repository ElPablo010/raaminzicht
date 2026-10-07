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
  Deploy-stap voor "klaar en deploy" — Claude draait dit zelf; SSH is toegestaan
  (zie "SSH vanuit Claude" verderop):
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
  Footer-settings) en slaat elke inzending op in de `aanvragen`-tabel (model `Aanvraag`). Zet in prod
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
- **Import/koppeling** (herhaalbaar, ook op prod na een deploy):
  `php artisan db:seed --class=RealisatiesSeeder --force`. Die maakt de categorieën,
  importeert de projecten uit `database/data/realisaties.php` **enkel in een lege
  tabel** (de klant heeft op 07/09/2026 alle geseede projecten verwijderd en zelf
  opnieuw ingevoerd met eigen slugs en geüploade foto's; een slug-check zou de
  datafile er dan opnieuw naast zetten), zet galerij-secties die nog níet uit de
  realisaties putten op bron "realisaties" (secties die al op realisaties staan
  blijven van de klant) en vervangt de placeholder-hero van /realisaties. De
  pagina→set-mapping staat in `App\Support\Realisaties`.
- **Verdwenen realisaties in een selectie** (galerij op "Zelf kiezen") worden bij
  het laden van het pagina-formulier stilletjes weggelaten (`GalleryFields`),
  anders blokkeert Filament het opslaan met "Realisaties is ongeldig".
- Let op: de oude "andere cover per pagina"-truc (`['knokke', 2]`) is weg — de
  cover is nu gewoon de eerste foto van de realisatie, herordenbaar in de admin.
- **`photos` is een MySQL `json`-kolom, en MySQL sorteert de sleutels binnen elk
  object zelf** (`alt` vóór `src`, terwijl wij `src` eerst wegschrijven). Lees je
  die kolom terug in een test, vergelijk dan met `toEqual` en niet met `toBe`:
  strikt vergelijken faalt op een volgorde die de database nooit belooft. De
  vólgorde van de foto's zelf blijft wél hard vergeleken — dat zijn lijstindexen.
- Tests: `tests/Feature/RealisatiesTest.php`, `tests/Feature/AdminSmokeTest.php`.

## Stack & structuur

- Admin op `/admin` (Filament), sidebar-groep **Website**: Pagina's, Media,
  Menu's, Redirects, Realisaties, Realisatie-categorieën, Header, Footer.
  Groepsvolgorde staat vast in `AdminPanelProvider::navigationGroups()`
  (Website → Groei → Instellingen). Admin-chrome via render hooks in dezelfde
  provider: oogje naar de site vóór het account-menu
  (`filament/admin/topbar-site-link`) en een uitlogknop onderaan de zijbalk
  (`filament/admin/sidebar-logout`).
- **Media-URL's in Filament-kolommen altijd absoluut maken** (`url($record->url)`):
  de opgeslagen URL's zijn root-relatief (`/storage/…`) en `ImageColumn` ziet
  zo'n string als disk-pad, vindt het niet en rendert een lege `src`.
- Publieke site: Blade + Livewire + Alpine, server-side gerenderd, catch-all
  route → `PublicPageController`.
- Pagina-builder: secties als herordenbare blokken. Een **nieuw sectietype** =
  drie plekken:
  1. `resources/views/components/site/sections/<type-met-streepjes>.blade.php`
  2. `app/Filament/Schemas/Sections/<Type>Fields.php` (`static make(): array`)
  3. een `Block::make('<type_snake_case>')` in `PageSectionsBuilder::blocks()`
- **Sectietypes:** hero (met `height` compact/medium/tall + `highlights`-chips),
  partners (logo-strip), text_media, text (full-width rich text), cards (icon óf
  image), gallery (met Alpine-lightbox; put uit de realisaties of uit losse
  foto's), reviews (testimonials + score, `items[]` met name/role/rating/quote/
  image), faq, form, booking, cta.
- **Core-standaard (sinds 07/10/2026)**, voorbereiding op een gedeelde
  `core`-package: `prose` → `text`, `formulier` → `form`, `afspraak` → `booking`
  (+ `provider`), reviews `reviews[]` → `items[]` (location → role, avatar →
  image), hero `groot` → `tall` (+ nieuw `medium`). Omgezet met migratie
  `2026_10_07_120000_align_section_types_with_core` (ook `seo_action_items.proposed`;
  heeft een `down()`). Achtergronden zijn bewust níet hernoemd. Seeders en
  tests gebruiken de nieuwe namen.
- **Gedeelde frontend-primitives:** `<x-site.picture>` (WebP+JPG via
  `WebsiteMedia` of lokale sibling-detectie), `<x-site.btn>` (primary/secondary/
  ghost), `<x-site.section-heading>` (eyebrow/titel/intro).
- **Formulier** = `form`-sectie (`FormFields`: type offerte/contact/
  beide + onderwerpen + zijbalk) die de Livewire-component `App\Livewire\LeadForm`
  rendert (validatie NL, opslaan in `aanvragen`, mailen via `LeadReceived`).
  In offerte-modus vraagt het ook een optioneel adres (straat, postcode,
  gemeente → `aanvragen.street/postal_code/city`); bij contact wordt dat genegeerd.
  De form_types zijn Raaminzicht-eigen; enkel de sectienaam volgt de core-standaard.
- **Agenda** = `booking`-sectie (`BookingFields`, label "Agenda (afspraak)") met
  `provider`; hier enkel `eigen_agenda` (tijdsloten → `App\Livewire\AppointmentForm`,
  aanvraagtype `afspraak` in `aanvragen`). De eigen agenda bestaat alleen op
  Raaminzicht: vraagt een andere site er een, dan niet opnieuw bouwen maar naar de
  core-package verhuizen (zie Modules/wiki/modules.md → Beslissingen, 7 oktober 2026).

## Groei-module (package webgoeroe/seo-growth)

De Groei-module komt uit de package **`webgoeroe/seo-growth`** (code in
`Internal OS/Modules/repo/seo-growth`, private repo `ElPablo010/seo-growth`), niet
meer uit gekopieerde bestanden. Pas de module nooit hier aan, maar in de package.
Projecteigen afwijkingen staan in `config/seo-growth.php` (de types offerte,
contact en afspraak). De planning (syncs 6:00/6:15, briefing maandag 7:00) en de
OAuth-routes komen uit de package.

Sidebar-groep **Groei**: Overzicht (`SeoDashboard`), Verkeer (`SearchConsole`,
gemeten Google-verkeer via OAuth), Leads (`SeoLeads`, first-party conversies +
nulmeting), Keywords (`SeoKeywordResource`, incl. "Stel keywords voor"), Acties
(`SeoActions`, goedkeuringsdashboard) en **SEO-instellingen** (`SeoSettings`). De
app-brede AI-config (Anthropic-key, merknaam, omschrijving, "Feiten voor AI")
staat op **Instellingen → Algemeen** (`GeneralSettings`); `.env`-fallback
`ANTHROPIC_API_KEY` via `config('services.anthropic.api_key')`.

**Instelwerk en cijfers staan strikt gescheiden** (sinds 14/09/2026). Álles wat
je invult staat op **Groei → SEO-instellingen**: de Google-koppeling met
client-ID/secret en omleidings-URI, de knoppen "Verbinden met Google", "Andere
site kiezen", "Andere property kiezen" en "Koppeling verbreken", het GA4-meet-ID
en property-ID, DataForSEO, de GEO-prompts, de rapport-ontvanger en de
briefing-schakelaar. De koppelknoppen hangen als `Section::headerActions()` aan
de sectie waar ze over gaan.

Het item heet bewust **"SEO-instellingen"** en niet "Instellingen": de sidebar
heeft al een gróep met die naam. Het **Verkeer**-scherm houdt enkel de twee
ververs-knoppen plus een doorverwijzing; z'n lege toestanden linken naar
`SeoSettings::getUrl()`, en de OAuth-callback keert daar ook naartoe terug.
`SeoSettings` draagt nu óók de `isAdmin()`-check, want de credentials die daar
staan zijn gevoeliger dan de cijfers.

- **Wekelijkse AI-briefing staat bewust UIT.** De code (`seo:weekly-report`,
  mail, actie-generatie) is voorzien, maar draait enkel als de schakelaar
  *Wekelijkse AI-briefing actief* op Groei → SEO-instellingen aan staat (Setting
  `seo_weekly_report_enabled`, op Raaminzicht uit). Manueel blijft alles werken:
  "Ververs cijfers", "Genereer acties nu", "Stel keywords voor".
- **Search Console**: `seo:sync-search-console` dagelijks 6:00 (eerste run =
  16 maanden backfill). Koppelen op Groei → Verkeer (Google Cloud OAuth-client,
  redirect-URI staat op die pagina; app op "In productie" zetten). Routes
  `/admin/search-console/oauth/{redirect,callback}` staan in `routes/web.php`
  vóór de catch-all.
- **Analytics op hetzelfde scherm** (sinds 14/09/2026). Verkeer heeft nu twee
  tabbladen met de kerncijfers van allebei erbóven, zodat je in één oogopslag
  ziet of meer bezoek ook meer gedrag opleverde. *Uit Google Zoeken* houdt de
  zoektermen, pagina's en kansen; *Op de site* toont de meest bekeken pagina's
  en de kanalen. `$tab` is Livewire-state en `tables()` haalt enkel op wat het
  actieve tabblad toont. Sync: `seo:sync-analytics` dagelijks 6:15
  (`Ga4Collector`, tabellen `ga4_daily_metrics` + `ga4_dimension_metrics`).
  - Property-ID in `ga4_property_id` — het **getal** uit Beheer →
    Property-instellingen, niet het `G-XXXX` meet-ID uit de meetcode (dat staat
    op Groei → SEO-instellingen en voedt `components/site/analytics.blade.php`).
  - In Google Cloud moeten de **Analytics Data API én de Admin API** aan staan.
    Staat de Data API uit, dan weigert Google met een 403 en komt er geen rij
    binnen; de ververs-knop toont dan Google's eigen zin, inclusief het
    projectnummer en de link om hem aan te zetten.
  - **Een ververs-knop die niets oplevert zegt waaróm.** `GoogleApiClient`
    houdt de laatste fout bij (`lastError()`, met Google's `error.message`),
    de collectors geven die door als `error` in hun `sync()`-resultaat, en het
    Verkeer-scherm splitst dat in "Google weigerde de opvraging" (instelfout,
    zelf oplossen) en "Nog geen cijfers bij Google" (koppeling werkt, wachten).
    `php artisan seo:sync-analytics` / `seo:sync-search-console` drukken
    diezelfde reden af en geven exitcode 1. Die fouten loggen bewust als
    **error** en niet als warning: op Combell staat `LOG_LEVEL=error`, en dan
    zou net de verklarende regel wegvallen.
  - Het tabblad blijft leeg tot er gemeten is: Analytics heeft geen
    terugwerkende kracht, anders dan de 16 maanden van Search Console.
- **Eén Google-koppeling voor beide** (`App\Services\Google\GoogleApiClient`).
  Het volledige inlogwerk — consent-URL, code inwisselen, access token halen en
  cachen, `invalid_grant` afvangen, JWT voor een service account, de HTTP-laag —
  staat in die basisklasse; `GoogleSearchConsoleService` en
  `GoogleAnalyticsService` vullen enkel `serviceAccountScope()`, `apiBase()` en
  `label()` in. `CONSENT_SCOPES` vraagt beide rechten in één keer en Google's
  antwoord landt in `google_oauth_scopes`.
  - De inloggegevens staan onder `google_*`; ze heetten vroeger `gsc_*` en de
    migratie `move_google_credentials_to_shared_keys` verplaatst ze en ruimt de
    oude rijen op. `gsc_site_url` blijft van Search Console.
  - Een koppeling van vóór deze uitbreiding dekt enkel Search Console. Het
    scherm toont dan "Analytics hangt er nog niet aan" plus de knop **"Analytics
    mee koppelen"** — je hoeft de bestaande koppeling dus niet te verbreken.
  - **De route- en klassenaam blijven bewust "gsc"**: de omleidings-URI staat zo
    in Google Cloud geregistreerd en hernoemen breekt elke koppeling.
- **De cijfers sluiten niet op elkaar aan, en dat hoort zo.** Analytics telt
  enkel wie cookies aanvaardde, Search Console telt elke klik, de leads-laag
  telt iedereen. Vergelijk verhoudingen binnen één bron, geen absolute aantallen
  tussen bronnen. Daarom staat er (nog) géén conversiegraad per pagina.
- **Aanvragen en leads zijn gescheiden** (sinds 6/10/2026). `aanvragen` (model
  `Aanvraag`) is de aanvraag zelf: naam, adres, bijlagen, afspraak. `leads` is het
  conversie-grootboek van de package: enkel type en herkomst (kanaal,
  landingspagina, referrer, utm's), met een verwijzing naar de aanvraag
  (`$aanvraag->lead`, `$lead->source`). `Aanvraag::booted()` registreert elke
  nieuwe aanvraag met `Lead::record()`, dat de herkomst uit de sessie-first-touch
  haalt (`Attribution` + middleware `CaptureFirstTouch`). Nieuwe formulieren
  hoeven niets te doen zolang ze een `Aanvraag` aanmaken. Type-labels in
  `Aanvraag::TYPE_LABELS`. De splitsing gebeurde met
  `2026_10_06_120000_split_aanvragen_from_leads`. Nulmeting-velden
  (`seo_live_since`, `seo_goal_leads_month`, `seo_leads_baseline`) op het
  Leads-scherm.
- **Sectie-contract**: de package herkent zelf dat het tekstblok hier `text` heet
  (eerste kandidaat met een view in `components/site/sections/`):
  `hero` → `text` (heading + body) → `faq` → `cta`. De gekloonde CTA-knop volgt
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
  `GoogleApiClientTest` (de gedeelde inloglaag, op een verzonnen subklasse),
  `AnalyticsCollectorTest` (GA4-sync + tweede tabblad),
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
- **Canonieke host = `APP_URL` (`https://www.raaminzicht.be`).** `RedirectToCanonicalHost`
  (globale middleware, `bootstrap/app.php`) stuurt `raaminzicht.be` met een 301 naar
  `www.` voor GET/HEAD, ook op `/admin`. Reden: beide hosts wijzen bij Combell naar
  dezelfde docroot, en de Google-OAuth-callback wordt uit de aanvraag-host opgebouwd
  (`route()`), dus de kale host gaf `redirect_uri_mismatch`. In de OAuth-client in
  Google Cloud (project *Client websites*) staan beide callbacks geregistreerd. Doet
  niets lokaal of op de preview-URL, want enkel een `APP_URL` met `www.` activeert het.
- **SSH vanuit Claude** is toegestaan via `.claude/settings.local.json`
  (buiten git): deploys, dumps en seeders op de server hoeven niet meer via de
  gebruiker. Commando's moeten letterlijk met `ssh raaminzichtbe@176.62.165.220`
  beginnen om de permissieregel te matchen.

### Livegang-checklist (domein www.raaminzicht.be → Combell)

Stand op 07/09/2026: het domein staat bij **one.com** (registrar én nameservers
ns01/ns02.one.com), de mailboxen draaien op one.com-mailservers (MX), er is geen
SPF/DMARC, en **DNSSEC staat aan** (DS-records van one.com bij DNS Belgium). De
klant heeft geen toegang tot het one.com-paneel (vorige webdeveloper, gestopt).
Oude-site-redirects zijn op 07/09/2026 volledig getest op de preview-URL: alle
891 sitemap-URL's landen op een 200.

**A. Voorbereiden (kan nu al, DNS speelt geen rol)**

1. **Houder controleren** in de web-whois op dnsbelgium.be: wie staat als houder
   en met welk e-mailadres? Is dat Raaminzicht met een werkend adres → ok. Is het
   de oude developer of een dood adres → eerst houderwijziging via DNS Belgium
   (bewijs: KBO-uittreksel van de BV). Parallel evt. one.com-support vragen om
   accounttoegang met hetzelfde bewijs (handig voor het overzicht van mailboxen
   en aliassen, niet strikt nodig).
2. **Mailboxen inventariseren**: welke adressen, aliassen, doorstuurregels, wie
   leest ze op welk toestel (vermoedelijk Outlook), en de mailboxwachtwoorden
   (= wat Outlook gebruikt; ook geldig op webmail.one.com).
3. **Mailboxen aanmaken bij Combell** (controlepaneel → E-mail) vóór de
   migratie; de tool heeft ze als bestemming nodig. Servergegevens noteren
   (normaal `imap.mailprotect.be`:993 en `smtp-auth.mailprotect.be`:587,
   gebruikersnaam = volledig adres; nakijken in het paneel).
4. **Mail kopiëren** met de migratietool van Combell: bron `imap.one.com`:993 +
   adres + one.com-wachtwoord, doel = nieuwe mailbox. Alternatief vanaf de Mac
   (herhaalbaar, tweede run = enkel delta):
   `imapsync --host1 imap.one.com --user1 <adres> --password1 '…' --host2 imap.mailprotect.be --user2 <adres> --password2 '…'`.
   Wachtwoord onbekend → enkel via Outlook: Combell-account **handmatig** (IMAP,
   servernamen hierboven, geen auto-detectie want die volgt de DNS naar one.com)
   toevoegen en mappen slepen, of in klassiek Outlook exporteren naar .pst en
   importeren (nieuwe Outlook kan geen .pst importeren). Controleren via de
   Combell-webmail.
5. **DNS-zone bij Combell volledig klaarzetten** vóór de nameservers switchen:
   A-records `raaminzicht.be` + `www` → 176.62.165.220, MX van Combell, SPF, en
   het Google Search Console TXT-record (`google-site-verification=…`, naam
   leeg/`@`, TTL 3600). Zonder MX bouncen inkomende mails.
6. `APP_URL=https://www.raaminzicht.be` in de server-`.env` (nu nog
   `raaminzicht.dewebgoeroe.be`, waardoor canonical/og:url naar een dood
   subdomein wijzen) + `php artisan optimize`.
7. `php artisan db:seed --class=LegalPagesSeeder --force` op prod: vult en
   **publiceert** privacyverklaring + cookiebeleid en koppelt ze aan de footer
   (zie "Juridische pagina's" hieronder).
8. `ProductSlugsSeeder` (roept RedirectsSeeder aan) draaien op prod en steekproef nemen.

**B. Transfer (pas na A, mail eerst)**

9. **Transfercode aanvragen** rechtstreeks bij DNS Belgium ("transfercode
   aanvragen" op dnsbelgium.be; wordt naar het houder-adres gestuurd), niet via
   het one.com-paneel. Transfer starten bij Combell met die code; een .be-transfer
   is meestal binnen enkele uren rond en de nameservers gaan dan naar Combell.
10. **DNSSEC**: Combell expliciet vragen om de one.com-DS-records te verwijderen
    of te vervangen door eigen sleutels. Anders is site én mail na de transfer
    onbereikbaar voor validerende resolvers.
11. SSL-certificaat voor www.raaminzicht.be activeren in Combell (anders 403).
12. Search Console: op "Verifiëren" klikken (TXT-record uit stap 5) en de
    OAuth-koppeling op Groei → Verkeer nakijken.

**C. Nazorg**

13. Migratietool/imapsync nog één keer draaien voor mails die tijdens de
    overgang nog bij one.com toekwamen.
14. Outlook van de klant: Combell-account toevoegen (auto-detectie werkt nu wel
    via de DNS), Outlook synct alles wat gemigreerd is; **uitgaande server (SMTP)
    ook op Combell** zetten, anders vertrekt mail nog via one.com. Het oude
    one.com-account laten staan tot de opzegging.
15. Redirects + productpagina's steekproefsgewijs testen op www.raaminzicht.be.
16. **one.com pas na 2–4 weken opzeggen**, nooit vóór de transfer (dan verdwijnen
    domein én mail). Let op: oude WordPress-URL's doen nu twee hops (slash-strip
    301 + redirect 301); werkt voor Google, één hop zou netter zijn.
17. Later, op basis van Search Console-data: eventueel enkele échte regiopagina's
    (gemeenten met realisaties) en die als exacte redirect boven het patroon zetten.

## Juridische pagina's & cookies

- **Privacyverklaring** (`/privacy-policy`) en **cookiebeleid** (`/cookie-policy`)
  worden gevuld door `database/seeders/LegalPagesSeeder.php` (één `text`-sectie
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
  admin blijven staan (met waarschuwing). Huidige tekst = v3 (met Google
  Analytics en het optionele werfadres bij een offerteaanvraag).
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
