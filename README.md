# IPS ownCloud Modul

Dieses Modul erzeugt eine Wecker Instance.

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang) 
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Funktionsreferenz](#4-funktionsreferenz) 
5. [Anhang](#5-anhang)

## 1. Funktionsumfang

 	ownCloud Kalender Modul für IPS 

## 2. Voraussetzungen

	- IPS 4.x
 
## 3. Installation

	- IPS 4.x  
        über das 'Modul Control' folgende URL hinzufügen:  
        `https://github.com/MCS-51/ownCloud.git`  


## 4. Funktionsreferenz
	
	OWN_Update( integer $InstanceID );

	Startet eine neue Abfrage des Kalenders.
	
	OWN_ModulSelfUpdate(integer $InstanceID );
	
	Wenn eine neuere Version des Moduls vorliegt, wird diese geladen.
	Diese Funktion kann auch automatisch bei jedem Kalender Update erfolgen.
	Einstellbar über die Instanz.


## 5. Anhang

**GUID's:**  
 `{F04E2782-9066-4E42-82A4-8EE1FACB0E48}`

**Changelog:**  
 Version 1.0:
  - Erstes Release
 
 Version 1.1:
  - Neu: Modul kann sich automatisch selbst updaten.
  - Neu: Wenn neuere Version vorhanden ist und automatisches Update aus, wird ein Hinweis im HTML Kalender angezeigt.
  - Neu: Wenn neuere Version vorhanden ist und automatisches Update aus, wird eine Variable auf true gesetzt.
  - Neu: Externes Script für Modifizierung des Titel, UserEvent und ReminderEvent.
  - Neu: Bei jährlichen Wiederholungen, wird die Anzahl der Wiederholungen hinter dem Termin angezeigt. (s. i. d. R. Geburtstage)
  - Neu: Variable nächster Termin jetzt mit Anzeige Datum und Uhrzeit
  - Fix: Variablen für Termine (ausser Kalender) werden auf ~TextBox gesetzt. Damit funktioniert auch der Zeilenumbruch
  - Fix: Wiederholungen täglich, wöchentlich, monatlich wurden nicht korrekt ausgewertet.
  