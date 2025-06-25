# E-Stem Suriname Documentatie

## Projectoverzicht

E-Stem Suriname is een digitaal stemsysteem, ontworpen om het verkiezingsproces in Suriname te moderniseren. Het platform is gericht op twee hoofdgroepen: kiezers en verkiezingsadministrators. Het primaire doel is het traditionele, op papier gebaseerde stemproces te vervangen door een veilige, efficiënte en transparante digitale oplossing. Dit minimaliseert de kans op menselijke fouten, versnelt de verwerking van resultaten en biedt real-time inzicht in het verloop van de verkiezing.

## Technische Opbouw

Het project is een webapplicatie die gebruikmaakt van een klassieke PHP-stack, verrijkt met moderne frontend-technologieën. De architectuur is opgezet volgens een MVC-geïnspireerd patroon om een duidelijke scheiding tussen logica, data en presentatie te waarborgen.

### Technologieën
- **Backend**: PHP 8.x
- **Database**: MySQL / MariaDB (via PDO)
- **Frontend**: HTML5, JavaScript (ES6+), Tailwind CSS
- **Dependencies**:
    - **[Composer](https://getcomposer.org/)**: Voor het beheren van PHP-packages.
    - **[Bacon/QRCode](https://github.com/Bacon/BaconQrCode)**: Voor het genereren van QR-codes.
    - **[Chart.js](https://www.chartjs.org/)**: Voor het visualiseren van verkiezingsdata in grafieken.
    - **[Font Awesome](https://fontawesome.com/)**: Voor iconen in de gebruikersinterface.

### Map- en Bestandsstructuur

De mappenstructuur is logisch ingedeeld om de verschillende onderdelen van de applicatie te scheiden:

- `/admin`: Bevat alle bestanden voor het administratiepaneel, inclusief het dashboard, gebruikersbeheer en QR-code generatie.
- `/src`: De kern van de applicatie.
    - `/src/controllers`: Verwerkt inkomende verzoeken en fungeert als schakel tussen models en views.
    - `/src/models`: Definieert de datastructuren (bv. `Voter`, `Vote`) en interacties met de database.
    - `/src/views`: Bevat de PHP-templates die de HTML-output genereren.
    - `/src/api`: Herbergt de API-endpoints die via AJAX worden aangeroepen voor dynamische functionaliteit.
- `/database`: Bevat het `.sql`-schema voor de database en eventuele migratie- of simulatiescripts.
- `/assets`: Publieke bestanden zoals CSS-stylesheets, JavaScript-bestanden en afbeeldingen.
- `/include`: Globale PHP-scripts voor configuratie (`config.php`), databaseverbinding (`db_connect.php`) en authenticatie.
- `/vendor`: Externe PHP-bibliotheken die via Composer zijn geïnstalleerd.
- `/pages/voting`: De publieke pagina's waar kiezers hun stem uitbrengen.

## Belangrijkste Functionaliteiten

De applicatie biedt een reeks robuuste functies voor zowel administrators als kiezers.

- **Admin Dashboard**: Een centraal dashboard (`admin/index.php`) toont real-time verkiezingsstatistieken, waaronder de totale opkomst, het aantal uitgebrachte stemmen, de leidende partij en resultaten per district.
- **Verkiezingsbeheer**: Administrators kunnen verkiezingen aanmaken, activeren en beheren via de interface in `/src/views/elections.php`.
- **Kandidaat- en Partijbeheer**: Volledige CRUD-functionaliteit voor het beheren van politieke partijen en kandidaten.
- **Kiezersbeheer**: Mogelijkheid om kiezers te beheren en te importeren via een CSV-bestand (`templates/voters_import_template.csv`).
- **QR-Code Systeem**: Het systeem genereert unieke QR-codes voor kiezers (`admin/qrcodes.php`). Deze codes worden waarschijnlijk gebruikt voor snelle en veilige identificatie bij het stembureau of voor toegang tot de digitale stembus.
- **Digitaal Stemmen**: Kiezers gebruiken een beveiligde interface (`/vote/ballot.php`) om hun stem uit te brengen op kandidaten voor De Nationale Assemblée (DNA) en de Ressortraden (RR).
- **Real-time Resultaten**: De resultaten worden live bijgewerkt en gevisualiseerd in grafieken op het admin dashboard, waardoor direct inzicht mogelijk is.
- **Authenticatie**: Er is een strikte scheiding tussen de admin-omgeving en de publieke stem-interface, beveiligd via aparte authenticatielogica (`include/admin_auth.php` en `include/VoterAuth.php`).
- **Anomalie Detectie**: Een simpele vorm van fraudedetectie is ingebouwd, die signaleert wanneer een kiezer ongebruikelijk vaak stemt.

## Kiezersinterface en Stemproces

Naast het admin-paneel is er een volledige flow ontwikkeld voor de kiezer. Deze interface is ontworpen om het stemproces intuïtief en veilig te maken.

1.  **Toegang en Verificatie**:
    -   De kiezer start het proces via een specifieke landingspagina, waarschijnlijk `voter/index.php`.
    -   Hier moet de kiezer zich authenticeren. Op basis van de bestandsstructuur (`verify_qr_code.php`, `qrcodes.php`) gebeurt dit door een unieke, persoonlijke QR-code te scannen. Dit zorgt ervoor dat alleen geautoriseerde kiezers toegang krijgen tot het stembiljet.

2.  **Het Digitale Stembiljet**:
    -   Na succesvolle verificatie wordt de kiezer doorgestuurd naar het digitale stembiljet (`pages/voting/index.php` en `vote/ballot.php`).
    -   Op deze pagina kan de kiezer een stem uitbrengen op een kandidaat voor De Nationale Assemblée (DNA) en een kandidaat voor de Ressortraden (RR). De interface toont de kandidaten en partijen op een overzichtelijke manier.

3.  **Stemming Bevestigen en Afronden**:
    -   Nadat de keuzes zijn gemaakt, wordt de stem definitief ingediend.
    -   Het systeem geeft een bevestiging en toont een bedankpagina (`pages/voting/thank-you.php` of `pages/voting/success.php`) om aan te geven dat het proces succesvol is afgerond. De sessie van de kiezer wordt hierna beëindigd om de privacy en veiligheid te waarborgen.