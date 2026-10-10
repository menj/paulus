# Licences

Paulus is the theme for apostleofdoom.org. It is built for that one site and is not distributed. This page says in one place what the theme is made of and on what terms. The complete licence texts that earlier sat in this file (the SIL Open Font License, the Apache License, the MIT licences and the long notice bundled with html2pdf.js) are linked below and are kept in the repository history, at commit `97246e5`.

## The theme

The theme's own code (PHP, CSS and JavaScript) is licensed under the [GNU General Public License, version 2 or later](https://www.gnu.org/licenses/gpl-2.0.html), the licence WordPress itself uses, and the one declared in `style.css`. The articles and illustrations come from the book *Paulus Perosak Risalah Al-Masih* by Mohd Elfie Nieshaem Juferi (Langgam Fikir, 2025) and belong to its author and publisher; the theme's licence covers code only.

Four small plugins are built into the theme as modules, each rewritten to keep the plugin's own settings: Unlist Posts & Pages by Nikschavan, Pretty Search Permalinks by Angel Costa, WPS Hide Login by WPServeur, Nicolas Kulka and wpformation, and Login Logo by Mark Jaquith. All four are licensed under the GPL, version 2 or later, like the theme, and their authors are credited here with thanks.

Two scripts are bundled for the reader. Lightbox2 2.12.0, which opens the figures at full size, is MIT-licensed, copyright 2015 Lokesh Dhakar. html2pdf.js 0.14.0, which saves a page as a PDF, is MIT-licensed, copyright 2026 Erik Koopmans, and carries its own libraries (html2canvas 1.4.1 by Niklas von Hertzen, jsPDF, canvg, DOMPurify, fflate, core-js and others), each under the MIT, Apache 2.0 or Mozilla Public License 2.0 terms its notices state. The MIT licence asks only that the copyright notice stay with the software, and it does.

## Typefaces

Three of the typefaces are free to use and embed. Cinzel is copyright 2020 The Cinzel Project Authors and EB Garamond is copyright 2017 The EB Garamond Project Authors; both are under the [SIL Open Font License 1.1](https://openfontlicense.org), which lets the fonts be used, embedded and redistributed freely as long as they are not sold by themselves and keep their copyright line. Special Elite is under the [Apache License 2.0](https://www.apache.org/licenses/LICENSE-2.0).

The Arabic script is set in three fonts by what it is. Quranic verses use KFGQPC Hafs Uthmanic Script, copyright 2010 King Fahd Glorious Quran Printing Complex, which grants free use, copying and distribution but forbids selling, modifying, altering, translating, reverse engineering or reproducing the font. The theme serves the supplied TrueType file re-encoded as WOFF2, with every glyph outline, advance width and character-map entry checked and unchanged; WOFF2 cannot carry the font's digital-signature table, which is the only thing left out. If KFGQPC requires its signed TrueType file to be served as supplied, that file replaces the WOFF2. Hadiths use Dubidam Arabic, and every other Arabic passage uses Arslan Wessam, which is distributed through Dev-Point.com with no licence file; its embedding flag permits web use, but the terms should be confirmed with its author.

Two typefaces are not free for a public site. Sabon Next LT is a Monotype font whose desktop licence does not cover web embedding, so a web font licence is required. Dubidam and Dubidam Arabic are NamelaType's free personal-use editions (copyright 2023 NamelaType, designed by Nur Syamsi and Bustanul Arifin); they replace digits and most punctuation with a watermark, so the theme asks them for letters only, and a site that sells a book needs the commercial licence, which also removes the watermark. Because the site is public, visitors download these fonts, so this point stands even though the theme itself is never shared.

## Icons

The social and platform icons come from the Minimalist Social & Platform Icons Pack, released under the GPL; 36 of its icons began as public-domain (CC0) marks and the rest are simple glyphs redrawn from each brand's own logo. Two are the exception: the LinkedIn and Scribd icons come from Font Awesome Free (CC BY 4.0), whose attribution is a condition of the licence. The credit to use wherever those two icons appear is "Icons by Font Awesome (fontawesome.com), CC BY 4.0". The brand names and logos remain trademarks of their owners, and the icons are used only to link to the author's own profiles.

## Images

Every photograph, painting and reproduction carries its author, licence and source in its credit line, from the figure registry in `inc/figures.php`. Most come from Wikimedia Commons under Creative Commons licences (CC BY or CC BY-SA, which require the credit shown and keep their licence when reused) or in the public domain. Five are from The Metropolitan Museum of Art's open access collection (CC0), one from the National Gallery of Art (CC0) and one from the Yale University Art Gallery (public domain). The portrait of Isma'il R. al Faruqi was supplied to the site's owner as a public-domain photograph in September 2026; its original publication and photographer are not recorded, and that supply is the basis on which it is credited. The engraved portraits of Paul and the portrait of Dale B. Martin, drawn from a still of the author's video, are the author's own work (MENJ, all rights reserved).
