#! /bin/bash

cat > landsat.map <<EOF
MAP
  NAME "Dawei Landsat"
  UNITS dd
  EXTENT -180 -90 180 90
  PROJECTION 'proj=longlat' 'ellps=WGS84' 'datum=WGS84' 'no_defs' END
  IMAGETYPE jpeg

  OUTPUTFORMAT
    NAME "aggpng24"
    DRIVER AGG/PNG
    MIMETYPE "image/png"
    IMAGEMODE RGBA
    EXTENSION "png"
  END
OUTPUTFORMAT
  NAME jpeg
  DRIVER "AGG/JPEG"
  MIMETYPE "image/jpeg"
  IMAGEMODE RGB
  EXTENSION "jpg"
END
OUTPUTFORMAT
  NAME GTiff
  DRIVER "GDAL/GTiff"
  MIMETYPE "image/tiff"
  IMAGEMODE RGB
  EXTENSION "tif"
END

  WEB
    IMAGEPATH '/tmp/'
    IMAGEURL '/tmp/'
    METADATA
      'ows_title'           'Dawei Landsat'
      'ows_onlineresource'  'http://guam.csis.u-tokyo.ac.jp/cgi-bin/mapserv?map=/var/www/dawei/landsat.map'
      'ows_srs'             'EPSG:4326 EPSG:900913'
      'wms_srs' 'EPSG:4326 EPSG:900913'
      "ows_enable_request"   "*"
    END
  END

  LAYER
    NAME 'bg_aster4practice'
    TYPE RASTER
    DUMP true
    EXTENT -180 -90 180 90
    MAXSCALE 1000000
    TILEINDEX '/home/heromiya/asterurban/products/bg_aster.latlon.tiles.d/tileindex.shp'
    TILEITEM "LOCATION"
    METADATA
      'ows_title' 'bg_aster'
    END
    STATUS OFF
    PROJECTION
    'proj=longlat'
    'ellps=WGS84'
    'datum=WGS84'
    'no_defs'
    END
    OFFSITE  0 0 0
#    PROCESSING "SCALE=AUTO"
#    PROCESSING "CLOSE_CONNECTION=DEFER"
#    PROCESSING "OVERSAMPLE_RATIO=1.5"
  END
EOF

for FILE in `find /mnt/lv2/kimijima-landsat/fcc.epsg4326/ | grep "tif$" `; do
GID=`echo $FILE | sed 's/.*\([A-Z0-9]\{21\}\)\..*/\1/g'`
cat >> landsat.map <<EOF
  LAYER
    NAME '${GID}'
    TYPE RASTER
    DUMP true
    EXTENT -180 -90 180 90
    MAXSCALE 1000000
    DATA "$FILE"
    METADATA
      'ows_title' '${GID}'
    END
    STATUS OFF
    PROJECTION 'proj=longlat' 'ellps=WGS84' 'datum=WGS84' 'no_defs' END
    OFFSITE  0 0 0
  END

  LAYER
    NAME '${GID}_as'
    TYPE RASTER
    DUMP true
    EXTENT -180 -90 180 90
    MAXSCALE 1000000
    DATA "$FILE"
    METADATA
      'ows_title' '${GID}'
    END
    STATUS OFF
    PROJECTION 'proj=longlat' 'ellps=WGS84' 'datum=WGS84' 'no_defs' END
    OFFSITE  0 0 0
    PROCESSING "SCALE=AUTO"
    PROCESSING "CLOSE_CONNECTION=DEFER"
    PROCESSING "OVERSAMPLE_RATIO=1.5"
  END

EOF
done

for FILE in `find  /mnt/lv2/LaGURAM/products/quicklook -type f | grep "tif$"`; do
GID=`basename $FILE | sed 's/.*\([A-Z0-9]\{21\}\)\..*/\1/g'`
PROJ=`gdalinfo /mnt/lv2/LaGURAM/products/quicklook/P126/R052/L5126052_05220090114.TM-GLS2010.quicklook.tif -proj4 | grep +proj | sed "s/+//g; s/ /' '/g; s/''//g"`
cat >> landsat.map <<EOF
  LAYER
    NAME '${GID}'
    TYPE RASTER
    DUMP true
    MAXSCALE 1000000
    DATA "$FILE"
    METADATA
      'ows_title' '${GID}'
    END
    STATUS OFF
PROJECTION $PROJ END
    OFFSITE  0 0 0
  END

  LAYER
    NAME '${GID}_as'
    TYPE RASTER
    DUMP true
    MAXSCALE 1000000
    DATA "$FILE"
    METADATA
      'ows_title' '${GID}'
    END
    STATUS OFF
PROJECTION $PROJ END
    OFFSITE  0 0 0
    PROCESSING "SCALE=AUTO"
    PROCESSING "CLOSE_CONNECTION=DEFER"
    PROCESSING "OVERSAMPLE_RATIO=1.5"
  END

EOF
done

echo "END" >> landsat.map
exit 0
