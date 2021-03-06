#! /bin/bash

DB=crowdsourcing

ZOOM=13
TILEMAX=`echo 2^$ZOOM-1 | bc`
ORIGINX=-20037508.342789244
ORIGINY=20037508.342789244
TILELEN=`echo "scale=10;20037508.342789244*2/(2^$ZOOM)" | bc`

LONMIN=97.960800
LONMAX=98.259065
LATMIN=14.046463
LATMAX=14.45

XMIN=`echo $LONMIN $LATMIN | proj +proj=merc +a=6378137 +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +k=1 | cut -f 1`
YMIN=`echo $LONMIN $LATMIN | proj +proj=merc +a=6378137 +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +k=1 | cut -f 2`
XMAX=`echo $LONMAX $LATMAX | proj +proj=merc +a=6378137 +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +k=1 | cut -f 1`
YMAX=`echo $LONMAX $LATMAX | proj +proj=merc +a=6378137 +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +k=1 | cut -f 2`

TILEX_MIN=`echo "($XMIN - $ORIGINX) / $TILELEN" | bc`
TILEX_MAX=`echo "($XMAX - $ORIGINX) / $TILELEN" | bc`
TILEY_MIN=`echo "($ORIGINY - $YMAX) / $TILELEN" | bc`
TILEY_MAX=`echo "($ORIGINY - $YMIN) / $TILELEN" | bc`
echo $TILEX_MIN $TILEX_MAX $TILEY_MIN $TILEY_MAX

psql -c "DROP TABLE IF EXISTS tmp_gmaptile_13_dawei; CREATE TABLE tmp_gmaptile_13_dawei (tilex integer, tiley integer, zoom integer); SELECT AddGeometryColumn('tmp_gmaptile_13_dawei','the_geom',900913,'POLYGON',2);" -d $DB
for TILEX in `seq $TILEX_MIN $TILEX_MAX`; do
    for TILEY in `seq $TILEY_MIN $TILEY_MAX`; do
        echo $TILEX $TILEY
        LRX=`echo "$ORIGINX + $TILELEN * $TILEX" | bc`
        LRY=`echo "$ORIGINY - $TILELEN * $TILEY" | bc`
        ULX=`echo "$ORIGINX + $TILELEN * ($TILEX + 1)" |bc`
        ULY=`echo "$ORIGINY - $TILELEN * ($TILEY + 1)" |bc`
        psql -q -d $DB -c "INSERT INTO tmp_gmaptile_13_dawei (tilex,tiley,zoom,the_geom) values ('$TILEX','$TILEY','$ZOOM',ST_GeometryFromText('POLYGON (($LRX $LRY, $LRX $ULY, $ULX $ULY, $ULX $LRY, $LRX $LRY))',900913));"
    done
done

psql -d $DB -c "DROP TABLE IF EXISTS dawei_target; CREATE TABLE dawei_target (tilex integer, tiley integer, zoom integer, refimage_gid varchar(64), priority integer); SELECT AddGeometryColumn('dawei_target','the_geom',900913,'POLYGON',2);"
for LANDSAT in LM11400501972356AAA04  LM11400501973026AAA05 LM21400501975331XXX01  LM21400501977032AAA04 LM21400501977320FAK03  LM21400501978351AAA02 LT41310501989002XXX02  LT51310502005038BKT00 LT51310502009017BKT00  LT51310502009337BKT00 LE71310501999334SGS00  LE71310502000321SGS00 LE71310502001339SGS00  LE71310502002310SGS00 LC81310502013348LGN00; do
    psql -q -d $DB -c "INSERT INTO dawei_target (tilex, tiley, zoom, the_geom, refimage_gid, priority) SELECT tilex, tiley, zoom, the_geom, '$LANDSAT', 1 from tmp_gmaptile_13_dawei;"
done

for LANDSAT in LE71310502003361ASN01 LE71310502004348PFS00 LE71310502005318SGS00 LE71310502006353SGS01 LE71310502007340EDC00 LE71310502011319PFS00 LE71310502012354PFS02; do
    psql -q -d $DB -c "INSERT INTO dawei_target (tilex, tiley, zoom, the_geom, refimage_gid, priority) SELECT tilex, tiley, zoom, the_geom, '$LANDSAT', 2 from tmp_gmaptile_13_dawei;"
done

exit 0
