OpenLayers.ProxyHost = "/cgi-bin/proxy.cgi?url=";
var map1 = new OpenLayers.Map();
var map2 = new OpenLayers.Map();
var WGS84 = new OpenLayers.Projection("EPSG:4326");
var TMS = new OpenLayers.Projection("EPSG:900913");
var buf = 0.001;
var tol = 50;
var maxscale = 10000;
var alertstr, dstProj1;
var selectedFeature;
var wfs_layer = new OpenLayers.Layer.Vector();
//var wfs_layer_wgs84;
var draw,attribute,del,panel,map1,map2;
var extlonmin,extlatmin,extlonmax,extlatmax,invmap;
var graticuleCtl1, graticuleCtl2;
var menuItemContrast, menuItemGraticule;

function switchContrast(){
    alert(map1.baseLayer.name); 
    switch(map1.baseLayer.name){
    case "refimage":
	map1.setBaseLayer(refimage_as);
	break;
    case "refimage_as":
	map1.setBaseLayer(refimage);
	break;
    }
}

function switchRefLayer(){
    switch (document.map2.baselayer.value){
    case "google_maps":
	map2.setBaseLayer(google_maps);
	break;
    case "google_hybrid":
	map2.setBaseLayer(google_hybrid);
	break;
    case "google_satellite":
	map2.setBaseLayer(google_satellite);
	break;
    case "osm":
	map2.setBaseLayer(osm);
	break;
    case "bing_road":
	map2.setBaseLayer(bing_road);
	break;
    case "bing_aerial":
	map2.setBaseLayer(bing_aerial);
	break;
    case "bing_hybrid":
	map2.setBaseLayer(bing_hybrid);
	break;
    }
}

function showMsg(szMessage) {
    document.getElementById("nodelist").innerHTML = szMessage;
    setTimeout("document.getElementById('nodelist').innerHTML = ''",3000);
}

function showSuccessMsg(){
    showMsg("<img src='Save-icon.png'> Saved.");
    var nowdate = new Date();
    document.getElementById("lastSave").innerHTML = "Last save:" + (Number(nowdate.getMonth())+1) + "/" + nowdate.getDate() + " " + nowdate.getHours() + ":" + nowdate.getMinutes();
};
function showFailureMsg(){alert('Problems in data transaction occured. Please tell this to the system administrator.')};
var saveStrategy = new OpenLayers.Strategy.Save();
saveStrategy.events.register("success", '', showSuccessMsg);
saveStrategy.events.register("failure", '', showFailureMsg);
saveStrategy.auto = true;

function inverseWindows(){
    if(invmap == 0) {
	invmap = 1;
    }else{
	invmap = 0;
    }

    if(map1.getProjection() == 'EPSG:4326') {
        var zCoords = map1.getExtent().toArray();
	dstProj1 = 'WGS84';
    }else if (map1.getProjection() == 'EPSG:900913') {
        var zCoords =map1.getExtent().transform(TMS,WGS84).toArray();
	dstProj1 = 'TMS';
    }

    var zlonmin=zCoords[0];
    var zlatmin=zCoords[1];
    var zlonmax=zCoords[2];
    var zlatmax=zCoords[3];

    document.location ='wms.tms.parallel.v2.php?'
	+ 'lonmin=' + extlonmin
	+ '&latmin=' + extlatmin
	+ '&lonmax=' + extlonmax
	+ '&latmax=' + extlatmax
	+ '&zlatmin=' + zlatmin
	+ '&zlonmin=' + zlonmin
	+ '&zlatmax=' + zlatmax
	+ '&zlonmax=' + zlonmax
	+ '&invmap=' + invmap
	+ '&proj1=' + dstProj1;
}

function chageProjection(){
    if(map1.getProjection() == 'EPSG:4326') {
        var zCoords =map1.getExtent().toArray();
	dstProj1 = 'TMS';
    }else if (map1.getProjection() == 'EPSG:900913') {
        var zCoords =map1.getExtent().transform(TMS,WGS84).toArray();
	dstProj1 = 'WGS84';
    }

    var zlonmin=zCoords[0];
    var zlatmin=zCoords[1];
    var zlonmax=zCoords[2];
    var zlatmax=zCoords[3];

    document.location ='wms.tms.parallel.v2.php?'
	+ 'lonmin=' + extlonmin
	+ '&latmin=' + extlatmin
	+ '&lonmax=' + extlonmax
	+ '&latmax=' + extlatmax
	+ '&zlatmin=' + zlatmin
	+ '&zlonmin=' + zlonmin
	+ '&zlatmax=' + zlatmax
	+ '&zlonmax=' + zlonmax
	+ '&invmap=' + invmap
	+ '&proj1=' + dstProj1;
}

