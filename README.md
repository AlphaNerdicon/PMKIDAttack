## PMKIDAttack

The module automates PMKID attack

![alt text](https://i.ibb.co/GdDrdKd/PMKIDAttack.png)

**Device:** Tetra/Nano
(Nano conversion done by:  Yearta92)
~code edited for the nano to use the sd directory due to the nanos root directory size limits

[![Demo video](https://i.ibb.co/wMf1BGg/PMKIDAttack-You-Tube.png)](https://youtu.be/AU2kAd3PUz8)

**Official topics for discussions:**
```
https://codeby.net/threads/6-wifi-pineapple-pmkidattack.66709
https://forums.hak5.org/topic/45365-module-pmkidattack/
```

**Module installation:**
```~Wifi Pineapple NAno~
ssh into pineapple nano

1. opkg update && opkg install git git-http (make sure you have a Micro SD card from this point, the pineapple root directory doesnt have the space for everything, i recommend a 16GB just to be safe)
2. cd /sd/modules/
3. opkg update && opkg install git git-http
4. git clone https://github.com/Yearta92/PMKIDAttack.git PMKIDAttack
5. chmod +x -R /sd/modules/PMKIDAttack/scripts
6. refresh browser interface
7. you should see the module
8. install dependancies

9. make sure PineAP is on before scanning (note: during scan if you have it set to 'live' the pineap will kick off towards the end of the scan, dont worry, just stop the scan to enable the attack buttons)


~original module for the tetra can be found here: https://github.com/n3d-b0y/PMKIDAttack

```
