<?php
include 'init.php';
$invmap=0;
$lonmin=$_GET['lonmin'];
$lonmax=$_GET['lonmax'];
$latmin=$_GET['latmin'];
$latmax=$_GET['latmax'];
$refimage_gid=$_GET['refimage_gid'];
$zlatmin=NULL;
$zlonmin=NULL;
$zlatmax=NULL;
$zlonmax=NULL;
$proj1=NULL;
$qid=NULL;
if(isset($_GET['zlatmin'])) $zlatmin=$_GET['zlatmin'];
if(isset($_GET['zlonmin'])) $zlonmin=$_GET['zlonmin'];
if(isset($_GET['zlatmax'])) $zlatmax=$_GET['zlatmax'];
if(isset($_GET['zlonmax'])) $zlonmax=$_GET['zlonmax'];
if(isset($_GET['qid'])) $qid=$_GET['qid'];
if(isset($scale)) {
	$scale=$_GET['scale'];
}else{
	$scale=-9999;
}
if($zlatmin==NULL) $zlatmin=-9999;
if($zlonmin==NULL) $zlonmin=-9999;
if($zlatmax==NULL) $zlatmax=-9999;
if($zlonmax==NULL) $zlonmax=-9999;

if(isset($_GET['invmap'])) $invmap=$_GET['invmap'];
if($invmap==NULL) $invmap = 0;

if(isset($_GET['proj1'])) $proj1=$_GET['proj1'];
if($proj1==NULL) $proj1 = "WGS84";

//alert($proj1);

$sql = sprintf("insert into dawei_logintime (username,logintime,qid) values ('%s', now(),'%s');",$username,$qid);
if (!($rs = pg_exec($sql))) {die;}
$sql = sprintf("update dawei_assignment set start_ts = now() where gid = '%s' and username = '%s' and start_ts is null;",$qid,$username);
if (!($rs = pg_exec($sql))) { die; }

?>
<html>
  <head>
    <script type="text/javascript">
      <!--
	  username = '<?php print $username?>';
	  qid='<?php print $qid?>';
	  // -->
    </script>
    <script type="text/javascript" src="../OpenLayers-2.12/lib/OpenLayers.js"></script> 
    <script type="text/javascript" src="../OpenLayers-2.12/lib/deprecated.js"></script>
    <script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>
    <script src="dojo-release-1.10.1/dojo/dojo.js" type="text/javascript" djConfig="parseOnLoad: true"></script>
    <script type="text/javascript" src="proj4js/lib/proj4js-compressed.js"></script>
    <script type="text/javascript" src="misc.openlayers.v2.js"></script>
    <script type="text/javascript" src="basemaps.openlayers.js"></script>
    <script type="text/javascript" src="panoramio.openlayers.js"></script>
    <script type="text/javascript" src="wms.tms.parallel.v2.js"></script>
    <script type="text/javascript" src="wms.tms.parallel.ui.js"></script>
    <link href="style.openlayers.css" rel="stylesheet" type="text/css" />
    <style type="text/css">
      @import "dojo-release-1.10.1/dojo/resources/dojo.css";
      @import "dojo-release-1.10.1/dijit/themes/tundra/tundra.css";
      div.olLayerGoogleCopyright,
      div.olLayerGooglePoweredBy
      {
      display: none !important;
      }   </style>
    <title>Urban Mapping Web-GIS</title>
  </head>
  <body onload="init(<?php printf('%lf,%lf,%lf,%lf,\'%s\',%lf,%lf,%lf,%lf,%d,%s,\'%s\'',$lonmin,$latmin,$lonmax,$latmax,$username,$zlonmin,$zlatmin,$zlonmax,$zlatmax,$invmap,$proj1,$refimage_gid)?>)" class="tundra">
    <table width="100%" height="100%" cellpadding="0" cellspacing="0" >
      <tr>
	<td>
	  <form name="form_attr">
	    <table>
	      <tr>
		<td>Logged in as <?php print $username ?></td>
	<td>Region: <?php print $qid?></td>
		<td><div id="functionsDropDown"></div></td>
		<td id="vi" class="item">
		  Class:<select size="1" name="vi_input" onChange="updateAttributes(attribute);">
		    <option value="urban" selected>urban</option>
		    <option value="unknown">unknown</option>
		    <option value="pending">pending</option>
		    <option value="revise">revise</option>
		  </select>
		</td>
		<td id="note" class="item">
		  Note:<input type="text" name="note_input" onChange="updateAttributes();" value="" size="10">
		</td>
		<td id="scale" align="center" class="item"> </td>
	      </tr>
	    </table>
	  </form>
	</td>
	  <td valign="top">
	<form name="map2">
	    <table border="0">
	      <tr>
		<td  width="100px" align="center" class="item"><div id="nodelist"></div> </td>
		<td><div id="referenceMapDropDown"></div></td>
		<td><div id="panoramioDropDown"></div></td>
		<td><a href="assignregion.php">Back to assignment</a></td>
		<td><div id="lastSave"></div></td>
	      </tr>
	    </table>
	</form>
	  </td>
      </tr>
      <tr height="100%">
    <?php
    if($invmap == 0 || $invmap==NULL){
	print '
       <td width="50%" id="col1">
	 <div id="map1" style="height: 100%; background-color: #808080" unselectable = "on" user-select: none;></div>
       </td>
	<td width="50%" id="col2">
	  <div id="map2" style="height: 100%; background-color: #808080" unselectable = "on" user-select: none;></div>
	</td>
	';}else{
	print '
    <td width="50%" id="col1">
      <div id="map2" style="height: 100%; background-color: #808080" unselectable = "on" user-select: none;/>
    </td>
    <td width="50%" id="col2">
      <div id="map1" style="height: 100%; background-color: #808080" unselectable = "on" user-select: none;/>
    </td>
    ';
    }
?>
      </tr>
    </table>
  </body>
</html>
