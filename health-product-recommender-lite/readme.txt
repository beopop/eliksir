=== Health Product Recommender Lite ===
Contributors: BeoHosting
Tags: quiz, health, recommendations
Requires at least: 6.0
Tested up to: 6.5
Stable tag: 1.3.9
License: GPL2+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lagani, responzivni WordPress plugin koji generiše preporuke proizvoda na osnovu zdravstvenog upitnika.

== Changelog ==

= 1.3.1 =
* Improved GitHub update handler. Plugin now checks the `beopop/eliksir` repository for new releases and installs the bundled `health-product-recommender-lite.zip` asset.

= 1.3.2 =
* Fixed CSV/Excel export headers. Files now download correctly without theme output.

= 1.3.3 =
* Dodata podrška za GitHub token kako bi se plugin mogao ažurirati iz privatnog repozitorijuma.

= 1.3.4 =
* Popravljen Excel export. Fajl se sada ispravno generiše bez dodatnog izlaza.

= 1.3.5 =
* Excel export sada radi i kada PHP nema instaliran ZipArchive – u tom slučaju se generiše .xls fajl.
* Automatsko ažuriranje plugina je podrazumevano omogućeno.

= 1.3.6 =
* Uklonjen je Excel eksport, sada je moguće preuzeti samo CSV fajl.

= 1.3.7 =
* Na poslednjem koraku prikazuju se slika proizvoda i cena umesto tekstualnog dugmeta.

= 1.3.8 =
* Svakoj kombinaciji odgovora može se dodeliti objašnjenje preko klasičnog editora koje se prikazuje ispod predloženih proizvoda.

= 1.3.9 =
* Token za GitHub se sada dodaje direktno na URL paketa kako bi se izbegle greške "Download failed: Not Found" pri ažuriranju.
