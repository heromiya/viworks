var refimage, refimage_as;
dojo.require("dojo.parser");
dojo.require("dijit.form.Button");
dojo.require("dijit.form.DropDownButton");
dojo.require("dijit.Menu");
dojo.require("dijit.MenuItem");
dojo.require('dijit.PopupMenuItem');
dojo.addOnLoad(
    function() {
	var elem = dojo.byId("zoomTo10k");
	var button = new dijit.form.Button(
	    {label:"Zoom to 1:10000"}
	    ,elem
	);
	button.onClick = function() {
	    map1.zoomToScale(10000,true);
//	    map2.zoomToScale(10000,false);
	}   
    }
);
dojo.addOnLoad(
    function() {
	var elem = dojo.byId("exchangeWindows");
	var button = new dijit.form.Button(
	    {label:"Exchange windows"}
	    ,elem
	);
	button.onClick = function() {
	    inverseWindows();
	}   
    }
);
dojo.addOnLoad(
    function() {
	var elem = dojo.byId("projection");
	var button = new dijit.form.Button(
	    {label:"Change projection"}
	    ,elem
	);
	button.onClick = function() {
	    changeProjection();
	}   
    }
);

dojo.addOnLoad(
    function(){
        var menu = new dijit.Menu({label: "Functions"});
		var elem = dojo.byId("functionsDropDown");
        var button = new dijit.form.DropDownButton({
            label: "Functions",
            name: "functions",
            dropDown: menu,
            id: "progButton"
        },elem);

        var menuItemContrast = new dijit.MenuItem({
            label: "Enable constrast enhancement",
            onClick: function(){
		switch(map1.baseLayer.name){
		case "refimage":
		    map1.setBaseLayer(refimage_as);
		    menuItemContrast.setLabel("Disable contrast enhancement");
		    break;
		case "refimage_as":
		    map1.setBaseLayer(refimage);
		    menuItemContrast.setLabel("Enable contrast enhancement");
		    break;
		}
	    }
        });
        menu.addChild(menuItemContrast);
	
        var menuItemGraticule = new dijit.MenuItem({
            label: "Remove graticule",
            onClick: function(){
		switch(graticuleCtl1.active){
		case false:
		    graticuleCtl1.activate();
		    graticuleCtl2.activate();
		    menuItemGraticule.setLabel("Remove graticule");
		    break;
		case true:
		    graticuleCtl1.deactivate();
		    graticuleCtl2.deactivate();
		    menuItemGraticule.setLabel("Display graticule");
		    break;
		}
	    }
        });
	menu.addChild(menuItemGraticule);

        var menuItem3 = new dijit.MenuItem({
            label: "Zoom to 1:10000",
            onClick: function(){map1.zoomToScale(10000,true);}
        });
	menu.addChild(menuItem3);

        var menuItem4 = new dijit.MenuItem({
            label: "Exchange windows",
            onClick: function(){inverseWindows();}
        });
	menu.addChild(menuItem4);

       var menuItem5 = new dijit.MenuItem({
            label: "Change Projection",
            onClick: function(){chageProjection();}
        });
	menu.addChild(menuItem5);

		var menuItem6 = new dijit.MenuItem({
            label: "Browse work time",
            onClick: function(){window.open("worktime.stats.ind.php",null)}
        });
	menu.addChild(menuItem6);

/*		var menuItem_BrowseWorkTime = new dijit.MenuItem({
            label: "Browse working/rewarded time",
            onClick: function(){window.open("worktime.stats.ind.php",null)}
        });
	menu.addChild(menuItem_BrowseWorkTime);*/

	var menuItem7 = new dijit.MenuItem({
            label: "Browse manual (English)",
            onClick: function(){window.open("http://guam.heromiya.net/english/index.php?title=Web-based_Interface",null)}
        });
	menu.addChild(menuItem7);

        var menuItem8 = new dijit.MenuItem({
            label: "Frequently asked questions",
            onClick: function(){window.open("http://guam.heromiya.net/english/index.php?title=Frequently_Asked_Questions",null)}
        });
	menu.addChild(menuItem8);

        var menuExamples = new dijit.Menu();
	menuExamples.addChild(new dijit.MenuItem({
            label:"Drawing areal features with polygons",
            onClick: function(){window.open("wms.tms.parallel.examples.php?latmin=31.723799802243&lonmin=72.965696121177&latmax=31.745610552439&lonmax=72.984840054755&invmap=0",null)}
	}));
	menuExamples.addChild(new dijit.MenuItem({
            label:"Drawing roads with lines",
            onClick: function(){window.open("wms.tms.parallel.examples.php?latmin=8.0596917670288&lonmin=4.676296493164&latmax=8.1186574500122&lonmax=4.7280523983154&invmap=0",null)}
	}));
	menuExamples.addChild(new dijit.MenuItem({
            label:"Exclude forest and open space from urban",
            onClick: function(){window.open("wms.tms.parallel.examples.php?latmin=23.570852730677&lonmin=86.457957156932&latmax=23.592663480873&lonmax=86.47710109051&invmap=0",null)}
	}));
	
	menu.addChild(new dijit.PopupMenuItem({
            label:"Browse good works",
            popup:menuExamples
	}));
    }
);

