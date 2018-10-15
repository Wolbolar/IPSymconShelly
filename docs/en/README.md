[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-5.0%20%3E-green.svg)](https://www.symcon.de/forum/threads/38222-IP-Symcon-5-0-verf%C3%BCgbar)

# IPSymconShelly

Module for IP Symcon version 5.0 or higher. Enables control of [Shelly](https://shelly.cloud/ "Shelly")'s products from IP-Symcon.

## Documentation

**Table of Contents**

1. [Features](#1-features)  
2. [Requirements](#2-requirements)  
3. [Installation](#3-installation)  
4. [Function reference](#4-function_reference)  
5. [Configuration](#5-configuration) 
6. [Annex](#5-annex)  


## 1. Features

  - Control of:
     - Shelly 1
     - Shelly Switch
     - Shelly 4 Pro
     - Shelly Plug
     - Shelly Bulb
     - Shelly Sense     
 - Energy consumption display  
  

## 2. Requirements

 - IPS 5.0
 - Shelly 1 / Shelly Switch / Shelly 4 Pro / Shelly Plug / Shelly Bulb / Shelly Sense

## 3. Installation

### a. Loading the module

Open the IP Symcon (min [Ver. 5.0](https://www.symcon.de/forum/threads/38222-IP-Symcon-5-0-verf%C3%BCgbar "IP-Symcon 5")) web console (*http://<IP-SYMCON IP>:3777/console/*). In the object tree, under core instances, open the instance __*modules*__ with a double mouse click.

![Modules](img/modules.png?raw=true "Modules")

In the _modules_ instance, press the button __*+*__ in the lower right corner.

![ModulesAdd](img/plus_add.png?raw=true "Hinzufügen")
 
Add the following URL in the window that opens:

```	
https://github.com/Wolbolar/IPSymconShelly  
```
    
and confirm with _OK_.

    
Then an entry for the module appears in the list of the instance _modules_


### b. Setup in IP-Symcon

In IP-Symcon, add a new instance with _object -> add instance_ (_CTRL + 1_ in the Legacy Console) and select _Shelly_.


### Webfront View


 ![Webfront](img/shelly_webfront.png?raw=true "Config IO")

## 4. Function reference

### Shelly Device:
 
**Power On**
```php
Shelly_PowerOn(int $InstanceID, int $id)
``` 
Parameter _$InstanceID_ ObjektID des Shelly Devices
Parameter _$id Nummer des Shelly Devices (1-4)

**Power Off**
```php
Shelly_PowerOff(int $InstanceID, int $id)
``` 
Parameter _$InstanceID_ ObjektID des Shelly Devices
Parameter _$id Nummer des Shelly Devices (1-4)

**Get power consumption**
```php
Shelly_GetPowerConsumption(int $InstanceID, int $id)
``` 
Parameter _$InstanceID_ ObjektID des Shelly Devices
Parameter _$id Nummer des Shelly Devices (1-4)


### ShellySplitter:




## 5. Configuration:


### Shelly Device:  

| Property        | Type    | Standard value | Function                                                              |
| :-------------: | :-----: | :------------: | :-------------------------------------------------------------------: |
| Devicetype      | string  |    -           | Type of device                                                        |
| Host            | string  |    -           | IP adress                               |
| UpdateIntervall | integer |  0             | Interval in seconds at which the data is fetched from the device and the status variables are updated |
| ExtendedInfo    | boolean |  false         | Select whether extended status variables are to be made available

## 6. Annex


#### Shelly Device:

GUID: `{C7FB21EB-BC3B-0304-E6DC-5BD74EA623C3}` 