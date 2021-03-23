# FAU-oEmbed

Automatische Einbindung der FAU-Karten, Videos von FAU.tv, YouTube-Videos ohne Cookies, sowie weitere oEmbed-Quellen der FAU.


## Beschreibung


Dieses Plugin dient im Wesentlichen zwei Zwecken:
* Die aufbereitete Darstellung von Embeddings aus 
    * dem Videoportal der FAU, 
    * YouTube, 
    * dem Kartendienst der FAU 
    * Slideshare.


Registrierung von weiteren oEmbed Providern aus dem Bereich der FAU.


## Embeddings 


### FAU Videoportal    

Hier sind zwei Wege möglich:

Via URL aus der Adresszeile:

1. Rufen Sie ein Video im Videoportal auf.
2. Danach kopieren Sie die URL zum Video aus der Adresszeile des Browsers und fügen diese in die neue oder zu ändernde Seite ein.


Via Embed-URL:

1. Im Videoportal der FAU https://fau.tv das Video auswählen, dass im Blog eingebunden werden soll
2. Die Adresse des "Anschauen"-Links kopieren und in WP-Seite oder -Beitrag einfügen
3. Das Video wird automatisch auf der Seite eingebunden.



### FAU-Karten 

1. Im Kartengenerator des Kartendienstes der FAU https://karte.fau.de/generator anhand der Suchkriterien den richtigen Ausschnitt heraussuchen lassen.
2. Den "direkten Link zum iFrame" kopieren und in WP-Seite oder -Beitrag einfügen
3. Die Karte wird automatisch in der Größe eingebunden, die in der Einstellungsseite (Einstellungen - oEmbed) festgelegt ist.


#### Shortcode  ```[faukarte]```    

Alternativ kann ein Shortcode verwendet werden:

Shortcode zur Einbindung von Karten von https://karte.fau.de immer verwenden
* wenn die Breite oder Höhe in % angegeben werden soll (z.B. width="100%" zur besseren Darstellungen auf mobile devices)    
* wenn die URL von der Startseite verwendet werden soll    
* wenn die Ausgabe aus dem Kartengenerator mit einem anderen Zoom-Faktor angezeigt werden soll    


Shortcode ```[faukarte]```    

##### Parameter:
- url: 
    1. Adresse aus dem Kartengenerator ("direkter Link zum iFrame") ohne http://karte.fau.de/api/v1/iframe/ oder
    2. Adressausschnitt von http://karte.fau.de auswählen und Adresszeile kopieren
- width: Breite des anzuzeigenden Kartenausschnitts (Pixel-Angaben oder Prozent-Wert)
- height: Höhe des anzuzeigenden Kartenausschnitts (Pixel-Angaben oder Prozent-Wert)
- zoom: Zoomfaktor für den anzuzeigenden Kartenausschnitt (Wert zwischen 1 und 19, je größer der Wert desto größer die Darstellung)

##### Beispiel:
    
```[faukarte url="address/martensstraße 1" width="100%" height="100px" zoom="12"]```   



### YouTube 

Automatische Einbindung von YouTube-Videos bei Angabe der URL.    

- ohne Cookies
- ohne Anzeige ähnlicher Videos am Ende der Wiedergabe


### Slideshare 

Automatische Einbindung von YouTube-Videos bei Angabe der URL.    

- zusätzlich wird unterhalb des Videos der Link zur Prsentation angezeigt



##  Konfiguration

Einstellungsmenü: Einstellungen › FAU oEmbed    

- Aktivierung der jeweiligen automatischen Einbindung
- Festlegen der Standardwerte für eingebettete Objekte 


## Compatability

This Plugin is compatible to the Plugin Embed Privacy (https://wordpress.org/plugins/embed-privacy/).
If Embed Privacy  is active, this plugin will give control to Embed Privacy to display the content provider
and handles only our local providers, like FAU Karte or FAU.tv