function attributeStr(feature){
    var furban, funknown, fpending,frevise;
    str = '<td id="vi" class="item">Class:';
    if(feature.attributes.vi == 'urban'){
	furban = "selected";
    }else if(feature.attributes.vi == 'unknown') {
	funknown = "selected";
    }else if(feature.attributes.vi == 'pending') {
	fpending = "selected";
    }else if(feature.attributes.vi == 'revise') {
	frevise = "selected";
    }
    str += '<select size="1" name="vi_input" onChange="updateAttributes(attribute);">\
	    <option value="urban" '+furban+'>urban</option>\
	    <option value="unknown" '+funknown+'>unknown</option>\
	    <option value="pending" '+fpending+'>pending</option>\
	    <option value="revise" '+frevise+'>revise</option>\
	    </select></td>';
    
    document.getElementById("vi").innerHTML = str;
    
    str = '<td id="note" class="item">Note:'
    if(feature.attributes.note == null){
	str += '<input type="text" name="note_input" onChange="updateAttributes(attribute);" value="">';
    }else{
	str += '<input type="text" name="note_input" onChange="updateAttributes(attribute);" value="' + feature.attributes.note + '">';
    }
    str += '</td>'
    document.getElementById("note").innerHTML = str;
}

function updateAttributes(attribute){
    selectedFeature.attributes.vi = document.form_attr.vi_input.value;
    selectedFeature.attributes.note = document.form_attr.note_input.value;
    selectedFeature.state = OpenLayers.State.UPDATE;
    saveStrategy.save();
    attribute.unselectAll();
};

function onFeatureSelect(feature) {
    attributeStr(feature);
    selectedFeature = feature;
}
function onFeatureUnselect(feature) {
    selectedFeature = feature;
}

var undoDraw = new OpenLayers.Control.Button({
    title: "Undo Draw",
    trigger: function() {
	if(draw.active == true){
	    draw.undo();
	}else if (drawPath.active == true){
	    drawPath.undo();
	}
    },
    displayClass: "olControlUndo"
});

var redoDraw = new OpenLayers.Control.Button({
    title: "Redo Draw",
    trigger: function() {
	if(draw.active == true){
	    draw.redo();
	}else if (drawPath.active == true){
	    drawPath.redo();
	}
    },
    displayClass: "olControlRedo"
});

var cancelDraw = new OpenLayers.Control.Button({
    title: "Cancel Draw",
    trigger: function() {
	if(draw.active == true){
	    draw.cancel();
	}else if (drawPath.active == true){
	    drawPath.cancel();
	}
    },
    displayClass: "olControlCancel"
});

var extentPolygonStyle =  new OpenLayers.Style(
    {},{
        fill: true,
        stroke: true,
	pointRadius: 5,
        fillOpacity: 1,
	strokeWidth: 4,
        zIndex: 0,
        fillColor: "#00000",
        strokeColor: "#FFFFFF"
    });
var selected = new OpenLayers.Style(
    {},{
        fill: true,
        stroke: true,
	pointRadius: 5,
        fillOpacity: "0.2",
	strokeWidth: 2,
        zIndex: 0,
        fillColor: "#999999",
        strokeColor: "#FFFFFF"
    });
