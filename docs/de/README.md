[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-5.0%20%3E-green.svg)](https://www.symcon.de/forum/threads/38222-IP-Symcon-5-0-verf%C3%BCgbar)

# IPSymconShelly

Modul für IP-Symcon ab Version 5.0. Ermöglicht die Steuerung von Produkten von [Shelly](https://shelly.cloud/ "Shelly") von IP-Symcon aus.

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)  
2. [Voraussetzungen](#2-voraussetzungen)  
3. [Installation](#3-installation)  
4. [Funktionsreferenz](#4-funktionsreferenz)  
5. [Konfiguration](#5-konfiguration)  
6. [Anhang](#6-anhang)  

## 1. Funktionsumfang

  - Steuerung von:
     - Shelly 1
     - Shelly Switch
     - Shelly 4 Pro
     - Shelly Plug
     - Shelly Bulb
     - Shelly Sense     
 - Energieverbrauch Anzeige  
  

## 2. Voraussetzungen

 - IPS 5.0
 - Shelly 1 / Shelly Switch / Shelly 4 Pro / Shelly Plug / Shelly Bulb / Shelly Sense

## 3. Installation

### a. Laden des Moduls

Die IP-Symcon (min [Ver. 5.0](https://www.symcon.de/forum/threads/38222-IP-Symcon-5-0-verf%C3%BCgbar "IP-Symcon 5")) Webkonsole öffnen ( *http://<IP-SYMCON IP>:3777/console/* ). Im Objektbaum unter Kerninstanzen die Instanz __*Modules*__ durch einen doppelten Mausklick öffnen.

![Modules](img/modules.png?raw=true "Modules")

In der _Modules_ Instanz rechts unten auf den Button __*+*__ drücken.

![ModulesAdd](img/plus_add.png?raw=true "Hinzufügen")
 
In dem sich öffnenden Fenster folgende URL hinzufügen:

```	
https://github.com/Wolbolar/IPSymconShelly  
```
    
und mit _OK_ bestätigen.    
    
Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_ 


### b. Einrichtung in IP-Symcon

In IP-Symcon nun neue Instanz mit _Objekt hinzufügen -> Instanz_ (_CTRL+1_ in der Legacy Konsole) hinzufügen, und _Shelly_ auswählen.


### Webfront Ansicht


 ![Webfront](img/shelly_webfront.png?raw=true "Config IO")

## 4. Funktionsreferenz

### Shelly Device:
 
**Power On**
```php
Shelly_PowerOn(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Shelly Devices

**Power Off**
```php
Shelly_PowerOff(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Shelly Devices


### ShellySplitter:




## 5. Konfiguration:


### Shelly Device:  

| Eigenschaft     | Typ     | Standardwert | Funktion                                                              |
| :-------------: | :-----: | :----------: | :-------------------------------------------------------------------: |
| Devicetype      | string  |    -          | Typ des Geräts                                                        |
| Host            | string  |    -          | IP Adresse                               |
| UpdateIntervall | integer |  0            | Intervall in Sekunden, in dem die Daten vom Gerät geholt werden und die Statusvariablen aktualisiert werden       |
| ExtendedInfo    | boolean |  false | Auswahl, ob erweiterte Statusvariablen zur Verfügung gestellt werden sollen

## 6. Anhang


#### Shelly Device:

GUID: `{C7FB21EB-BC3B-0304-E6DC-5BD74EA623C3}` 