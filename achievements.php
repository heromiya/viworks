<?php
include 'init.php';
$unfinished=$_POST['unfinished'];
$finished=$_POST['finished'];
$approve=$_POST['approve'];
$unapprove=$_POST['unapprove'];
$gid=$_POST['gid'];
if(isset($_GET['sbu'])) $sort_str = ",username";

if ($unfinished == 1) {
    $stm = $db->prepare("update dawei_assignment set end_ts = '9999-12-31' where gid = ?;"
			,array('text')
			,MDB2_PREPARE_MANIP);
    $stm->execute(array($gid));
}else if ($approve == 1) {
    $stm_del = $db->prepare("delete from dawei_approval where qid = ? and username = ?"
			,array('text','text')
			,MDB2_PREPARE_MANIP);
    $stm_ins = $db->prepare("insert into dawei_approval (qid, username) values (?,?);"
			,array('text','text')
			,MDB2_PREPARE_MANIP);
    $stm_del->execute(array($gid,$username));
    $stm_ins->execute(array($gid,$username));
}else if ($unapprove == 1){
    $stm = $db->prepare("delete from dawei_approval where qid = ? and username = ?;"
			,array('text','text')
			,MDB2_PREPARE_MANIP);
    $stm->execute(array($gid,$username));
}else if ($finished == 1){
    $stm = $db->prepare("update dawei_assignment set end_ts = now() where gid = ?;"
			,array('text')
			,MDB2_PREPARE_MANIP);
    $stm->execute(array($gid));
}

