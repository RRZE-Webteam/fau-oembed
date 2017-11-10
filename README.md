FAU-oEmbed
==========

Wordpress-Plugin
----------------

Automatische Einbindung der FAU-Karten (maps) und des FAU Videoportals bei Angabe der URL:

FAU Videoportal    

1. Im Videoportal der FAU https://fau.tv das Video auswählen, dass im Blog eingebunden werden soll
2. Die Adresse des "Anschauen"-Links kopieren und in WP-Seite oder -Beitrag einfügen
3. Das Video wird automatisch auf der Seite eingebunden.

FAU-Karten (funktioniert nur, wenn die Karte über die URL aus dem Kartengenerator und nicht von der Startseite angegeben wird)    

1. Im Kartengenerator des Kartendienstes der FAU https://karte.fau.de/generator anhand der Suchkriterien den richtigen Ausschnitt heraussuchen lassen.
2. Den "direkten Link zum iFrame" kopieren und in WP-Seite oder -Beitrag einfügen
3. Die Karte wird automatisch in der Größe eingebunden, die in der Einstellungsseite (Einstellungen - oEmbed) festgelegt ist.

Mehr Flexibilität bei der Einbindung über Shortcode:   

Shortcode zur Einbindung von Karten von https://karte.fau.de immer verwenden
- wenn die Breite oder Höhe in % angegeben werden soll (z.B. width="100%" zur besseren Darstellungen auf mobile devices)    
- wenn die URL von der Startseite verwendet werden soll    
- wenn die Ausgabe aus dem Kartengenerator mit einem anderen Zoom-Faktor angezeigt werden soll    

Shortcode [faukarte]    
Parameter:
- url: 
1. Adresse aus dem Kartengenerator ("direkter Link zum iFrame") ohne http://karte.fau.de/api/v1/iframe/ oder
2. Adressausschnitt von http://karte.fau.de auswählen und Adresszeile kopieren
- width: Breite des anzuzeigenden Kartenausschnitts (Pixel-Angaben oder Prozent-Wert)
- height: Höhe des anzuzeigenden Kartenausschnitts (Pixel-Angaben oder Prozent-Wert)
- zoom: Zoomfaktor für den anzuzeigenden Kartenausschnitt (Wert zwischen 1 und 19, je größer der Wert desto größer die Darstellung)
Beispiel:    
[faukarte url="address/martensstraße 1" width="100%" height="100px" zoom="12"]]    

Automatische Einbindung von YouTube-Videos bei Angabe der URL.    

- ohne Cookies
- ohne Anzeige ähnlicher Videos am Ende der Wiedergabe
- zusätzlich wird unterhalb des Videos der Link zum Video angezeigt

WP-Einstellungsmenü: Einstellungen › oEmbed    

- Aktivierung der jeweiligen automatischen Einbindung
- Festlegen der Standardwerte für eingebettete Objekte (Änderung von Steffi [faukarte] 29.6.15: Breite: 620; Höhe 300)
- Festlegen der Breite von YouTube-Videos
- Anzeige ähnlicher YouTube-Videos deaktivieren
