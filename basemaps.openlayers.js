

var aster4practice =
    new OpenLayers.Layer.WMS(
	"ASTER"
	,"http://guam.csis.u-tokyo.ac.jp/cgi-bin/mapserv?map=/var/www/map/bg_aster.tiles.vi.map&"
	,{
	    layers: 'bg_aster,vi_urban_polygon,vi_urban_line,vi_unknown_polygon,vi_unknown_line'
	    ,format: 'image/png'
	    ,srs: 'EPSG:900913'
	    ,isBaseLayer: true
	}
    );
aster4practice.name = "aster4pracetice";


var aster_as4practice =
    new OpenLayers.Layer.WMS(
	"ASTER with enhanced color contrast"
	,"http://guam.csis.u-tokyo.ac.jp/cgi-bin/mapserv?map=/var/www/map/bg_aster.tiles.autoscale.map&"
	,{
	    layers: 'bg_aster'
	    ,format: 'image/jpeg'
	    ,srs: 'EPSG:4326'
	    ,isBaseLayer: true
	}
    );
aster_as4practice.name = "aster_as4practice";

var agsstreet = new OpenLayers.Layer.XYZ( "ESRI","http://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/${z}/${y}/${x}",{sphericalMercator: true});var agsimagery =  new OpenLayers.Layer.XYZ( "ESRI","http://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/${z}/${y}/${x}",{sphericalMercator: true});var agsphisical =  new OpenLayers.Layer.XYZ( "ESRI","http://server.arcgisonline.com/ArcGIS/rest/services/World_Physical_Map/MapServer/tile/${z}/${y}/${x}",{sphericalMercator: true});
				
//needed to override default of PNG for tiled services
				agsstreet.params.FORMAT = "jpg";
				agsimagery.params.FORMAT = "jpg";

var google_maps=new OpenLayers.Layer.Google("Google Maps",{numZoomLevels:20});var google_satellite=new OpenLayers.Layer.Google("Google Satellite",{type:google.maps.MapTypeId.SATELLITE});var google_hybrid=new OpenLayers.Layer.Google("Google Hybrid",{type:google.maps.MapTypeId.HYBRID});var osm=new OpenLayers.Layer.OSM();var BingAPIKey="ApbUJrB8FK-JwVvA89sxqcQWeMJJBwxszcNgdOUFb02xaUfZTBiEKa9EW9p9FHBU";var bing_road=new OpenLayers.Layer.Bing({key:BingAPIKey,type:"Road",metadataParams:{mapVersion:"v1"}});var bing_aerial=new OpenLayers.Layer.Bing({key:BingAPIKey,type:"Aerial"});var bing_hybrid=new OpenLayers.Layer.Bing({key:BingAPIKey,type:"AerialWithLabels",});
