=== Paulus ===
Contributors: menj
Requires at least: 6.7
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 2.77.28
License: GNU General Public License v3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

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
* On every article: the dates under the byline, an optional "Last reviewed" line, Key takeaways above the contents, and one line in the text pointing to its study questions.
* A Lightbox2 viewer for the figures, and Print and Save as PDF buttons on every article.
* Shortcodes for figures, charts, the Journal and the footer blocks, listed in README.md.
* Titles in the form "<page> | Apostle of Doom", with page descriptions handed to any active SEO plugin.
* Structured data. Alongside Rank Math, the theme adds only the nodes Rank Math does not print itself.
* A private login address, clean search addresses, and application passwords off until you switch them on.
* Security switches for XML-RPC, REST API writes, the user list and gambling spam, with a cleanup tool and reports.
* Maintenance mode, and an email when new fatal errors appear.
* Contextual links from the first mention of a subject to the page that treats it, on this site or another.
* Bundled fonts, illustrations and licensed photographs, each with its credit line.

== Usage ==

Open Appearance, Theme Options. Each tab is a card with a short note at the top. Switches turn a feature on or off, sliders show their value as you move them, and Save changes stores every tab at once.

* Book: titles, author, catalogue figures and the order link. The author card, the book page and the structured data read these fields.
* Catalogue record: the national library record, as the footer colophon prints it.
* Publisher: name, registration, address, telephone, email and website. The address and telephone go into the structured data only.
* Colors: pick a scheme from the cards. The screen takes the scheme at once, so you can judge it before you save. The ornament switch adds the textures and borders.
* Reading: the progress bar (where it shows, top or bottom, its height and its two colours), arrow-key paging, resume reading, the copy-link button and back to top. Leave a colour empty to follow the scheme. Two switches for articles: Key takeaways, and the line before the third section.
* Media: stop WordPress making resized copies of uploads, or scaled copies of large ones.
* Login: the medallion or logo, the tagline and Greek inscription, application passwords and the security headers.
* Security: XML-RPC, REST API writes from a browser session only, the user list hidden from visitors, the spam gate and 410 Gone for removed spam. The cleanup tools and reports load under the switches.
* Maintenance: maintenance mode for FTP updates, and the fatal-error email.
* Contextual links: links to this site and to other sites, the limit for each, your own phrases and the pages to leave alone.
* Journal: the title, introduction and search description of the Journal page, and three lead and list settings (category tabs, arrows and motion, an accent colour).
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

= Where do I write an article's key takeaways? =
In the Key takeaways and review box on the article's screen, one a line. The same box takes the date you last checked the article against its sources. Leave both empty and nothing shows.

= What is under Theme Options, Content? =

The installer. It shows which articles and pages are installed, and one button installs or updates the rest.

= My site is in a subfolder. Where does the browser icon go? =

Browsers ask for /favicon.ico at the top of the domain, such as example.com/favicon.ico, even when the site lives in example.com/blog/. WordPress never sees that request, so put a copy of the theme's favicon.ico in the domain root folder. The icon tags in each page cover the pages either way. If you have set a Site Icon, put that icon there, converted to an ICO file named favicon.ico, instead.

== Privacy ==

The theme sends no data to other servers and adds no analytics or tracking. It sets no cookies of its own.

It stores a few things on the site itself:

* The security log, up to 200 entries, records the time, the user, the request type and the IP address of each blocked or held action. Clear it from Theme Options, Security.
* The error watch reads the PHP error log and keeps a count of new errors. With the fatal-error email on, a summary goes to the site's administration address.
* Resume reading, when switched on, keeps the reader's place in that reader's own browser (localStorage). Nothing reaches the server.
* The Contextual Links screen remembers its last tab in the administrator's browser.

The share buttons are plain links. Facebook or X sees a visit only when a reader clicks one.

== Copyright ==

