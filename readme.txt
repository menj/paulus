=== Paulus ===
Contributors: menj
Requires at least: 6.7
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 2.77.9
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Twenty Twenty-Five child theme presenting the Islamic case against Paul of Tarsus, with a book landing page and starter content.

== Description ==

Paulus is the theme of Apostle of Doom, the English site of the book Paulus Perosak Risalah Al-Masih by Mohd Elfie Nieshaem Juferi. It is a block child theme of Twenty Twenty-Five, built for that one site.

The site reads as a case file: the man, six charges, the witnesses, answers to missionary claims and the verdict. The theme ships the book's chapters as 56 articles in three sections, with the reference pages, a Journal and a book page.

README.md is the full technical reference. Read UPGRADING.md before you update a live site; it also says what is planned next.

== Features ==

* A content installer for the articles, pages, Journal entries and menu. It keeps your edits field by field, and offers the theme's text beside any article you changed.
* A tabbed Theme Options screen under Appearance, with six named colour schemes and an optional first-century ornament layer.
* An author card and footer brand block, both filled from Theme Options.
* Reading aids: a progress bar, arrow-key paging, resume reading, a copy-link button and back to top. Each can be switched off.
* A Lightbox2 viewer for the figures, and Print and Save as PDF buttons on every article.
* Shortcodes for figures, charts, the Journal and the footer blocks, listed in README.md.
* Titles in the form "<page> | Apostle of Doom", with page descriptions handed to any active SEO plugin.
* Structured data. Alongside Rank Math, the theme adds only the nodes Rank Math does not print itself.
* A private login address, clean search addresses, and application passwords off until you switch them on.
* Security switches for XML-RPC, REST API writes and gambling spam, with a cleanup tool and reports.
* Maintenance mode, and an email when new fatal errors appear.
* Contextual links from the first mention of a subject to the page that treats it, on this site or another.
* Bundled fonts, illustrations and licensed photographs, each with its credit line.

== Usage ==

Open Appearance, Theme Options. Each tab is a card with a short note at the top. Switches turn a feature on or off, sliders show their value as you move them, and Save changes stores every tab at once.

* Book: titles, author, catalogue figures and the order link. The author card, the book page and the structured data read these fields.
* Catalogue record: the national library record, as the footer colophon prints it.
* Publisher: name, registration, address, telephone, email and website. The address and telephone go into the structured data only.
* Colors: pick a scheme from the cards. The screen takes the scheme at once, so you can judge it before you save. The ornament switch adds the textures and borders.
* Reading: the progress bar (where it shows, top or bottom, its height and its two colours), arrow-key paging, resume reading, the copy-link button and back to top. Leave a colour empty to follow the scheme.
* Media: stop WordPress making resized copies of uploads, or scaled copies of large ones.
* Login: the medallion or logo, the tagline and Greek inscription, application passwords and the security headers.
* Security: XML-RPC, REST API writes from a browser session only, the spam gate and 410 Gone for removed spam. The cleanup tools and reports load under the switches.
* Maintenance: maintenance mode for FTP updates, and the fatal-error email.
* Contextual links: links to this site and to other sites, the limit for each, your own phrases and the pages to leave alone.
* Journal: the title, introduction and search description of the Journal page.
* Front page: the hero, the front-page title and description, and the footer text and badges.
* Content: install or update the shipped content. Text you have edited is kept.

Appearance, Contextual Links holds a second, smaller set of link rules: approved domains, site-wide phrases, a per-post limit and whether links open in a new tab.

== Installation ==

Twenty Twenty-Five 1.5 or later must be installed first, and must stay installed, since Paulus inherits its templates, patterns and styles. It need not be active.

With the WordPress uploader:

1. Go to Appearance, Themes, Add New, Upload Theme, choose the Paulus zip, install it and activate it.
2. Select Install site content from the prompt on the dashboard or the front page (or under Appearance, Theme Options, Content). The installer also sets word-based permalinks and the site icon.

With FTP:

1. Turn on hidden files in your FTP program first. In FileZilla it is Server, Force showing hidden files. The theme has files whose names start with a dot (.htaccess), and they must go up too.
2. Upload the paulus folder into wp-content/themes under a temporary name, such as paulus-new.
3. When every file is up, rename the old folder (if there is one) to paulus-old, then at once rename paulus-new to paulus. Do the two renames one straight after the other, at a quiet hour, with Appearance, Themes closed. With Maintenance mode on, on Apache or LiteSpeed, visitors who ask for a page in the moment between them get the "Back shortly" page from the web server. On Nginx, or without that mode, they may see an error. And if WordPress opens that screen for an administrator while the folder is missing, it switches the site to another theme.
4. Activate Paulus if it is not active yet, then install the site content as above. Delete paulus-old once the site looks right.

The folder must be called paulus, all lower case.

Never upload WordPress itself (wp-admin, wp-includes and the files beside them) to a live folder that has no wp-config.php. In that state the WordPress installer answers anyone who finds it. Put wp-config.php in place first, or upload WordPress to a folder the web server does not serve (one outside public_html), then move it into place.

== Updating ==

Read UPGRADING.md first. Then:

1. Switch Maintenance mode on (Theme Options, Maintenance). Visitors see "Back shortly" while you work; you still see the site.
2. Upload the new version the same way as above, with hidden files shown.
3. Open any page as an administrator. The structure brings itself up to date, and an article you edited keeps your text.
4. Switch Maintenance mode off.

Uploading into the live folder file by file is riskier. The theme checks only its PHP files in inc/ and shows the maintenance page while one is missing; functions.php, the templates, the stylesheets and the images are not checked. Keep Maintenance mode on for the whole upload: on Apache and LiteSpeed the web server itself then serves the maintenance page, even while a PHP file is missing.

