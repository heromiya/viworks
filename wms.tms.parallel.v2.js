var map1Nav = new OpenLayers.Control.Navigation();
var invokeDraw = new OpenLayers.Control.Button();
var invokeDrawPath = new OpenLayers.Control.Button();
var invokeEdit = new OpenLayers.Control.Button();
var zbound;

function reload(qid){
    var zCoords =map1.getExtent().toArray();
    var zlonmin=zCoords[0];
    var zlatmin=zCoords[1];
    var zlonmax=zCoords[2];
    var zlatmax=zCoords[3];
    document.location ='wms.tms.parallel.v2.php?lonmin=' + extlonmin + '&latmin=' + extlatmin + '&lonmax=' + extlonmax + '&latmax=' + extlatmax + '&zlatmin=' + zlatmin + '&zlonmin=' + zlonmin +  '&zlatmax=' + zlatmax + '&zlonmax=' + zlonmax + '&qid=' + qid;
}

function init(lonmin,latmin,lonmax,latmax,username,zlonmin,zlatmin,zlonmax,zlatmax,invmaparg, proj1,refimage_gid) {
    extlonmin = lonmin;
    extlatmin = latmin;
    extlonmax = lonmax;
    extlatmax = latmax;
    invmap = invmaparg;
    var mapextent_wgs84 = new OpenLayers.Bounds(lonmin,latmin,lonmax,latmax);
//    var mapextent_tms = new OpenLayers.Bounds(lonmin,latmin,lonmax,latmax);
    var mapextent_tms = new OpenLayers.Bounds(lonmin,latmin,lonmax,latmax).transform(WGS84,TMS);
    var graticuleLineSymbolizer = new OpenLayers.Symbolizer.Line({
	strokeColor: "#dddddd",
	strokeWidth: 1,
	strokeOpacity: 0.8
    });
    graticuleCtl1 = new OpenLayers.Control.Graticule({
        numPoints: 2, 
        labelled: false,
	intervals: [4.166666666,0.833333333,0.4166666666,0.0833333333,0.04166666666,0.00833333333],
	lineSymbolizer: graticuleLineSymbolizer
    });
    graticuleCtl2 = new OpenLayers.Control.Graticule({
        numPoints: 2,
        labelled: false,
	intervals: [4.166666666,0.833333333,0.4166666666,0.0833333333,0.04166666666,0.00833333333],
	lineSymbolizer: graticuleLineSymbolizer
    });
    if(proj1 == "EPSG:4326" || proj1 == "WGS84") {
	var map1proj=WGS84;
	var unit1 = 'dd';
	var map1extent = new OpenLayers.Bounds(lonmin-buf,latmin-buf,lonmax+buf,latmax+buf);
    }else if(proj1 == "EPSG:900913" || proj1 == "TMS"){
	var map1proj=TMS;
	var unit1 = 'm';
	var map1extent = new OpenLayers.Bounds(lonmin-buf,latmin-buf,lonmax+buf,latmax+buf).transform(WGS84,TMS);
    }

    var map1Opts = {
	displayProjection: map1proj
	,projection: map1proj
	,units: unit1
	,numZoomLevels: 20
	,maxExtent: map1extent
	,controls:[
	    map1Nav,
	    new OpenLayers.Control.PanZoom()
    	,new OpenLayers.Control.MousePosition()
	    ,new OpenLayers.Control.KeyboardDefaults() 
	    ,new OpenLayers.Control.Scale()
		,new OpenLayers.Control.OverviewMap()
	    ,graticuleCtl1
	]
	,eventListeners: {
	    'zoomend': function(){
		if (map1.getExtent() != null && map2.getExtent() != null && Math.abs(map1.getZoom()-map2.getZoom()) > 0.8) {
		    map2.zoomToScale(map1.getScale(),true);
		    if(invokeDraw.active && map1.getScale() > maxscale+1){
			invokeDraw.deactivate();
			draw.deactivate();
			navigate.activate();
		    }
		    if(invokeDrawPath.active && map1.getScale() > maxscale+1){
			invokeDrawPath.deactivate();
			drawPath.deactivate();
			navigate.activate();
		    }
		    if(invokeEdit.active && map1.getScale() > maxscale+1){
			invokeEdit.deactivate();
			modify.deactivate();
			navigate.activate();
		    }
		    document.getElementById("scale").innerHTML = "Scale: 1:" + Math.round(map1.getScale());
		}
	    }
	    ,'move': function(){
		if (map1.getExtent() != null && map2.getExtent() != null )
		{
		    if (proj1 == "EPSG:4326") {
			if((Math.abs(map1.getCenter().transform(WGS84,TMS).lon - map2.getCenter().lon) > tol
			    || Math.abs(map1.getCenter().transform(WGS84,TMS).lat - map2.getCenter().lat) > tol))
			{
			    map2.setCenter(this.getCenter().transform(WGS84,TMS),map2.getZoom(),false,false);
			}
		    }else if (proj1 == "EPSG:900913"){
			if((Math.abs(map1.getCenter().lon - map2.getCenter().lon) > tol
			    || Math.abs(map1.getCenter().lat - map2.getCenter().lat) > tol))
			{
			    map2.setCenter(this.getCenter(),map2.getZoom(),false,false);
			}			
		    }
		}
	    }
	}
    }
    var map2Opts = {
	displayProjection: TMS
	,projection: TMS
	,units: "m"
	,numZoomLevels: 20
	,allOverlays: false
	,maxExtent: new OpenLayers.Bounds(lonmin-buf,latmin-buf,lonmax+buf,latmax+buf).transform(WGS84, TMS)
	,controls:[
	    new OpenLayers.Control.Navigation()
	    ,new OpenLayers.Control.PanZoom()
    	,new OpenLayers.Control.Scale()
    	,new OpenLayers.Control.MousePosition()
//		,new OpenLayers.Control.OverviewMap()
	    ,graticuleCtl2
	]
	,eventListeners: {
	    'zoomend': function(){
		if (map1.getExtent() != null && map2.getExtent() != null 
			&& Math.abs(map1.getZoom()-map2.getZoom()) > 0.8 	
			&& map1.getScale() < 9000 && map1.getScale() > 11000){
		    map1.zoomToScale(map2.getScale(),true);
		}
		    document.getElementById("scale").innerHTML = "Scale: 1:" + Math.round(map1.getScale());
	    }
	    ,'move': function(){
		if (map1.getExtent() != null && map2.getExtent() != null )
		{
		    //alert(proj1);
		    if (proj1 == "EPSG:4326") {
			if((Math.abs(map1.getCenter().transform(WGS84,TMS).lon - map2.getCenter().lon) > tol
			    || Math.abs(map1.getCenter().transform(WGS84,TMS).lat - map2.getCenter().lat) > tol))
			{
			    map1.setCenter(this.getCenter().transform(TMS,WGS84),map1.getZoom(),false,false);
			}
		    }
		    else if (proj1 == "EPSG:900913"){
			if((Math.abs(map1.getCenter().lon - map2.getCenter().lon) > tol
			    || Math.abs(map1.getCenter().lat - map2.getCenter().lat) > tol))
			{
			    map1.setCenter(this.getCenter(),map1.getZoom(),false,false);
			}			
		    }
		}
	    }
	}
    }
    map2 = new OpenLayers.Map('map2',map2Opts );
    map1 = new OpenLayers.Map('map1',map1Opts);

    var wfs_layer = new OpenLayers.Layer.Vector(
	"Editable Features"
	,{
	    strategies: [
		new OpenLayers.Strategy.BBOX()
		, saveStrategy
	    ]
	    ,styleMap: new OpenLayers.StyleMap(style)
	    ,protocol: new OpenLayers.Protocol.WFS(
		{
		    version: "1.1.0",
		    srsName: proj1,
		    url: "http://guam.csis.u-tokyo.ac.jp:28080/geoserver/wfs",	
		    featureNS :  "http://www.opengeospatial.net/cite",
		    maxExtent: mapextent_wgs84,
		    featureType: "dawei_viworks_" + username,
		    geometryName: "the_geom",
		   schema: "http://guam.csis.u-tokyo.ac.jp:28080/geoserver/wfs/DescribeFeatureType?version=1.1.0&typename=cite:dawei_viworks_" + username
		})
	    ,eventListeners: {
		"loadstart": function(){
		    document.getElementById("nodelist").innerHTML = "<img src='loader.gif'> Loading ...";
		},
		"loadend": function(){
		    document.getElementById("nodelist").innerHTML = "<img src='Check-icon.png'> Completed.";
		    setTimeout("document.getElementById('nodelist').innerHTML = ''",5000);
		}
	    }
	    ,filter:  new OpenLayers.Filter.Logical(
                {type: OpenLayers.Filter.Logical.AND,
                 filters: [
                     new OpenLayers.Filter.Comparison(
                         {type: OpenLayers.Filter.Comparison.EQUAL_TO,
                          property: "refimage",
                          value: refimage_gid
                         })
                 ]
                }
            )

	}
    );
    
    var mapextentPolygon_map1 = new OpenLayers.Layer.Vector("Target extent");

    refimage =
	new OpenLayers.Layer.WMS(
	    refimage_gid
	    ,"http://guam.csis.u-tokyo.ac.jp/cgi-bin/mapserv?map=/var/www/dawei/landsat.map&"
	    ,{
		layers: refimage_gid
		,format: 'image/jpeg'
		,srs: 'EPSG:4326'
		,isBaseLayer: true
	    }
	);
    refimage.name = "refimage";

    refimage_as =
	new OpenLayers.Layer.WMS(
	    "ASTER with enhanced color contrast"
	    ,"http://guam.csis.u-tokyo.ac.jp/cgi-bin/mapserv?map=/var/www/dawei/landsat.map&"
	    ,{
		layers:  refimage_gid+'_as'
		,format: 'image/jpeg'
		,srs: 'EPSG:4326'
		,isBaseLayer: true
	    }
	);
    refimage_as.name = "refimage_as";

    if (proj1 == "EPSG:4326"){
	var rectangle_map1 = new OpenLayers.Feature.Vector(
	    new OpenLayers.Geometry.LineString(
		[
		    new OpenLayers.Geometry.Point(lonmin, latmin)
		    ,new OpenLayers.Geometry.Point(lonmin, latmax)
		    ,new OpenLayers.Geometry.Point(lonmax, latmax)
		    ,new OpenLayers.Geometry.Point(lonmax, latmin)
		    ,new OpenLayers.Geometry.Point(lonmin, latmin)
		]
	    )
	);
	mapextentPolygon_map1.addFeatures(rectangle_map1);
        map1.addLayers([refimage,refimage_as,wfs_layer,mapextentPolygon_map1]);
        map1.zoomToExtent(mapextent_wgs84);
    }else if (proj1 == "EPSG:900913"){
	var rectangle_map1 = new OpenLayers.Feature.Vector(
	    new OpenLayers.Geometry.LineString(
		[
		    new OpenLayers.Geometry.Point(lonmin, latmin).transform(WGS84,TMS)
		    ,new OpenLayers.Geometry.Point(lonmin, latmax).transform(WGS84,TMS)
		    ,new OpenLayers.Geometry.Point(lonmax, latmax).transform(WGS84,TMS)
		    ,new OpenLayers.Geometry.Point(lonmax, latmin).transform(WGS84,TMS)
		    ,new OpenLayers.Geometry.Point(lonmin, latmin).transform(WGS84,TMS)
		]
	    )
	);
	mapextentPolygon_map1.addFeatures(rectangle_map1);
        map1.addLayers([aster,aster_as,wfs_layer,mapextentPolygon_map1]);
        map1.zoomToExtent(mapextent_tms);
    }

    var mapextentPolygon_map2 = new OpenLayers.Layer.Vector("Target extent");
    var rectangle_map2 = new OpenLayers.Feature.Vector(
	new OpenLayers.Geometry.LineString(
	    [
		new OpenLayers.Geometry.Point(lonmin, latmin).transform(WGS84,TMS)
		,new OpenLayers.Geometry.Point(lonmin, latmax).transform(WGS84,TMS)
		,new OpenLayers.Geometry.Point(lonmax, latmax).transform(WGS84,TMS)
		,new OpenLayers.Geometry.Point(lonmax, latmin).transform(WGS84,TMS)
		,new OpenLayers.Geometry.Point(lonmin, latmin).transform(WGS84,TMS)
	    ]
	)
    );

    mapextentPolygon_map2.addFeatures(rectangle_map2);
    map2.addLayers([google_hybrid,google_maps,google_satellite,bing_hybrid,bing_road,bing_aerial,osm,mapextentPolygon_map2,agsimagery,agsstreet]);
    map2.zoomToExtent(mapextent_tms);

    wfs_layer.events.on({
        "featuremodified": function(event){
	    event.feature.state = OpenLayers.State.UPDATE;
	    saveStrategy.save();
	},
/*        "featureadded": function(event){
	    event.feature.state = OpenLayers.State.UPDATE;
	    saveStrategy.save();
	},*/
	"beforefeatureadded": function(event){
	    feature = event.feature;
	    if (feature.attributes.vi == null)
		feature.attributes.vi = 'urban';
	    if (feature.attributes.note == null)
		feature.attributes.note = '';
	    feature.attributes.refimage = refimage_gid;
		document.getElementById("vi").innerHTML = '<td id="vi" class="item">Class:<select size="1" name="vi_input" onChange="updateAttributes(attribute);">\
	    <option value="urban" selected>urban</option>\
	    <option value="unknown">unknown</option>\
	    <option value="pending">pending</option>\
	    <option value="revise">revise</option>\
	    </select></td>';
	},
	"sketchstarted": function(event){
	    map1Nav.deactivate();
	},
	"sketchcomplete": function(event){
	    map1Nav.activate();
	}
    });

    invokeDraw = new OpenLayers.Control.Button({
	title: "Draw New Polygons",
	trigger: function() {
    	    if(map1.getScale() > maxscale+1){
		alert("Please zoom into finer scale than 1:" + maxscale + " to draw new polygons.");
	    }else{
		invokeDrawPath.deactivate();
		drawPath.deactivate();
		navigate.deactivate();
		modify.deactivate();
		del.deactivate();
		attribute.deactivate();
		invokeEdit.deactivate();
		this.activate();
		draw.activate();
		redoDraw.activate();
		undoDraw.activate();
		cancelDraw.activate();
	    }
	},
	displayClass: "olControlDrawFeaturePolygon"
    });

    draw = new OpenLayers.Control.DrawFeature(
	wfs_layer
	,OpenLayers.Handler.Polygon
	,{handlerOptions: {holeModifier: "altKey"}}
	,{
	    title: "Draw Feature",
	    displayClass: "olControlDrawFeaturePolygon",
	    multi: false,
	}
    );

    invokeDrawPath = new OpenLayers.Control.Button({
	title: "Draw New Paths",
	trigger: function() {
    	    if(map1.getScale() > maxscale+1){
		alert("Please zoom into finer scale than 1:" + maxscale + " to draw new paths.");
	    }else{
		draw.deactivate();
		navigate.deactivate();
		modify.deactivate();
		del.deactivate();
		attribute.deactivate();
		invokeEdit.deactivate();
		invokeDraw.deactivate();
		this.activate();
		drawPath.activate();
		redoDraw.activate();
		undoDraw.activate();
		cancelDraw.activate();
	    }
	},
	displayClass: "olControlDrawFeaturePath"
    });

    drawPath = new OpenLayers.Control.DrawFeature(
	wfs_layer
	,OpenLayers.Handler.Path
	,{
	    title: "Draw Path",
	    displayClass: "olControlDrawFeaturePath",
	    multi: false,
	}
    );

    var modify = new OpenLayers.Control.ModifyFeature(
	wfs_layer
	,{
	    title: "Modify Feature"
	    ,displayClass: "olControlModifyFeature"
	    ,clickout: true
	    ,eventListeners: {
		deactivate: function(feature){
		    this.unselectFeature(feature);		    
		}
	    }

	}
    );

    invokeEdit = new OpenLayers.Control.Button({
	title: "Edit Polygons",
	trigger: function() {
    	    if(map1.getScale() > maxscale+1){
		alert("Please zoom into finer scale than 1:" + maxscale + " to edit polygons.");
	    }else{
		navigate.deactivate();
		invokeDraw.deactivate();
		draw.deactivate();
		invokeDrawPath.deactivate();
		drawPath.deactivate();
		del.deactivate();
		attribute.deactivate();
		this.activate();
		modify.activate();
		redoDraw.deactivate();
		undoDraw.deactivate();
		cancelDraw.deactivate();
	    }
	},
	displayClass: "olControlModifyFeature"
    });
    var save = new OpenLayers.Control.Button({
	title: "Save",
	 trigger: function(){
	     saveStrategy.save();
	     setTimeout('reload(qid)',2000) 
	},
	displayClass: "olControlSave"
    });

    attribute = new OpenLayers.Control.SelectFeature(
	wfs_layer
	,{
	    title: "Edit Attribute"
	    ,onSelect: onFeatureSelect
	    ,onUnselect: onFeatureUnselect
	    ,clickout: true
	    ,toggle: true
	    ,displayClass: "olControlSelectFeature"
	    ,selectStyle: selected
	    ,eventListeners: {
		activate: function(){
		    invokeDraw.deactivate();
		    draw.deactivate();
		    invokeDrawPath.deactivate();
		    drawPath.deactivate();
		    navigate.deactivate();
		    del.deactivate();
		    invokeEdit.deactivate();
		    redoDraw.deactivate();
		    undoDraw.deactivate();
		    cancelDraw.deactivate();
		},
		deactivate: function(feature){
		    this.unselectAll();		    
		}
	    }
	}
    );
        
    var del = new DeleteFeature(
	wfs_layer
	,{
	    title: "Delete Polygons",
	    eventListeners: {
		activate: function(){
		    invokeDraw.deactivate();
		    draw.deactivate();
		    invokeDrawPath.deactivate();
		    drawPath.deactivate();
		    attribute.deactivate();
		    navigate.deactivate();
		    invokeEdit.deactivate();
		    modify.deactivate();
		    redoDraw.deactivate();
		    undoDraw.deactivate();
		    cancelDraw.deactivate();
		}
	    }	    
	}
    );

    var navigate = new OpenLayers.Control.Navigation(
	{
	    title: "Pan Map"
	    ,eventListeners: {
		activate: function(){
		    invokeDraw.deactivate();
		    draw.deactivate();
		    invokeDrawPath.deactivate();
		    drawPath.deactivate();
		    attribute.deactivate();
		    del.deactivate();
		    invokeEdit.deactivate();
		    modify.deactivate();
		    redoDraw.deactivate();
		    undoDraw.deactivate();
		    cancelDraw.deactivate();
		}
	    }
	}
    );

    var panel = new OpenLayers.Control.Panel(
	{
	    displayClass: 'customEditingToolbar'
	    ,type: OpenLayers.Control.TYPE_TOGGLE
	}
    );

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
	'to':20,
	'minx': minx,
	'miny': miny,
	'maxx': maxx,
	'maxy': maxy,
	'size':'thumbnail'
    }   
    OpenLayers.loadURL(panoramiourl, parameters, this, panoFeatures);

    panel.addControls([navigate, del, attribute, invokeEdit, invokeDraw,invokeDrawPath,cancelDraw, redoDraw,undoDraw,save]);
    panel.defaultControl = navigate;						   
    map1.addControl(draw)
    map1.addControl(drawPath)
    map1.addControl(modify);
    map1.addControl(panel);
    map1.fractionalZoom =true;
    document.getElementById("scale").innerHTML = "Scale: 1:" + Math.round(map1.getScale());

    if(zlonmin != -9999 && zlonmax != -9999 && zlatmin != -9999 && zlatmax != -9999){
	if(map1.getProjection() == 'EPSG:4326') {
	    zbound = new OpenLayers.Bounds(zlonmin,zlatmin,zlonmax,zlatmax);
	}else if (map1.getProjection() == 'EPSG:900913') {
	    zbound = new OpenLayers.Bounds(zlonmin,zlatmin,zlonmax,zlatmax).transform(WGS84,TMS);
	}
        map1.zoomToExtent(zbound,true);
    }
};
