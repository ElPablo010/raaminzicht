<?php

/**
 * Echte projectfoto's van Raaminzicht, per project (gemeente) gebundeld.
 *
 * Bron: de klantmap "foto's projecten" (geordend per gemeente; staat er niets
 * bij, dan is het ramen en deuren). De beelden staan in git onder
 * /public/images/realisaties/<project>/NN.{jpg,webp} — 1440px, EXIF/GPS
 * gestript — zodat ze mee deployen met de code. Near-duplicaten uit de bron
 * zijn weggelaten; de eerste foto van elk project is de cover in het grid.
 *
 * Gebruik via App\Support\Realisaties (sets per pagina, galerij-items).
 * Wijzig hier titels/alt-teksten; de RealisatiesSeeder zet ze in de pagina's.
 */
return [
    'bonheiden' => [
        'title' => 'Bonheiden — nieuwbouwwijk, ramen en deuren',
        'photos' => [
            ['/images/realisaties/bonheiden/01.jpg', 'Moderne woning in donkere gevelsteen met grote raampartijen'],
            ['/images/realisaties/bonheiden/02.jpg', 'Rij moderne nieuwbouwwoningen met zwarte aluminium ramen'],
            ['/images/realisaties/bonheiden/03.jpg', 'Hoekwoning met grote raampartij en garagepoort'],
            ['/images/realisaties/bonheiden/04.jpg', 'Kubistische woning met zwarte ramen en garagepoort'],
            ['/images/realisaties/bonheiden/05.jpg', 'Hoekraam in een moderne gevel'],
            ['/images/realisaties/bonheiden/06.jpg', 'Appartementsgebouw met glazen balkons en schuiframen'],
            ['/images/realisaties/bonheiden/07.jpg', 'Appartementen met brede raampartijen en terrassen'],
            ['/images/realisaties/bonheiden/08.jpg', 'Nieuwbouw met grote vensters in lichte gevelsteen'],
            ['/images/realisaties/bonheiden/09.jpg', 'Nieuwbouw naast een gerenoveerde hoeve'],
            ['/images/realisaties/bonheiden/10.jpg', 'Witte gerenoveerde woning met zwarte ramen en dakvlakvensters'],
        ],
    ],
    'aarschot-woning' => [
        'title' => 'Aarschot — ramen, deuren en rolluiken',
        'photos' => [
            ['/images/realisaties/aarschot-woning/01.jpg', 'Gerenoveerde woning met ramen en voordeur in houtlook'],
            ['/images/realisaties/aarschot-woning/02.jpg', 'Voordeur en ramen in houtlook'],
            ['/images/realisaties/aarschot-woning/03.jpg', 'Nieuwe ramen in houtlook aan de zijgevel'],
            ['/images/realisaties/aarschot-woning/04.jpg', 'Terras met nieuwe ramen en schuifraam'],
            ['/images/realisaties/aarschot-woning/05.jpg', 'Schuifraam in houtlook met zicht op het terras'],
            ['/images/realisaties/aarschot-woning/06.jpg', 'Rolluik in houtlook, gesloten'],
            ['/images/realisaties/aarschot-woning/07.jpg', 'Dubbele terrasdeur gezien vanuit de leefruimte'],
            ['/images/realisaties/aarschot-woning/08.jpg', 'Schuifraam met zicht op de tuin'],
            ['/images/realisaties/aarschot-woning/09.jpg', 'Terrasdeur, binnenzicht'],
            ['/images/realisaties/aarschot-woning/10.jpg', 'Nieuw draaikiepraam met zicht op de tuin'],
            ['/images/realisaties/aarschot-woning/11.jpg', 'Draaikiepraam met dubbele vleugel, binnenzicht'],
        ],
    ],
    'knokke' => [
        'title' => 'Knokke — ramen, deuren en veranda',
        'photos' => [
            ['/images/realisaties/knokke/01.jpg', 'Witte kustwoning met zwarte ramen en glazen veranda'],
            ['/images/realisaties/knokke/02.jpg', 'Veranda met schuiframen aan de tuinzijde'],
            ['/images/realisaties/knokke/03.jpg', 'Achtergevel met veranda en dakkapellen'],
            ['/images/realisaties/knokke/04.jpg', 'Voorgevel met zwarte ramen en garagepoort'],
        ],
    ],
    'scherpenheuvel' => [
        'title' => 'Scherpenheuvel — ramen en deuren',
        'photos' => [
            ['/images/realisaties/scherpenheuvel/01.jpg', 'Moderne uitbouw met grote raampartijen'],
            ['/images/realisaties/scherpenheuvel/02.jpg', 'Woning met nieuwe uitbouw in baksteen en zwart'],
            ['/images/realisaties/scherpenheuvel/03.jpg', 'Uitbouw met zwarte gevel en ramen'],
            ['/images/realisaties/scherpenheuvel/04.jpg', 'Achteraanzicht van de uitbouw met groot raam'],
        ],
    ],
    'retie' => [
        'title' => 'Retie — veranda',
        'photos' => [
            ['/images/realisaties/retie/01.jpg', 'Zwarte aluminium veranda tegen de woning'],
            ['/images/realisaties/retie/02.jpg', 'Veranda met grote glaspartijen, zijaanzicht'],
            ['/images/realisaties/retie/03.jpg', 'Veranda in opbouw met aluminium profielen'],
            ['/images/realisaties/retie/04.jpg', 'Binnenzicht van de veranda met zicht op de tuin'],
            ['/images/realisaties/retie/05.jpg', 'Detail van de dakrand en de aluminium profielen'],
        ],
    ],
    'booischot-overkapping' => [
        'title' => 'Booischot — terrasoverkapping',
        'photos' => [
            ['/images/realisaties/booischot-overkapping/01.jpg', 'Aluminium terrasoverkapping met lichtdoorlatend dak'],
            ['/images/realisaties/booischot-overkapping/02.jpg', 'Overkapping met wandpanelen in de tuin'],
            ['/images/realisaties/booischot-overkapping/03.jpg', 'Zijaanzicht van de terrasoverkapping'],
            ['/images/realisaties/booischot-overkapping/04.jpg', 'Onder de overkapping: wandpanelen en dak'],
            ['/images/realisaties/booischot-overkapping/05.jpg', 'Afgewerkte wandpanelen onder de overkapping'],
            ['/images/realisaties/booischot-overkapping/06.jpg', 'Overkapping tegen de achtergevel'],
        ],
    ],
    'aarschot-appartementen' => [
        'title' => 'Aarschot — appartementen, ramen en deuren',
        'photos' => [
            ['/images/realisaties/aarschot-appartementen/01.jpg', 'Appartementsgebouw met houten gevelbekleding en aluminium ramen'],
            ['/images/realisaties/aarschot-appartementen/02.jpg', 'Gevel met houten bekleding en grote ramen'],
            ['/images/realisaties/aarschot-appartementen/03.jpg', 'Inkom met zwarte aluminium deur'],
            ['/images/realisaties/aarschot-appartementen/04.jpg', 'Hoekzicht op de gevel met ramen en balkons'],
            ['/images/realisaties/aarschot-appartementen/05.jpg', 'Zijgevel met terrassen en raampartijen'],
            ['/images/realisaties/aarschot-appartementen/06.jpg', 'Appartementen met balkons, gezien vanaf de straat'],
            ['/images/realisaties/aarschot-appartementen/07.jpg', 'Straatzicht op het afgewerkte gebouw'],
            ['/images/realisaties/aarschot-appartementen/08.jpg', 'Deur met vlakke Duitse drempel, drempelloos'],
            ['/images/realisaties/aarschot-appartementen/09.jpg', 'Detail van de verzonken drempel'],
        ],
    ],
    'herentals' => [
        'title' => 'Herentals — renovatie ramen en deuren',
        'photos' => [
            ['/images/realisaties/herentals/01.jpg', 'Gerenoveerde woning met antraciet ramen met roedes'],
            ['/images/realisaties/herentals/02.jpg', 'Nieuw raam met roedes in de witte gevel'],
            ['/images/realisaties/herentals/03.jpg', 'Draaikiepraam met roedes'],
            ['/images/realisaties/herentals/04.jpg', 'Nieuwe voordeur in antraciet met glasvakken'],
            ['/images/realisaties/herentals/05.jpg', 'Raam met roedes aan de zijgevel'],
            ['/images/realisaties/herentals/06.jpg', 'Nieuw raam in de witgeschilderde gevel'],
            ['/images/realisaties/herentals/07.jpg', 'Ramen en deur aan de achtergevel'],
            ['/images/realisaties/herentals/08.jpg', 'Nieuwe raampartij tijdens de renovatie'],
            ['/images/realisaties/herentals/09.jpg', 'Hoekraam met zicht op het groen'],
            ['/images/realisaties/herentals/10.jpg', 'Nieuw raam geplaatst in de bestaande opening'],
            ['/images/realisaties/herentals/11.jpg', 'Nieuwe achterdeur tijdens de plaatsing'],
        ],
    ],
    'bonheiden-nieuwbouw' => [
        'title' => 'Bonheiden — nieuwbouwwoningen, ramen en deuren',
        'photos' => [
            ['/images/realisaties/bonheiden-nieuwbouw/01.jpg', 'Nieuwbouwwoningen met zwarte ramen en houten poorten'],
            ['/images/realisaties/bonheiden-nieuwbouw/02.jpg', 'Nieuwbouw met dakvlakvensters en zwarte ramen'],
            ['/images/realisaties/bonheiden-nieuwbouw/03.jpg', 'Woning met houten garagepoort en zwarte ramen'],
            ['/images/realisaties/bonheiden-nieuwbouw/04.jpg', 'Rij nieuwbouwwoningen met zwarte ramen'],
        ],
    ],
    'booischot' => [
        'title' => 'Booischot — ramen en deuren, gerenoveerde hoeve',
        'photos' => [
            ['/images/realisaties/booischot/01.jpg', 'Gerenoveerde hoeve met nieuwe ramen en houten poort'],
            ['/images/realisaties/booischot/02.jpg', 'Vooraanzicht van de hoeve met nieuwe ramen'],
            ['/images/realisaties/booischot/03.jpg', 'Binnenkoer met rondboogpoort en nieuwe ramen'],
        ],
    ],
    'westerlo' => [
        'title' => 'Westerlo — ramen en deuren',
        'photos' => [
            ['/images/realisaties/westerlo/01.jpg', 'Witte villa met zwembad en nieuwe ramen en deuren'],
        ],
    ],
    'haacht' => [
        'title' => 'Haacht — wijkrenovatie van 59 woningen',
        'photos' => [
            ['/images/realisaties/haacht/01.jpg', 'Rijwoning met nieuwe ramen in de gerenoveerde wijk'],
            ['/images/realisaties/haacht/02.jpg', 'Rij gerenoveerde woningen met nieuwe ramen'],
        ],
    ],
    'terrasoverkapping' => [
        'title' => 'Terrasoverkapping met lamellendak',
        'photos' => [
            ['/images/realisaties/terrasoverkapping/01.jpg', 'Terrasoverkapping met lamellendak naast de woning'],
        ],
    ],
    'luifel' => [
        'title' => 'Zonneluifel',
        'photos' => [
            ['/images/realisaties/luifel/01.jpg', 'Zonneluifel boven het terras van een landelijke woning'],
        ],
    ],
    'veranda' => [
        'title' => 'Veranda',
        'photos' => [
            ['/images/realisaties/veranda/01.jpg', 'Veranda met schuiframen en terras'],
        ],
    ],
    'industrie' => [
        'title' => 'Industriebouw — glasgevel',
        'photos' => [
            ['/images/realisaties/industrie/01.jpg', 'Industriegebouw met glazen gevel'],
        ],
    ],
    'zonwering' => [
        'title' => 'Zonwering',
        'photos' => [
            ['/images/realisaties/zonwering/01.jpg', 'Ramen met zonwering aan een bakstenen woning'],
        ],
    ],
    'kraanwerk' => [
        'title' => 'Plaatsing met de kraan',
        'photos' => [
            ['/images/realisaties/kraanwerk/01.jpg', 'Groot raamkader wordt met de kraan geplaatst'],
        ],
    ],
];
