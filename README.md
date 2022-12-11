# Tahoma

Dieses Modul ist kompatibel mit IP-Symcon ab Version 6.x. Es ermöglicht die Kommunikation mit Somfy Rolladen/Aktoren und einer TaHoma Box (V2), TaHoma DIN-Rail und TaHoma Switch. Die Connexoon Box und der TaHoma Classic (V) ist nicht kompatibel und laut Somfy wird es auch kein Update dafür geben. Bitte zur Sicherheit vorher prüfen, ob der Developer Mode aktiviert werden kann!

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)  
2. [Voraussetzungen](#2-voraussetzungen)  
3. [Installation](#3-installation)  

## 1. Funktionsumfang

Steuerung von Somfy Geräten über die neue lokale Somfy API genannt "Developer Mode".

## 2. Voraussetzungen

 - IP-Symcon 6.x
 - TaHoma Modul aus dem Module Store

## 3. Installation

### a. TaHoma Gerät vorbereiten

Um die neue API nutzen zu können muss der Developer Mode auf dem Gerät aktiviert werden. Eine Anleitung von Somfy dazu befindet sich hier: https://developer.somfy.com/developer-mode

### b. Modul installieren

Die Verwaltungskonsile von IP-Symcon mit _http://{IP-Symcon IP}:3777/console/_ öffnen. 

Anschließend oben rechts auf das Symbol für den Modul Store klicken

Im Suchfeld nun

```
TaHoma
```  

eingeben und schließend das Modul auswählen und auf _Installieren_ klicken.

### b. Gateway/Geräte konfigurieren

Nach der Installation bietet der Module Store an, dass die Discovery Instanz erstellt wird. Dies bejahen. Sofern ein kompatibles TaHoma Gateway vor Ort ist, kann dies Erstellt werden. Im erstelleten Konfigurator sind dann alle kompatiblen Geräte direkt verfügbar und können ebenfalls erstellt werden. Über die Geräteinstanzen können dann die Rolladen direkt gesteuert werden und empfangen auch über den Rückkanal den Status sofern lokal änderungen durchgeführt werden.