$sql = sprintf("select username
                      ,dawei_assignment.gid
                      ,end_ts
                      ,dawei_assignment.refimage_gid
                      ,ST_XMax(ST_Transform(the_geom,4326)) as lonmax
                      ,ST_XMin(ST_Transform(the_geom,4326)) as lonmin
                      ,ST_YMax(ST_Transform(the_geom,4326)) as latmax
                      ,ST_YMin(ST_Transform(the_geom,4326)) as latmin
		      ,ARRAY_TO_STRING(
			ARRAY(
			    SELECT dawei_approval.username FROM dawei_approval
			    WHERE dawei_approval.qid=dawei_assignment.gid
			    ORDER BY dawei_approval.username
			    ), '<br>') AS approver
                from dawei_assignment
                left join dawei_target on dawei_assignment.gid = dawei_target.tilex || '-' || dawei_target.tiley || '-' || dawei_target.refimage_gid 
                where end_ts is not null 
                  and end_ts < '2100-01-01'
		  and dawei_assignment.username = '" . $username . "'
                order by end_ts desc
				".$sort_str."
				;");
  if (!($rs = pg_exec($sql))) {
    die;
  }
    $nrow = pg_num_rows($rs);
    $html_finished = $html_finished . "<table border='1' style='border-width: thin; border-style: solid; border-collapse: collapse'>
			<tr><th>User name</th><th>Region #</th>
			    <th>Finished time</th>
			    <th>Overview</th>
			    <th>Revise</th>
			    <th>Mark as unfinished</th>
			    <th>Approve</th>
			    <th>Approved by</th>
			</tr>";
    for ($i = 0; $i < $nrow; $i++) {
	$row = pg_fetch_array($rs, $i);

	$lonmin=$row['lonmin'];
	$latmin=$row['latmin'];
	$lonmax=$row['lonmax'];
	$latmax=$row['latmax'];
	$refimage_gid=$row['refimage_gid'];
	$pixelsize=0.0001388888888888888888888;
	$thumbsize=96;
	$wmsthumb='http://'.$WEBHOST.'/cgi-bin/mapserv?map=/var/www/dawei/landsat.map&SERVICE=WMS&REQUEST=GetMap&VERSION=1.1.1&LAYERS='.$refimage_gid.'&WIDTH='.$thumbsize.'&HEIGHT='.$thumbsize.'&SRS=EPSG:4326&FORMAT=JPEG&BBOX=';
	$tiles='<a href="javascript:void(0);" onClick="writemapthumb('.$thumbsize.','.$lonmin.','.$latmin.','.$lonmax.','.$latmax.','.$i.','."'thumb'".",'".$refimage_gid.'\');" id="thumb'.$i.'">Thumbnail</a>';

	if(preg_match('/.*'.$username.'.*/',$row["approver"])){
		$approvestr = "<td>You have approved this.<br><form method='post' action='".$_SERVER['SCRIPT_NAME']."'><input type='hidden' name='unapprove' value='1'><input type='hidden' name='gid' value='".$row["gid"]."'><input name='submit_unapproval' type='submit' value='Cancel approval'></form></td>";
	    	}else{
		$approvestr = "<td><form method='post' action='".$_SERVER['SCRIPT_NAME']."'><input type='hidden' name='approve' value='1'><input type='hidden' name='gid' value='".$row["gid"]."'><input name='submit_approval' type='submit' value='Approve this'></form></td>";   
	}
    
	$html_finished = $html_finished . "<tr align='center'><td>" . $row["username"] . "</td>"
	    . "<td>" . $row["gid"] . "</td>"
	    . "<td>" . ereg_replace('\.[0-9]*$','',$row["end_ts"]) . "</td>"
	    . "<td>" . $tiles . "</td>"
	    . "<td><a href='wms.tms.parallel.v2.php?lonmin=". $row["lonmin"] ."&latmin=". $row["latmin"] ."&lonmax=". $row["lonmax"] ."&latmax=". $row["latmax"] ."&refimage_gid=".$row['refimage_gid']."'>Revise</a>" . "</td>"
	    . "<td><form method='post' action='".$_SERVER['SCRIPT_NAME']."'><input type='hidden' name='unfinished' value='1'><input type='hidden' name='gid' value='".$row["gid"]."'><input name='submit_unfinished' type='submit' value='Mark as unfinished'></form>
		</td>"
	    . $approvestr
	    . "<td>".$row["approver"]."</td>"
	    . "</tr>"
	    ;
    }
$html_finished = $html_finished . "</table>";

$sql = sprintf("select username
                      ,dawei_assignment.gid
                      ,dawei_assignment.refimage_gid
                      ,end_ts
                      ,ST_XMax(ST_Transform(the_geom,4326)) as lonmax
                      ,ST_XMin(ST_Transform(the_geom,4326)) as lonmin
                      ,ST_YMax(ST_Transform(the_geom,4326)) as latmax
                      ,ST_YMin(ST_Transform(the_geom,4326)) as latmin
		      ,ARRAY_TO_STRING(
			ARRAY(
			    SELECT dawei_approval.username FROM dawei_approval
			    WHERE dawei_approval.qid=dawei_assignment.gid
			    ORDER BY dawei_approval.username
			    ), '<br>') AS approver
                from dawei_assignment
                left join dawei_target on dawei_assignment.gid = dawei_target.tilex || '-' ||  dawei_target.tiley || '-' ||  dawei_target.refimage_gid
                where end_ts is not null 
                  and end_ts > '2100-01-01'
				  and dawei_assignment.username = '" . $username . "'
                order by end_ts desc;");
  if (!($rs = pg_exec($sql))) {
    die;
  }
    $nrow = pg_num_rows($rs);
    $html_unfinished = $html_unfinished . "<table border='1' style='border-width: thin; border-style: solid; border-collapse: collapse'>
			<tr><th>User name</th><th>Region #</th>
			    
			    <th>Overview: Click to show thumbnail</th>
			    <th>Revise</th>
			    <th>Mark as finished</th>
			    <th>Approve</th>
			    <th>Approved by</th>
			</tr>";
    for ($i = 0; $i < $nrow; $i++) {
	$row = pg_fetch_array($rs, $i);

	$lonmin=$row['lonmin'];
	$latmin=$row['latmin'];
	$lonmax=$row['lonmax'];
	$latmax=$row['latmax'];
	$refimage_gid=$row['refimage_gid'];
	$pixelsize=0.0001388888888888888888888;
	$thumbsize=96;
	$wmsthumb='http://'.$WEBHOST.'/cgi-bin/mapserv?map=/var/www/dawei/landsat.map&SERVICE=WMS&REQUEST=GetMap&VERSION=1.1.1&LAYERS='.$refimage.'&WIDTH='.$thumbsize.'&HEIGHT='.$thumbsize.'&SRS=EPSG:4326&FORMAT=JPEG&BBOX=';
	//	$tiles='<img height="'.$thumbsize.'" width="'.$thumbsize.'" src="'.$wmsthumb.$lonmin.','.$latmin.','.$lonmax.','.$latmax.'">';
	$tiles='<a href="javascript:void(0);" onClick="writemapthumb('.$thumbsize.','.$lonmin.','.$latmin.','.$lonmax.','.$latmax.','.$i.','."'thumb_unfinished'".",'".$refimage_gid.'\');" id="thumb_unfinished'.$i.'">Thumbnail</a>';

	if(preg_match('/.*'.$username.'.*/',$row["approver"])){
		$approvestr = "<td>You have approved this.<br><form method='post' action='".$_SERVER['SCRIPT_NAME']."'><input type='hidden' name='unapprove' value='1'><input type='hidden' name='gid' value='".$row["gid"]."'><input name='submit_unapproval' type='submit' value='Cancel approval'></form></td>";
	    	}else{
		$approvestr = "<td><form method='post' action='".$_SERVER['SCRIPT_NAME']."'><input type='hidden' name='approve' value='1'><input type='hidden' name='gid' value='".$row["gid"]."'><input name='submit_approval' type='submit' value='Approve this'></form></td>";   
	}
    
	$html_unfinished = $html_unfinished . "<tr align='center'><td>" . $row["username"] . "</td>"
	    . "<td>" . $row["gid"] . "</td>"
	    . "<td>" . $tiles . "</td>"
	    . "<td><a href='wms.tms.parallel.v2.php?lonmin=". $row["lonmin"] ."&latmin=". $row["latmin"] ."&lonmax=". $row["lonmax"] ."&latmax=". $row["latmax"] ."&refimage_gid=".$refimage_gid."'>Revise</a>" . "</td>"
	    . "<td><form method='post' action='".$_SERVER['SCRIPT_NAME']."'><input type='hidden' name='finished' value='1'><input type='hidden' name='gid' value='".$row["gid"]."'><input name='submit_unfinished' type='submit' value='Mark as finished'></form>
		</td>"
	    . $approvestr
	    . "<td>".$row["approver"]."</td>"
	    . "</tr>"
	    ;
    }
$html_unfinished = $html_unfinished . "</table>";

?>

<html>
<head>
<meta charset="UTF-8">
    <title>Achievements</title></head>
    <script type="text/javascript"> 
    <!-- 
    
function writemapthumb(thumbsize,lonmin,latmin,lonmax,latmax,rn,id,refimage_gid){
    wmsthumb='http://guam.csis.u-tokyo.ac.jp/cgi-bin/mapserv?map=/var/www/dawei/landsat.map&SERVICE=WMS&REQUEST=GetMap&VERSION=1.1.1&LAYERS='+refimage_gid+'&WIDTH='+thumbsize+'&HEIGHT='+thumbsize+'&SRS=EPSG:4326&FORMAT=JPEG&BBOX=';
    img='<img height="'+thumbsize+'" width="'+thumbsize+'" src="'+wmsthumb+lonmin+','+latmin+','+lonmax+','+latmax+'">';
    document.getElementById(id+rn).innerHTML = img;
}

// -->
</script>

<style type="text/css">
    <!--
td {
    font-size:0.8em;
}
td.item_header {
    font-size:0.8em;
}
th {
    font-size:0.8em;
}

// -->
</style>
<body>
<a href="assignregion.php">Back to assignment</a>
<h3>Finished regions</h3>
<?php echo $html_finished ?>
<h3>Regions marked as unfinished</h3>
<?php echo $html_unfinished ?>
</body>
</html>