dojo.addOnLoad(
    function(){
        var menu = new dijit.Menu({label: "Reference map"});
	var elem = dojo.byId("referenceMapDropDown");
        var button = new dijit.form.DropDownButton({
            label: "Reference map",
            name: "referenceMap",
            dropDown: menu,
            id: "progButtonReferenceMap"
        },elem);

        var menuItemGoogleHybrid = new dijit.MenuItem({
            label: "Google Hybrid",
            onClick: function(){map2.setBaseLayer(google_hybrid);}
        });
	menu.addChild(menuItemGoogleHybrid);

        var menuItemGoogleRoad = new dijit.MenuItem({
            label: "Google Road",
            onClick: function(){map2.setBaseLayer(google_maps);}
        });
	menu.addChild(menuItemGoogleRoad);

        var menuItemGoogleSatellite = new dijit.MenuItem({
            label: "Google Satellite",
            onClick: function(){map2.setBaseLayer(google_satellite);}
        });
	menu.addChild(menuItemGoogleSatellite);

        var menuItemBingHybrid = new dijit.MenuItem({
            label: "Bing Hybrid",
            onClick: function(){map2.setBaseLayer(bing_hybrid);}
        });
	menu.addChild(menuItemBingHybrid);

        var menuItemBingRoad = new dijit.MenuItem({
            label: "Bing Road",
            onClick: function(){map2.setBaseLayer(bing_road);}
        });
	menu.addChild(menuItemBingRoad);

        var menuItemBingAerial = new dijit.MenuItem({
            label: "Bing Aerial",
            onClick: function(){map2.setBaseLayer(bing_aerial);}
        });
	menu.addChild(menuItemBingAerial);

        var menuItemOSM = new dijit.MenuItem({
            label: "OpenStreetMap",
            onClick: function(){map2.setBaseLayer(osm);}
        });
	menu.addChild(menuItemOSM);

		var menuItemAGSImagery = new dijit.MenuItem({
            label: "ArcGIS Imagery",
            onClick: function(){map2.setBaseLayer(agsimagery);}
        });
	menu.addChild(menuItemAGSImagery);

        var menuItemAGSStreet = new dijit.MenuItem({
            label: "ArcGIS Street",
            onClick: function(){map2.setBaseLayer(agsstreet);}
        });
	menu.addChild(menuItemAGSStreet);

    }
);

dojo.addOnLoad(
    function(){
        var menu = new dijit.Menu({label: "Panoramio"});
	var elem = dojo.byId("panoramioDropDown");
        var button = new dijit.form.DropDownButton({
            label: "Panoramio",
            name: "referenceMap",
            dropDown: menu,
            id: "progButtonPanoramio"
        },elem);

        var menuItem0 = new dijit.MenuItem({
            label: "0",
            onClick: function(){
	    	vectorPano.setVisibility(false);
		map2.removePopup(PanoPopup); 
		PanoPopup.destroy();
		PanoPopup = null;
	    }
        });
	menu.addChild(menuItem0);

        var menuItem20 = new dijit.MenuItem({
            label: "20",
            onClick: function(){displayPanoramio(20);}
        });
	menu.addChild(menuItem20);

        var menuItem50 = new dijit.MenuItem({
            label: "50",
            onClick: function(){displayPanoramio(50);}
        });
	menu.addChild(menuItem50);

        var menuItem100 = new dijit.MenuItem({
            label: "100",
            onClick: function(){displayPanoramio(100);}
        });
	menu.addChild(menuItem100);

    }
);