== Frequently Asked Questions ==

= Does the theme load anything from other servers? =

Not by default. Fonts, scripts, styles and images are all served from the theme folder. The one exception is anything you put in the footer badges yourself, such as a badge image hosted elsewhere. Sites updated from 2.74.0 or earlier keep the AI Visibility badge they showed before; a fresh install has none.

= Will an update overwrite my edits? =

No. The installer records what it shipped, and an article or page you changed keeps your text. The dashboard lists those articles so you can take the theme's text or keep yours.

= Why are some typefaces marked as needing a licence? =

Sabon Next LT and the Dubidam fonts are not free for a public site. LICENSES.md gives the terms of every font, image and script.

= I see "Back shortly" on the site. =

Either Maintenance mode is on, or the theme is incomplete on the server because an upload is still running or stopped part-way. Switch Maintenance mode off, or upload the missing files. Deleting the file .paulus-maintenance in the site's folder by FTP also ends maintenance.

= My host runs Nginx. =

The theme writes its server rules only on Apache and LiteSpeed. On Nginx, give your host the rules in README.md to add to the server settings.

= What is under Theme Options, Content? =

The installer. It shows which articles and pages are installed, and one button installs or updates the rest.

== Privacy ==

The theme sends no data to other servers and adds no analytics or tracking. It sets no cookies of its own.

It stores a few things on the site itself:

* The security log, up to 200 entries, records the time, the user, the request type and the IP address of each blocked or held action. Clear it from Theme Options, Security.
* The error watch reads the PHP error log and keeps a count of new errors. With the fatal-error email on, a summary goes to the site's administration address.
* Resume reading, when switched on, keeps the reader's place in that reader's own browser (localStorage). Nothing reaches the server.
* The Contextual Links screen remembers its last tab in the administrator's browser.

The share buttons are plain links. Facebook or X sees a visit only when a reader clicks one.

== Copyright ==

Paulus, Copyright 2023-2026 MENJ. The theme's code is licensed under the GNU General Public License v2 or later, like WordPress. Article text and illustrations are copyright Mohd Elfie Nieshaem Juferi and Langgam Fikir.

Bundled resources keep the licences of their owners and are not relicensed. LICENSES.md holds the full list.

* Cinzel and EB Garamond: SIL Open Font License 1.1.
* Special Elite: Apache License 2.0.
* Sabon Next LT: Monotype commercial font; a web font licence is required.
* Dubidam and Dubidam Arabic: NamelaType, free personal-use edition; a commercial licence is required for a public site that sells a book.
* KFGQPC HAFS Uthmanic Script: King Fahd Glorious Quran Printing Complex (free of cost to use, copy and distribute, not to be sold or modified), served as a WOFF2 conversion of the supplied file by the owner's decision of 2026-10-26, accepting that restriction. Licence text in assets/fonts/kfgqpc-hafs-uthmanic-script-LICENSE.txt.
* Arslan Wessam: distributed through Dev-Point.com with no licence file.
* Photographs and images: the licence stated in each figure's credit line (Wikimedia Commons, Pexels, Pixabay, The Met, the National Gallery of Art, Yale University Art Gallery); see LICENSES.md.
* Lightbox2 2.12.0 (MIT, Lokesh Dhakar): assets/vendor/lightbox2, with its LICENSE and unminified sources.
* html2pdf.js 0.14.0 (MIT, Erik Koopmans) and the libraries it bundles: assets/js/vendor, with html2pdf-LICENSE, the bundle's licence notices and the unminified bundle.
* Social icons: Minimalist Social & Platform Icons Pack (GPL), with LinkedIn and Scribd from Font Awesome Free (CC BY 4.0).

== Changelog ==

= 2.77.9 =
* Documentation only, and no article changes. A single source of truth joins the readme, the README, the upgrade notes and the changelog.
* The readme covers updating over FTP, the README gains its security and hosting sections, and the upgrade notes a roadmap.

= 2.77.8 =
* Old spam-era addresses such as /?p=739 answer 410 Gone.
* Development files uploaded with the theme (.git, tests and the like) are refused by the web server and removed on the first request after an update.
* Theme updates no longer fill the error log with Rank Math database errors, and bots asking for WordPress's own files get 403.

= 2.77.7 =
* The front page reads as one story, from the man to the charges, the witnesses, the replies and the book, with a short step line before each section.
* On phones narrower than 414 pixels the header no longer makes the page scroll sideways.

= 2.77.6 =
* CLAUDE.md and FARUQI.md move into the .claude folder, which the release zip leaves out and the web server never serves.
* On a site installed in a subfolder, the static error pages linked the home page and their stylesheets at the domain root. The theme now writes a copy of each into wp-content/paulus-errors/ with the site's own addresses, and the .htaccess lines on the Content tab point there.
* On a multisite install whose main site runs another theme, a subsite that runs Paulus now keeps the error pages up to date.

= 2.77.5 =
* On a multisite install, a theme switch on the main site leaves the error pages in place while another site still runs Paulus.
* The error pages card shows a failed write over another theme's error page as failed.

Earlier releases, back to 1.0.0, are in CHANGELOG.md in the theme's repository.

== Upgrade Notice ==

= 2.77.9 =
Documentation only. Nothing needs doing.

= 2.77.8 =
Upload with hidden files shown, so the new .htaccess files go up.

= 2.75.0 =
Theme Options has a new layout; every setting keeps its value. A fresh install no longer shows the AI Visibility footer badge.
