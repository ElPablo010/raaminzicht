<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Setting;
use App\Support\SiteFooter;
use Illuminate\Database\Seeder;

/**
 * Vult de juridische pagina's (privacyverklaring + cookiebeleid), publiceert ze
 * en koppelt ze aan de footer (onderste balk) via de Footer-instellingen.
 *
 * De teksten zijn op maat van Raaminzicht: wat de site effectief doet (offerte-,
 * contact- en afspraakformulier, sessie-cookie, Google Fonts, geen analytische
 * of marketing-cookies) en de bedrijfsgegevens uit de Footer-instellingen
 * (naam, adres, btw, e-mail, telefoon) op het moment van seeden.
 *
 * Slug-tolerant: op prod kunnen de pagina's een andere slug hebben dan lokaal
 * (zie CLAUDE.md, "Slug-drift"). Per pagina wordt de eerste bestaande kandidaat
 * gebruikt; bestaat er geen, dan wordt de pagina aangemaakt op de eerste slug.
 *
 * Edit-veilig, met versies: de tekst wordt geschreven als de pagina nog geen
 * sectie heeft, én opnieuw wanneer TEXT_VERSION verhoogd is en de bestaande tekst
 * nog exact de tekst is die deze seeder eerder schreef (hash in Setting
 * `legal_pages_seeded`). Heeft de klant de tekst intussen in de admin aangepast,
 * dan blijft die staan en waarschuwt de seeder. Publiceren en de footer-
 * koppeling gebeuren altijd. Herhaalbaar:
 *   php artisan db:seed --class=LegalPagesSeeder --force
 *
 * Tekst wijzigen? Pas de body aan én verhoog TEXT_VERSION, anders blijft de oude
 * tekst op prod staan.
 */
class LegalPagesSeeder extends Seeder
{
    private const PRIVACY_SLUGS = ['privacy-policy', 'privacybeleid', 'privacy-beleid', 'privacyverklaring', 'privacy'];

    private const COOKIE_SLUGS = ['cookie-policy', 'cookiebeleid', 'cookie-beleid', 'cookies'];

    /** Verhogen bij elke inhoudelijke tekstwijziging (zie docblock). */
    public const TEXT_VERSION = 2;

    public const SEEDED_KEY = 'legal_pages_seeded';

    public function run(): void
    {
        $info = $this->companyInfo();

        // Eerst beide pagina's (of hun slug) bepalen, zodat de teksten naar de
        // echte slug van de andere pagina kunnen linken.
        $privacy = $this->resolvePage(self::PRIVACY_SLUGS);
        $cookie = $this->resolvePage(self::COOKIE_SLUGS);

        $this->fillPage(
            'privacy',
            $privacy,
            'Privacyverklaring',
            'Hoe Raaminzicht omgaat met uw persoonsgegevens: welke gegevens we verzamelen via de website, waarvoor, hoe lang we ze bewaren en welke rechten u hebt.',
            $this->privacyBody($info, '/'.$cookie->slug),
        );

        $this->fillPage(
            'cookie',
            $cookie,
            'Cookiebeleid',
            'Welke cookies de website van Raaminzicht plaatst, waarvoor ze dienen en hoe u ze kunt verwijderen.',
            $this->cookieBody($info, '/'.$privacy->slug),
        );

        $this->linkInFooter($privacy, $cookie);

        $this->command?->info("Privacy: /{$privacy->slug} (id {$privacy->id}), cookies: /{$cookie->slug} (id {$cookie->id}) — gepubliceerd en gekoppeld aan de footer.");
    }

    /**
     * Zoek de pagina op één van de kandidaat-slugs (in volgorde van voorkeur),
     * of geef een nog niet bewaarde pagina op de eerste slug terug.
     *
     * @param  list<string>  $slugs
     */
    private function resolvePage(array $slugs): Page
    {
        $page = Page::query()
            ->where('locale', 'nl')
            ->whereIn('slug', $slugs)
            ->get()
            ->sortBy(fn (Page $p) => array_search($p->slug, $slugs, true))
            ->first();

        return $page ?? new Page(['slug' => $slugs[0], 'locale' => 'nl', 'is_homepage' => false]);
    }