Paulus, Copyright 2023-2026 MENJ. The theme's code is licensed under the GNU General Public License v3 or later; the full text is in the LICENSE file. Article text and illustrations are copyright Mohd Elfie Nieshaem Juferi and Langgam Fikir.

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

= 2.77.28 =
* A newer text can now replace an older one safely. The old one redirects to the new one, drops out of lists, search, feeds and sitemaps, asks search engines not to index it, and moves to the Trash if you have not edited it. Under Theme Options, Content, a dry run shows what the next update would do, and the dashboard shows the differences between your text and the theme's before you choose. Updates stop removing anything if a shipped text fails its check or the item count drops by more than a tenth. Nothing removed, nothing renamed.

= 2.77.27 =
* The accessibility scan script reports a page that fails to load as a failure, and its usage line names the right folder. Nothing removed, nothing renamed.

= 2.77.26 =
* The claim that Rome banned gospels in 382 is corrected in the article on the gospels and the new covenant. Three Journal entries added, on Rome and 382, on Marcion and on Paul's thirteen or fourteen letters. Samuel Davidson's history of the canon is added to the Sources page. Nothing removed, nothing renamed.

= 2.77.25 =
* Accessibility pass (WCAG 2.2 AA): Arabic and Greek words inside English sentences now carry a language tag, so screen readers pronounce them. An axe audit script and a test were added. Nothing removed, nothing renamed.

= 2.77.24 =
* Phone layout pass: long words in titles, excerpts and search snippets wrap, wide tables and code blocks in pages scroll inside their own box, embeds scale with the column, and small links are easier to tap. The look on larger screens is unchanged.

= 2.77.23 =
* The lead and list on the Journal reacts. A row under the pointer or the keyboard focus gets a soft tint that grows past it, an accent bar on its left edge, a zoom on its thumbnail, an underlined accent title and an arrow. The first row carries the bar at rest.
* Each entry shows its category as a coloured tab that links to the category, with its date and reading time under the title. The large panel shows a Read arrow on hover and focus.
* The block fades up in turn as it scrolls into view. Visitors who ask for reduced motion see colour changes only.
* The Journal tab has three new settings: category tabs, arrows and motion, and an accent colour.

= 2.77.22 =
* Two more articles (Was Paul married?, The purse) quote the ESV.

= 2.77.21 =
* Ten Journal entries about Paul end on a verdict, a closing section that judges the evidence they set out.

= 2.77.20 =
* The Journal lists every entry again. The lead and list is now a block for featured entries only (one lead, three in the list); pointing at a list entry brings its picture forward.

= 2.77.19 =
* Five more articles quote the English Standard Version beneath the Greek: Tarsus and a persona for every audience, the flesh, the letters of a man, who was Paul of Tarsus, and the character of Paul. The rest follow in later releases.

= 2.77.18 =
* Four more pages quote the English Standard Version beneath the Greek: the answers to missionary claims, the Islamic dilemma, Saul in the Bible, and the meaning of the name Paul. The rest follow in later releases.

= 2.77.17 =
* Six more articles quote the English Standard Version beneath the Greek: Saul the persecutor, the three accounts compared, "Lord" before the Spirit, seven doctrines, suffering that atones, and by their fruits. The rest follow in later releases.

= 2.77.16 =
* Seven more articles quote the English Standard Version beneath the Greek: the citizenship and the appeal to Caesar, the Roman, curses for dissenters, women, slaves and Caesar, James the brother, the parting with Barnabas, and "Them which say they are apostles". The rest follow in later releases.

= 2.77.15 =
* The settings and the readme speak of the other sites in plain words. Links to them stay.
* The readme says where the browser icon goes on a site in a subfolder: a copy of favicon.ico in the domain root.

= 2.77.14 =
* The Journal opens with the newest entry large, with its image credit, and the next five in a list beside it. The other entries follow as before. With fewer than five entries, or a newest entry without an image, the Journal looks as it did.

