#!/bin/sh
cd ../../geo-mashup
/usr/local/Cellar/gettext/0.17/bin/xgettext --language=PHP --indent --keyword=__ --keyword=_e --keyword=_c --keyword=__ngettext:1,2 -s -n --from-code=UTF8 -o lang/GeoMashup.pot *.php