    private function fillPage(string $key, Page $page, string $title, string $metaDescription, string $body): void
    {
        $page->fill([
            'title' => $page->title ?: $title,
            'published' => true,
            'meta_title' => $page->meta_title ?: "{$title} | Raaminzicht",
            'meta_description' => $page->meta_description ?: $metaDescription,
        ])->save();

        $seeded = (array) Setting::get(self::SEEDED_KEY, []);
        $section = $page->sections()->orderBy('position')->first();

        if ($section === null) {
            $page->sections()->create([
                'section_type' => 'prose',
                'position' => 0,
                'locale' => 'nl',
                'content' => [
                    'background' => 'white',
                    'eyebrow' => 'Juridisch',
                    'heading' => $title,
                    'body' => $body,
                ],
            ]);
        } elseif (($seeded[$key]['version'] ?? 1) < self::TEXT_VERSION) {
            // Versie verhoogd: enkel overschrijven als de tekst nog de onze is.
            // Vóór het hash-mechanisme (v1) is er geen hash; herken onze v1-tekst
            // dan aan de datumregel waarmee ze begint (klant-eigen tekst heeft die niet).
            $currentBody = (string) ($section->content['body'] ?? '');
            $untouched = isset($seeded[$key]['hash'])
                ? $seeded[$key]['hash'] === md5($currentBody)
                : str_contains($currentBody, 'Laatst bijgewerkt op 04/09/2026');

            if ($untouched) {
                $section->update(['content' => [...$section->content, 'body' => $body]]);
            } else {
                $this->command?->warn("/{$page->slug}: tekst is in de admin aangepast, niet overschreven met versie ".self::TEXT_VERSION.'. Werk de tekst handmatig bij.');

                return;
            }
        } else {
            return;
        }

        $seeded[$key] = ['version' => self::TEXT_VERSION, 'hash' => md5($body)];
        Setting::set(self::SEEDED_KEY, $seeded);
    }

    /**
     * Zet de pagina-id's in de Footer-instellingen (groep `legal`) zodat de
     * onderste footerbalk ernaar linkt. Een bestaande, geldige koppeling wordt
     * niet overschreven.
     */
    private function linkInFooter(Page $privacy, Page $cookie): void
    {
        $footer = Setting::get(SiteFooter::KEY, []);
        $legal = $footer['legal'] ?? [];

        foreach (['privacy_page_id' => $privacy, 'cookie_page_id' => $cookie] as $key => $page) {
            $current = $legal[$key] ?? null;
            if (! $current || ! Page::query()->whereKey($current)->where('published', true)->exists()) {
                $legal[$key] = $page->id;
            }
        }

        $footer['legal'] = $legal;
        Setting::set(SiteFooter::KEY, $footer);
    }

    /**
     * @return array{name: string, address: string, vat: string, email: string, phone: string}
     */
    private function companyInfo(): array
    {
        $contact = SiteFooter::current()['contact'] ?? [];

        $address = trim(preg_replace('/\s*\n\s*/', ', ', (string) ($contact['address'] ?? '')));

        return [
            'name' => 'Raaminzicht BV',
            'address' => $address !== '' ? $address : 'Liersesteenweg 42, 2221 Heist-op-den-Berg (Booischot)',
            'vat' => preg_replace('/^BTW\s+/i', '', (string) ($contact['vat'] ?? '')) ?: 'BE0643.868.479',
            'email' => $contact['email'] ?: 'info@raaminzicht.be',
            'phone' => $contact['phone'] ?: '0469 79 22 40',
        ];
    }

