#! /bin/bash

DB=crowdsourcing

ZOOM=13
TILEMAX=`echo 2^$ZOOM-1 | bc`
ORIGINX=-20037508.342789244
ORIGINY=20037508.342789244
TILELEN=`echo "scale=10;20037508.342789244*2/(2^$ZOOM)" | bc`

LONMIN=104.2
LONMAX=104.65
LATMIN=17.7
LATMAX=18.07

XMIN=`echo $LONMIN $LATMIN | proj +proj=merc +a=6378137 +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +k=1 | cut -f 1`
YMIN=`echo $LONMIN $LATMIN | proj +proj=merc +a=6378137 +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +k=1 | cut -f 2`
XMAX=`echo $LONMAX $LATMAX | proj +proj=merc +a=6378137 +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +k=1 | cut -f 1`
YMAX=`echo $LONMAX $LATMAX | proj +proj=merc +a=6378137 +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +k=1 | cut -f 2`

TILEX_MIN=`echo "($XMIN - $ORIGINX) / $TILELEN" | bc`
TILEX_MAX=`echo "($XMAX - $ORIGINX) / $TILELEN" | bc`
TILEY_MIN=`echo "($ORIGINY - $YMAX) / $TILELEN" | bc`
TILEY_MAX=`echo "($ORIGINY - $YMIN) / $TILELEN" | bc`
echo $TILEX_MIN $TILEX_MAX $TILEY_MIN $TILEY_MAX

psql -c "DROP TABLE IF EXISTS tmp_gmaptile_13_laos; CREATE TABLE tmp_gmaptile_13_laos (tilex integer, tiley integer, zoom integer); SELECT AddGeometryColumn('tmp_gmaptile_13_laos','the_geom',900913,'POLYGON',2);" -d $DB
for TILEX in `seq $TILEX_MIN $TILEX_MAX`; do
    for TILEY in `seq $TILEY_MIN $TILEY_MAX`; do
        echo $TILEX $TILEY
        LRX=`echo "$ORIGINX + $TILELEN * $TILEX" | bc`
        LRY=`echo "$ORIGINY - $TILELEN * $TILEY" | bc`
        ULX=`echo "$ORIGINX + $TILELEN * ($TILEX + 1)" |bc`
        ULY=`echo "$ORIGINY - $TILELEN * ($TILEY + 1)" |bc`
        psql -q -d $DB -c "INSERT INTO tmp_gmaptile_13_laos (tilex,tiley,zoom,the_geom) values ('$TILEX','$TILEY','$ZOOM',ST_GeometryFromText('POLYGON (($LRX $LRY, $LRX $ULY, $ULX $ULY, $ULX $LRY, $LRX $LRY))',900913));"
    done
done

psql -d $DB -c "DROP TABLE IF EXISTS laos_target; CREATE TABLE laos_target (tilex integer, tiley integer, zoom integer, refimage_gid varchar(64), priority integer); SELECT AddGeometryColumn('laos_target','the_geom',900913,'POLYGON',2);"
for LANDSAT in LC81270482013336LGN00 LE71270482000357SGS00 LT51270481994028BKT00 LT51270481996002CLT00 LT51270482006029BKT00 LT51270482006349BKT00 LT51270482009005BKT00 LM11360481972352AAA04 LM21360481975363GDS03; do
    psql -q -d $DB -c "INSERT INTO laos_target (tilex, tiley, zoom, the_geom, refimage_gid, priority) SELECT tilex, tiley, zoom, the_geom, '$LANDSAT', 1 from tmp_gmaptile_13_laos;"
done

#for LANDSAT in LE71310502003361ASN01 LE71310502004348PFS00 LE71310502005318SGS00 LE71310502006353SGS01 LE71310502007340EDC00 LE71310502011319PFS00 LE71310502012354PFS02; do
#    psql -q -d $DB -c "INSERT INTO dawei_target (tilex, tiley, zoom, the_geom, refimage_gid, priority) SELECT tilex, tiley, zoom, the_geom, '$LANDSAT', 2 from tmp_gmaptile_13_dawei;"
#done

exit 0