= 2.77.13 =
* The browser icon set is complete: favicon.ico, an SVG icon, 32px, touch, 192px and 512px icons and a web app manifest, all from the portrait the site icon uses. Until the site icon is set, they show on the site, the login screen and the admin, and the site answers /favicon.ico with the icon. The error pages show it in the tab.

= 2.77.12 =
* The Study guide gives search engines its questions as an FAQPage again, word for word, as in 2.77.10. Search Console may list them as questions without answers. The owner keeps them for AI and answer search. The questions on the page are unchanged.

= 2.77.11 =
* The Study guide no longer gives search engines an FAQPage. The page shows questions without answers, and Google counts an FAQPage question without a shown answer as an error. The questions on the page are unchanged.

= 2.77.10 =
* Each article shows the date it was published, and the date it was updated, under the byline. A "Last reviewed" date and Key takeaways show once you fill them in on the article's screen.
* One line in the middle of an article points to its study questions, or to the next article.
* The Study guide's questions are given to search engines as an FAQPage, word for word.
* Visitors who are not logged in can no longer list the site's accounts through the REST API or ?author= addresses.
* The theme is now licensed under the GNU General Public License, version 3 or later. The full text is in the LICENSE file.

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

Earlier releases, back to 1.0.0, are in CHANGELOG.md in the theme's repository.

== Upgrade Notice ==

= 2.77.28 =
Upload the whole theme folder. Nothing else to do.

= 2.77.27 =
Upload the whole theme folder. Nothing else to do.

= 2.77.26 =
Upload the whole theme folder, then run Install or update under Theme Options, Content. Three Journal entries arrive on their dates, and edited texts are offered beside your own.

= 2.77.25 =
Upload the whole theme folder. Nothing else to do.

= 2.77.24 =
Upload the whole theme folder. Nothing else to do.

= 2.77.23 =
Upload the whole theme folder. Nothing else to do; the three new Journal tab settings start with everything on.

= 2.77.22 =
Two more articles move to the ESV. Unedited pages update on their own.

= 2.77.21 =
Ten Journal entries gain a closing verdict. Unedited entries update on their own.

= 2.77.20 =
The Journal shows every entry again; tick "Featured" on four entries to show the featured block.

= 2.77.19 =
Upload the whole theme folder. Five articles change their English quotations from the King James Version to the English Standard Version; nothing else needs doing.

= 2.77.18 =
Upload the whole theme folder. Four pages change their English quotations from the King James Version to the English Standard Version; nothing else needs doing.

= 2.77.17 =
Upload the whole theme folder. Six articles change their English quotations from the King James Version to the English Standard Version; nothing else needs doing.

= 2.77.16 =
Upload the whole theme folder. Seven articles change their English quotations from the King James Version to the English Standard Version; nothing else needs doing.

= 2.77.15 =
Upload the whole theme folder. On a site in a subfolder, also put a copy of assets/images/favicon.ico in the domain root folder (or, with a Site Icon set, that icon converted to an ICO file named favicon.ico); otherwise nothing to do.

= 2.77.14 =
Upload the whole theme folder. Nothing else needs doing.

= 2.77.13 =
Upload the whole theme folder. Open one admin page so the error pages are written again with the icon.

= 2.77.12 =
Upload the whole theme folder. Nothing else needs doing. Search Console may report the Study guide's questions as missing answers; the owner keeps them on purpose.

= 2.77.11 =
Upload the whole theme folder. Nothing else needs doing. Search Console's FAQ error on the Study guide clears once Google reads the page again.

= 2.77.10 =
Upload the whole theme folder. Key takeaways and "Last reviewed" are written on an article's screen. If something outside the site reads the user list through the REST API, switch "Hide the user list" off under Security.

= 2.77.9 =
Documentation only. Nothing needs doing.

= 2.77.8 =
Upload with hidden files shown, so the new .htaccess files go up.

= 2.75.0 =
Theme Options has a new layout; every setting keeps its value. A fresh install no longer shows the AI Visibility footer badge.