    /**
     * @param  array{name: string, address: string, vat: string, email: string, phone: string}  $c
     */
    private function privacyBody(array $c, string $cookieHref): string
    {
        $cookieHref = e($cookieHref);
        $mail = '<a href="mailto:'.e($c['email']).'">'.e($c['email']).'</a>';
        $tel = '<a href="tel:'.preg_replace('/[^0-9+]/', '', $c['phone']).'">'.e($c['phone']).'</a>';
        $name = e($c['name']);
        $address = e($c['address']);
        $vat = e($c['vat']);

        return <<<HTML
            <p><em>Laatst bijgewerkt op 04/09/2026.</em></p>
            <p>{$name} hecht veel belang aan de bescherming van uw persoonsgegevens. In deze privacyverklaring leest u welke gegevens we verzamelen wanneer u onze website bezoekt of contact met ons opneemt, waarvoor we ze gebruiken, hoe lang we ze bewaren en welke rechten u hebt. We verwerken uw gegevens in overeenstemming met de Algemene Verordening Gegevensbescherming (AVG/GDPR) en de Belgische privacywetgeving.</p>

            <h2>1. Wie is verantwoordelijk voor uw gegevens?</h2>
            <p>De verwerkingsverantwoordelijke is:</p>
            <p><strong>{$name}</strong><br>{$address}<br>Ondernemingsnummer / btw: {$vat}<br>E-mail: {$mail}<br>Telefoon: {$tel}</p>
            <p>Hebt u vragen over deze verklaring of over de manier waarop we met uw gegevens omgaan, dan kunt u ons altijd bereiken via bovenstaande gegevens.</p>

            <h2>2. Welke gegevens verzamelen we?</h2>
            <p><strong>Gegevens die u zelf aan ons bezorgt.</strong> Wanneer u een offerte aanvraagt, een vraag stelt via het contactformulier, een toonzaalafspraak inplant, ons belt of mailt, verwerken we de gegevens die u daarbij meedeelt: uw naam, e-mailadres, telefoonnummer, de onderwerpen waarin u interesse hebt, uw bericht, de gewenste datum en het tijdstip van een afspraak, en eventuele bijlagen zoals plannen of foto's van uw woning. Wordt u klant, dan verwerken we daarnaast uw adres en de gegevens die nodig zijn voor de opmeting, de bestelling, de plaatsing, de facturatie en de garantie.</p>
            <p><strong>Gegevens die automatisch verzameld worden.</strong> Bij een bezoek aan onze website registreren onze servers technische gegevens zoals uw IP-adres, het type browser en toestel, de bezochte pagina's en het tijdstip van uw bezoek. Tijdens uw bezoek onthouden we in uw sessie ook via welke weg u op de site terechtkwam (bijvoorbeeld via Google, een sociaal netwerk of rechtstreeks) en op welke pagina u binnenkwam. Die herkomst wordt enkel bij een aanvraag mee opgeslagen, zodat we weten welke kanalen voor ons werken. Deze gegevens zijn niet gekoppeld aan advertentieprofielen en worden niet gedeeld met advertentienetwerken.</p>
            <p>We verzamelen geen bijzondere categorieën van persoonsgegevens (zoals gezondheids- of financiële gegevens), tenzij u ze ons zelf meedeelt in een bericht.</p>

            <h2>3. Waarvoor gebruiken we uw gegevens en op welke grond?</h2>
            <ul>
                <li><strong>Om uw aanvraag te beantwoorden</strong> en u een offerte, advies of afspraakbevestiging te bezorgen. Rechtsgrond: het nemen van stappen op uw verzoek voorafgaand aan een overeenkomst.</li>
                <li><strong>Om onze overeenkomst uit te voeren:</strong> opmeting, bestelling bij de fabrikant, plaatsing, facturatie, dienst na verkoop en garantie. Rechtsgrond: uitvoering van de overeenkomst.</li>
                <li><strong>Om aan onze wettelijke verplichtingen te voldoen,</strong> zoals de boekhoud- en btw-wetgeving. Rechtsgrond: wettelijke verplichting.</li>
                <li><strong>Om onze website veilig en werkend te houden</strong> en misbruik (zoals spam via de formulieren) te voorkomen, en om te begrijpen via welke kanalen bezoekers ons vinden. Rechtsgrond: ons gerechtvaardigd belang bij een goed werkende, veilige website.</li>
                <li><strong>Om u later opnieuw te contacteren</strong> over uw lopende aanvraag of project. We sturen geen nieuwsbrieven of reclame zonder dat u daar uitdrukkelijk om vraagt.</li>
            </ul>

            <h2>4. Met wie delen we uw gegevens?</h2>
            <p>We verkopen uw gegevens nooit en geven ze niet door aan derden voor hun eigen marketingdoeleinden. Uw gegevens worden enkel gedeeld met partijen die ons helpen onze diensten te leveren en die dat uitsluitend in onze opdracht doen:</p>
            <ul>
                <li><strong>Onze hostingpartner</strong> (Combell, België), waar de website en de ingestuurde formulieren bewaard worden;</li>
                <li><strong>Onze e-mailprovider</strong>, om formulieren en berichten bij ons te laten toekomen;</li>
                <li><strong>Onze boekhouder</strong>, voor facturatie en de wettelijke boekhouding;</li>
                <li><strong>Fabrikanten en leveranciers</strong> van ramen, deuren, veranda's, zonwering en poorten, enkel voor zover dat nodig is om uw bestelling te produceren of te leveren (bijvoorbeeld het leveradres).</li>
            </ul>
            <p>Met deze partijen maken we afspraken over de beveiliging en het vertrouwelijk gebruik van uw gegevens. Daarnaast kunnen we gegevens doorgeven wanneer de wet ons daartoe verplicht, bijvoorbeeld op vraag van een bevoegde overheid.</p>
            <p><strong>Google Analytics.</strong> Enkel wanneer u analytische cookies aanvaardt in de cookiebanner, meten we met Google Analytics hoe onze website gebruikt wordt: welke pagina's bezocht worden, hoe lang, vanaf welk type toestel en via welke weg bezoekers bij ons terechtkomen. Google verwerkt die gegevens in onze opdracht, ook op servers buiten de Europese Economische Ruimte, op basis van de standaardcontractbepalingen van de Europese Commissie. IP-adressen worden door Google Analytics niet opgeslagen en we delen geen gegevens met Google voor advertentiedoeleinden. Zonder uw toestemming wordt Google Analytics niet geladen.</p>
            <p><strong>Google Fonts.</strong> Onze website laadt lettertypes via Google Fonts. Daarbij wordt uw IP-adres doorgegeven aan Google, dat gegevens ook buiten de Europese Economische Ruimte kan verwerken. Google plaatst hierbij geen cookies. Meer informatie vindt u in het <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">privacybeleid van Google</a>.</p>

            <h2>5. Hoe lang bewaren we uw gegevens?</h2>
            <ul>
                <li><strong>Aanvragen zonder vervolg</strong> (offerte, contactvraag, afspraak die niet tot een bestelling leidt): maximaal 2 jaar na ons laatste contact, zodat we u kunnen verder helpen als u later terugkomt op uw aanvraag.</li>
                <li><strong>Klantgegevens, offertes, bestellingen en facturen:</strong> zo lang als nodig voor de garantie en de dienst na verkoop, en minstens gedurende de wettelijke bewaartermijn voor boekhoudkundige stukken (momenteel 10 jaar).</li>
                <li><strong>Technische servergegevens</strong> (logbestanden): maximaal 12 maanden.</li>
            </ul>

            <h2>6. Hoe beveiligen we uw gegevens?</h2>
            <p>We nemen passende technische en organisatorische maatregelen om uw gegevens te beschermen tegen verlies, misbruik en ongeoorloofde toegang. Onze website is beveiligd met een SSL-certificaat (https), de formulieren zijn beschermd tegen geautomatiseerde spam, en enkel medewerkers die uw aanvraag opvolgen hebben toegang tot uw gegevens.</p>

            <h2>7. Welke rechten hebt u?</h2>
            <p>U hebt op elk moment het recht om:</p>
            <ul>
                <li>uw persoonsgegevens <strong>in te kijken</strong> en er een kopie van te ontvangen;</li>
                <li>onjuiste of onvolledige gegevens te laten <strong>verbeteren</strong>;</li>
                <li>uw gegevens te laten <strong>verwijderen</strong>, voor zover we ze niet wettelijk moeten bewaren;</li>
                <li>de verwerking te laten <strong>beperken</strong> of er <strong>bezwaar</strong> tegen te maken;</li>
                <li>uw gegevens in een gangbaar formaat te ontvangen om ze aan een andere partij <strong>over te dragen</strong>;</li>
                <li>een gegeven <strong>toestemming in te trekken</strong>, zonder dat dit de rechtmatigheid van de verwerking vóór de intrekking aantast.</li>
            </ul>
            <p>Stuur uw verzoek naar {$mail} of per post naar het adres hierboven. Om zeker te zijn dat we met de juiste persoon te maken hebben, kunnen we u vragen om uw identiteit te bevestigen. We antwoorden binnen één maand.</p>
            <p>Bent u niet tevreden over de manier waarop we met uw gegevens omgaan, dan kunt u een klacht indienen bij de <a href="https://www.gegevensbeschermingsautoriteit.be" target="_blank" rel="noopener">Gegevensbeschermingsautoriteit</a>, Drukpersstraat 35, 1000 Brussel, <a href="mailto:contact@apd-gba.be">contact@apd-gba.be</a>.</p>

            <h2>8. Cookies</h2>
            <p>Onze website gebruikt cookies die noodzakelijk zijn voor de werking ervan en, enkel met uw toestemming, analytische cookies van Google Analytics. Bij uw eerste bezoek vragen we uw keuze via een cookiebanner; u kunt ze op elk moment aanpassen via de link "Cookie-instellingen" onderaan elke pagina. Welke cookies dat zijn en hoe u ze kunt verwijderen, leest u in ons <a href="{$cookieHref}">cookiebeleid</a>.</p>

            <h2>9. Minderjarigen</h2>
            <p>Onze diensten richten zich tot volwassenen. We verzamelen niet bewust gegevens van personen jonger dan 16 jaar. Merkt u dat een minderjarige ons toch gegevens bezorgde, laat het ons dan weten, dan verwijderen we die.</p>

            <h2>10. Wijzigingen</h2>
            <p>We kunnen deze privacyverklaring aanpassen, bijvoorbeeld wanneer we nieuwe diensten of tools in gebruik nemen. De meest recente versie staat altijd op deze pagina, met bovenaan de datum van de laatste wijziging.</p>
            HTML;
    }