var style = new OpenLayers.Style(
    {
        fill: true,
        stroke: true,
	pointRadius: 5,
        fillOpacity: "0.2",
	strokeWidth: 1,
        zIndex: 0,
    },
    {
        rules: [
            new OpenLayers.Rule({
                filter: new OpenLayers.Filter.Comparison({
                    type: OpenLayers.Filter.Comparison.NOT_EQUAL_TO,
                    property: "vi",
		    value: ""
                }),
                symbolizer: {
                    fillColor: "#99FF66",
                    strokeColor: "#33FF00"
                }
            }),
            new OpenLayers.Rule({
                filter: new OpenLayers.Filter.Comparison({
                    type: OpenLayers.Filter.Comparison.EQUAL_TO,
                    property: "vi",
                    value: "urban"
                }),
                symbolizer: {
                    fillColor: "#6666FF",
                    strokeColor: "#0000FF"
                }
            }),
            new OpenLayers.Rule({
                filter: new OpenLayers.Filter.Comparison({
                    type: OpenLayers.Filter.Comparison.EQUAL_TO,
                    property: "vi",
		    value: "non-urban"
                }),
                symbolizer: {
                    fillColor: "#FF6666",
                    strokeColor: "#FF0000"
                }
            }),
            new OpenLayers.Rule({
                filter: new OpenLayers.Filter.Comparison({
                    type: OpenLayers.Filter.Comparison.EQUAL_TO,
                    property: "vi",
		    value: "unknown"
                }),
                symbolizer: {
                    fillColor: "#FFFF66",
                    strokeColor: "#FF6600"
                }
            }),
            new OpenLayers.Rule({
                filter: new OpenLayers.Filter.Comparison({
                    type: OpenLayers.Filter.Comparison.EQUAL_TO,
                    property: "vi",
		    value: "pending"
                }),
                symbolizer: {
                    fillColor: "#cc6699",
                    strokeColor: "#cc0066"
                }
            }),
            new OpenLayers.Rule({
                filter: new OpenLayers.Filter.Comparison({
                    type: OpenLayers.Filter.Comparison.EQUAL_TO,
                    property: "vi",
		    value: "revise"
                }),
                symbolizer: {
                    fillColor: "#ffff99",
                    strokeColor: "#ffff00"
                }
            }),
        ]
    }
);


var DeleteFeature = OpenLayers.Class(OpenLayers.Control, {
    initialize: function(layer, options) {
	OpenLayers.Control.prototype.initialize.apply(this, [options]);
	this.layer = layer;
	this.handler = new OpenLayers.Handler.Feature(this, layer, {click: this.clickFeature});
    },
    clickFeature: function(feature) {
	/*if(feature.attributes.insert_user == username 
	   || feature.attributes.insert_user == undefined 
	   || feature.attributes.insert_user == 'heromiya'
	   || feature.attributes.insert_user == 'sawaidabbas'
	   || feature.attributes.insert_user == 'kimijimasatomi'
	   || feature.attributes.insert_user == 'katelom'
	   || feature.attributes.insert_user == 'yilamakadicaiaphas'
	   || feature.attributes.insert_user == 'nagai'
	   || feature.attributes.insert_user == 'isnaini'
	   || feature.attributes.insert_user == 'c51m50n'
	   || feature.attributes.insert_user == 'ify'
	   || feature.attributes.insert_user == 'samedy'	   
	   || feature.attributes.insert_user == 'yeye'
	   || feature.attributes.insert_user == 'intan'
	   || feature.attributes.insert_user == 'kagenuch'
	   || feature.attributes.insert_user == 'supatan'
	   || feature.attributes.insert_user == 'sazar1980'
	   || feature.attributes.insert_user == 'tansine'
	  ){*/
	    if(feature.fid == undefined) {
		this.layer.destroyFeatures([feature]);
	    } else {
		feature.state = OpenLayers.State.DELETE;
		this.layer.events.triggerEvent("afterfeaturemodified",
					       {feature: feature});
		feature.renderIntent = "select";
		this.layer.drawFeature(feature);
	    }
	    saveStrategy.save();
	/*}else{
	    alert("This feature is drawn by *" 
		  + feature.attributes.insert_user 
		  + "*. Please be sure that your assignment is not conflicting with assginment of *" 
		  + feature.attributes.insert_user + "*.");
	}*/
    },
    setMap: function(map) {
	this.handler.setMap(map);
	OpenLayers.Control.prototype.setMap.apply(this, arguments);
    },
    CLASS_NAME: "OpenLayers.Control.DeleteFeature"
});

var EditFeature = OpenLayers.Class(OpenLayers.Control, {
    initialize: function(layer, options) {
	OpenLayers.Control.prototype.initialize.apply(this, [options]);
	this.layer = layer;
	this.handler = new OpenLayers.Handler.Feature(this, layer, {click: this.clickFeature});
    },
    clickFeature: function(feature) {
	if(feature.fid == undefined) {
	    this.layer.destroyFeatures([feature]);
	} else {
	    feature.state = OpenLayers.State.DELETE;
	    this.layer.events.triggerEvent("afterfeaturemodified",
					   {feature: feature});
	    feature.renderIntent = "select";
	    this.layer.drawFeature(feature);
	}
	saveStrategy.save();
    },
    setMap: function(map) {
	this.handler.setMap(map);
	OpenLayers.Control.prototype.setMap.apply(this, arguments);
    },
    CLASS_NAME: "OpenLayers.Control.DeleteFeature"
});
