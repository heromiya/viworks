var selectedFeaturePano, selectControlPanoramio, panoramio_style,vectorPano, PanoPopup;
var panoVisible = false;
function panoFeatures(response) {
    var json = new OpenLayers.Format.JSON();
    var panoramio = json.read(response.responseText);
    var features = new Array(panoramio.photos.length);
   
    for (var i = 0; i < panoramio.photos.length; i++)
    {
	upload_date = panoramio.photos[i].upload_date;//"15 January 2007"
	owner_name = panoramio.photos[i].owner_name;//"ThoiryK"
	photo_id = panoramio.photos[i].photo_id;//444265
	longitude = panoramio.photos[i].longitude;//-82.350453000000002
	latitude = panoramio.photos[i].latitude;//23.136354000000001
	pheight = panoramio.photos[i].height;//75
	pwidth = panoramio.photos[i].width;//100
	photo_title = panoramio.photos[i].photo_title;//"Cafe, Calle and Capitol of Cuba"
	owner_url = panoramio.photos[i].owner_url;//"http://www.panoramio.com/user/57893"
	owner_id = panoramio.photos[i].owner_id;//57893
	photo_file_url = panoramio.photos[i].photo_file_url;//"http://mw2.google.com/mw-panoramio/photos/thumbnail/444265.jpg"
	photo_url = panoramio.photos[i].photo_url;//"http://www.panoramio.com/photo/444265"

        var fpoint = new OpenLayers.Geometry.Point(longitude,latitude);
	var proj = new OpenLayers.Projection("EPSG:4326");
	fpoint.transform(WGS84, TMS);
	var panoatt = {
	    'upload_date' : upload_date,
	    'owner_name':owner_name,
	    'photo_id':photo_id,
	    'longitude':longitude,
	    'latitude':latitude,
	    'pheight':pheight,
	    'pwidth':pwidth,
	    'pheight':pheight,
	    'photo_title':photo_title,
	    'owner_url':owner_url,
	    'owner_id':owner_id,
	    'photo_file_url':photo_file_url,
	    'photo_url':photo_url 
	}
	features[i] = new OpenLayers.Feature.Vector(fpoint,panoatt, panoramio_style);
    }

    var panoramio_style = new OpenLayers.StyleMap(OpenLayers.Util.applyDefaults(
	{ pointRadius: 7,
	  fillOpacity: 1,
	  externalGraphic: "panoramio-marker.png"
	},
	OpenLayers.Feature.Vector.style["default"]));
    vectorPano = new OpenLayers.Layer.Vector("Panoramio", {styleMap: panoramio_style, visibility: panoVisible});
    vectorPano.addFeatures(features);
    map2.addLayer(vectorPano);
    
    selectControlPanoramio = new OpenLayers.Control.SelectFeature(
	vectorPano,
	{
	    onSelect: onFeatureSelectPanoramio
	    , onUnselect: onFeatureUnselectPanoramio
	}
    );
    map2.addControl(selectControlPanoramio);
    selectControlPanoramio.activate();
}

function onPopupClose(evt) {
    selectControlPanoramio.unselect(selectedFeaturePano);
}
function onFeatureSelectPanoramio(feature) {
    selectedFeaturePano = feature;
    msg = "<p><a href='"+ feature.attributes.photo_url+
	"' target='_blank'><img src='http://mw2.google.com/mw-panoramio/photos/small/" + 
	feature.attributes.photo_id + ".jpg' border='0' alt='' width='200' height='200' ></a></p>";
    popup = new OpenLayers.Popup.FramedCloud(
	"chicken", 
	feature.geometry.getBounds().getCenterLonLat(),
	null,
	msg,
	null, true, onPopupClose
    );
    popup.autoSize = true;
    feature.popup = popup;
    PanoPopup = popup;
    map2.addPopup(popup);
}
function onFeatureUnselectPanoramio(feature) {
    map2.removePopup(feature.popup); 
    feature.popup.destroy();
    feature.popup = null;
}

function switchPanoramio(){
    if(document.map2.swtPano.checked==true){
	vectorPano.setVisibility(true);
    }else{
	vectorPano.setVisibility(false);
	map2.removePopup(PanoPopup); 
	PanoPopup.destroy();
	PanoPopup = null;
    }
}

function updatePanoramio(){
    selectControlPanoramio.unselectAll();
    map2.removeLayer(vectorPano);
    panoVisible=true;
    var ext = map2.getExtent().transform(TMS,WGS84);
    var minx = ext.left;
    var miny = ext.bottom;
    var maxx = ext.right;
    var maxy = ext.top;
    panoramiourl = "http://www.panoramio.com/map/get_panoramas.php";
    parameters = {
	'order':'popularity',
	'set':'full',
	'from':0,
	'to': document.map2.nPanoramio.value,
	'minx': minx,
	'miny': miny,
	'maxx': maxx,
	'maxy': maxy,
	'size':'thumbnail'
    }   
    OpenLayers.loadURL(panoramiourl, parameters, this, panoFeatures);
    document.map2.swtPano.checked=true;
}

function displayPanoramio(number){
    selectControlPanoramio.unselectAll();
    map2.removeLayer(vectorPano);
    panoVisible=true;
    var ext = map2.getExtent().transform(TMS,WGS84);
    var minx = ext.left;
    var miny = ext.bottom;
    var maxx = ext.right;
    var maxy = ext.top;
    panoramiourl = "http://www.panoramio.com/map/get_panoramas.php";
    parameters = {
	'order':'popularity',
	'set':'full',
	'from':0,
	'to': number,
	'minx': minx,
	'miny': miny,
	'maxx': maxx,
	'maxy': maxy,
	'size':'thumbnail'
    }   
    OpenLayers.loadURL(panoramiourl, parameters, this, panoFeatures);
    vectorPano.setVisibility(true);
}