    /**
     * @param  array{name: string, address: string, vat: string, email: string, phone: string}  $c
     */
    private function cookieBody(array $c, string $privacyHref): string
    {
        $mail = '<a href="mailto:'.e($c['email']).'">'.e($c['email']).'</a>';
        $name = e($c['name']);
        $privacyHref = e($privacyHref);

        return <<<HTML
            <p><em>Laatst bijgewerkt op 04/09/2026.</em></p>
            <p>Deze website van {$name} maakt gebruik van cookies. Hieronder leest u wat cookies zijn, welke cookies we plaatsen, waarvoor ze dienen, hoe we uw toestemming vragen en hoe u uw keuze kunt aanpassen.</p>

            <h2>1. Wat zijn cookies?</h2>
            <p>Cookies zijn kleine tekstbestanden die een website bij uw bezoek op uw computer, tablet of smartphone bewaart. Ze laten de website toe om u tijdens uw bezoek te herkennen, bijvoorbeeld om een ingevuld formulier veilig te kunnen verzenden. Cookies bevatten geen virussen en kunnen uw toestel niet beschadigen.</p>

            <h2>2. Welke cookies plaatsen we?</h2>
            <p><strong>Functionele (strikt noodzakelijke) cookies.</strong> Die zijn nodig om de website correct en veilig te laten werken. Voor het plaatsen ervan is volgens de wet geen toestemming vereist.</p>
            <ul>
                <li><strong>raaminzicht-session</strong> (vervalt 2 uur na uw laatste activiteit): houdt uw bezoek bij als één sessie, zodat de formulieren (offerte, contact, afspraak) correct werken en foutmeldingen getoond kunnen worden. Onthoudt tijdens uw bezoek ook via welke weg u op de site kwam, zodat we bij een aanvraag weten welk kanaal voor ons werkt.</li>
                <li><strong>XSRF-TOKEN</strong> (vervalt 2 uur na uw laatste activiteit): beveiligt de formulieren tegen misbruik door andere websites (CSRF-bescherming).</li>
                <li><strong>cookie_consent</strong> (180 dagen): bewaart uw cookiekeuze, zodat we de cookiebanner niet bij elk bezoek opnieuw tonen.</li>
            </ul>
            <p><strong>Analytische cookies (Google Analytics), enkel met uw toestemming.</strong> Hiermee meten we anoniem hoe de website gebruikt wordt (bezochte pagina's, duur van het bezoek, type toestel, herkomst van het bezoek), zodat we de site kunnen verbeteren. Google Analytics wordt pas geladen nadat u analytische cookies aanvaardt; tot dan gaat er geen enkel gegeven naar Google.</p>
            <ul>
                <li><strong>_ga</strong> (2 jaar): onderscheidt bezoekers van elkaar aan de hand van een willekeurig nummer.</li>
                <li><strong>_ga_&lt;ID&gt;</strong> (2 jaar): houdt de sessiestatus bij voor Google Analytics.</li>
            </ul>
            <p>Deze cookies bevatten geen naam, e-mailadres of andere rechtstreeks identificeerbare gegevens. Meer over hoe Google gegevens verwerkt leest u in het <a href="https://policies.google.com/technologies/partner-sites" target="_blank" rel="noopener">beleid van Google</a>.</p>
            <p><strong>Marketingcookies.</strong> Die gebruiken we momenteel niet. Zetten we ze in de toekomst in (bijvoorbeeld voor advertenties op sociale media), dan vragen we daarvoor eerst apart uw toestemming en passen we dit cookiebeleid aan.</p>

            <h2>3. Uw toestemming en hoe u ze aanpast</h2>
            <p>Bij uw eerste bezoek tonen we een cookiebanner. Daar kiest u of u analytische cookies aanvaardt of weigert; via "Voorkeuren aanpassen" kiest u per categorie. Functionele cookies staan altijd aan. Zolang u geen keuze maakt, worden enkel de functionele cookies geplaatst.</p>
            <p>U kunt uw keuze op elk moment aanpassen of intrekken via de link <strong>"Cookie-instellingen"</strong> onderaan elke pagina. Trekt u uw toestemming voor analytische cookies in, dan wordt Google Analytics uitgeschakeld en verwijderen we de bijhorende cookies.</p>
            <p>Om te weten hoe bezoekers ons via Google vinden, gebruiken we daarnaast Google Search Console. Die dienst werkt op basis van geanonimiseerde zoekgegevens die Google ons bezorgt en plaatst geen cookies op uw toestel.</p>

            <h2>4. Diensten van derden</h2>
            <p>Onze website laadt lettertypes via <strong>Google Fonts</strong>. Daarbij plaatst Google geen cookies, maar wordt uw IP-adres wel doorgegeven aan Google. Meer daarover leest u in onze <a href="{$privacyHref}">privacyverklaring</a>.</p>

            <h2>5. Cookies verwijderen of blokkeren</h2>
            <p>U kunt cookies altijd verwijderen of blokkeren via de instellingen van uw browser. Hoe dat werkt, leest u bij de browser die u gebruikt:</p>
            <ul>
                <li><a href="https://support.google.com/chrome/answer/95647?hl=nl" target="_blank" rel="noopener">Google Chrome</a></li>
                <li><a href="https://support.mozilla.org/nl/kb/cookies-verwijderen-gegevens-wissen-websites-opgeslagen" target="_blank" rel="noopener">Mozilla Firefox</a></li>
                <li><a href="https://support.apple.com/nl-be/guide/safari/sfri11471/mac" target="_blank" rel="noopener">Safari</a></li>
                <li><a href="https://support.microsoft.com/nl-nl/microsoft-edge/cookies-verwijderen-in-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" target="_blank" rel="noopener">Microsoft Edge</a></li>
            </ul>
            <p>Let op: blokkeert u de noodzakelijke cookies, dan kunt u de formulieren op onze website mogelijk niet meer verzenden.</p>

            <h2>6. Vragen?</h2>
            <p>Hebt u vragen over dit cookiebeleid of over de manier waarop we met uw gegevens omgaan, neem dan contact op via {$mail}. Hoe we persoonsgegevens verwerken, leest u in onze <a href="{$privacyHref}">privacyverklaring</a>.</p>
            HTML;
    }
}
