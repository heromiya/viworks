<?php
include 'init.php';
$unfinished=$_POST['unfinished'];
$approve=$_POST['approve'];
$unapprove=$_POST['unapprove'];
$paid=$_POST['paid'];
$unpaid=$_POST['unpaid'];
$reassign=$_POST['reassign'];
$searchkeyword=$_POST['searchkeyword'];
$page=$_GET['page'];
if (is_null($page)) {
$page = 1;
}
$gid=$_POST['gid'];
if(isset($_GET['sbu'])) $sort_str = "t.username,";
$filter_finished=$_POST['filter_finished'];
$filter_approved=$_POST['filter_approved'];
$filter_paid=$_POST['filter_paid'];

if ($unfinished == 1) {
    $stm = $db->prepare("update dawei_assignment set end_ts = '9999-12-31' where gid = ?;"
			,array('text')
			,MDB2_PREPARE_MANIP
			);
    $stm->execute(array($gid));
} else if ($approve == 1) {
    $stm_del = $db->prepare("delete from dawei_approval where qid = ? and username = ?"
			,array('text','text')
			,MDB2_PREPARE_MANIP);
    $stm_del->execute(array($gid,$username));
    
    $stm_ins = $db->prepare("insert into dawei_approval (qid, username) values (?,?);"
			    ,array('text','text')
			    ,MDB2_PREPARE_MANIP);
    $stm_ins->execute(array($gid,$username));

}else if ($unapprove == 1){
    $stm = $db->prepare("delete from dawei_approval where qid = ? and username = ?;"
			,array('text','text')
			,MDB2_PREPARE_MANIP);
    $stm->execute(array($gid,$username));
}else if ($reassign == 1){
    $stm_ins = $db->prepare("insert into dawei_assignment_deleted select * from  dawei_assignment where gid = ?"
			,array('text')
			,MDB2_PREPARE_MANIP);
    $stm_ins->execute(array($gid));
    $stm_del = $db->prepare("delete from dawei_assignment where gid = ?;"
			,array('text')
			,MDB2_PREPARE_MANIP);
    $stm_del->execute(array($gid));
}

if ($paid == 1) {
	pg_exec("update dawei_assignment set paid_timestamp = now() where gid = '".$gid."';");	
}
if ($unpaid == 1) {
	pg_exec("update dawei_assignment set paid_timestamp = '9999-12-31' where gid = '".$gid."';");	
}

if ($filter_finished==1){
	$whereclause="where t.end_ts is not null and t.end_ts != '9999-12-31' and t.start_ts is not null and approver !~ '.*kimijimasatomi.*'";
}
if ($filter_approved==1){
	$whereclause="where approver ~ '.*kimijimasatomi.*' and (paid_timestamp is null or paid_timestamp = '9999-12-31 00:00:00')";
}
if ($filter_paid==1){
	$whereclause="where paid_timestamp is not null and paid_timestamp != '9999-12-31 00:00:00'";
}

if (!is_null($searchkeyword)){
   if(is_null($whereclause)){
   		   $whereclause="where dawei_assignments_approval.gid like '%" . $searchkeyword . "%'";
   }else{
   $whereclause=$whereclause . "and dawei_assignments_approval.gid like '%" . $searchkeyword . "%'";
}
}

$sql = "
CREATE OR REPLACE VIEW dawei_assignments_approval AS
SELECT DISTINCT dawei_assignment.username AS username ,
       dawei_assignment.gid ,
       dawei_assignment.start_ts ,
       dawei_assignment.end_ts ,
       paid_timestamp ,
       dawei_assignment.refimage_gid ,
       ST_XMax(ST_Transform(the_geom,4326)) AS lonmax ,
       ST_XMin(ST_Transform(the_geom,4326)) AS lonmin ,
       ST_YMax(ST_Transform(the_geom,4326)) AS latmax ,
       ST_YMin(ST_Transform(the_geom,4326)) AS latmin ,
       ARRAY_TO_STRING( ARRAY
                         ( SELECT dawei_approval.username || ' ' || date_part('year',dawei_approval.insert_timestamp) || '-' || date_part('month',dawei_approval.insert_timestamp) || '-' || date_part('day',dawei_approval.insert_timestamp)
                          FROM dawei_approval
                          WHERE dawei_approval.qid=dawei_assignment.gid
                            AND dawei_approval.insert_timestamp > '2013-11-10 00:00:00'
                          ORDER BY dawei_approval.username ), '<br>' ) AS approver ,
       seq
FROM dawei_assignment ,
     dawei_target
WHERE dawei_assignment.gid = dawei_target.tilex || '-' || dawei_target.tiley || '-' || dawei_target.refimage_gid
  AND (dawei_assignment.end_ts > '2013-11-10 00:00:00'
       OR dawei_assignment.end_ts IS NULL);

select distinct *
    ,dawei_assignments_approval.gid
    ,dawei_assignments_approval.username
    ,dawei_assignments_approval.end_ts
    ,dawei_assignments_approval.start_ts
    ,sum as worktime
    ,extract(epoch from sum) as worktime_sec
    ,avg
    ,stddev
    ,nfeature
from dawei_assignments_approval
    left join dawei_worktime_by_region on dawei_assignments_approval.gid = dawei_worktime_by_region.gid
    ".$whereclause."
order by ".$sort_str." dawei_assignments_approval.end_ts,dawei_assignments_approval.start_ts OFFSET ". ($page-1) * 50 ." limit 50;";

if (!($rs = pg_exec($sql))) {die;}
$nrow = pg_num_rows($rs);
$html = $html . "";
$num_approve = 0;
$num_finished = 0;
for ($i = 0; $i < $nrow; $i++) {
    $row = pg_fetch_array($rs, $i);
    $lonmin=$row['lonmin'];
    $latmin=$row['latmin'];
    $lonmax=$row['lonmax'];
    $latmax=$row['latmax'];
    $paid_timestamp=$row['paid_timestamp'];
    $refimage_gid=$row['refimage_gid'];
    $pixelsize=0.0001388888888888888888888;
    $thumbsize=96;
    $wmsthumb='http://'.$WEBHOST.'/cgi-bin/mapserv?map=/var/www/dawei/landsat.map&SERVICE=WMS&REQUEST=GetMap&VERSION=1.1.1&LAYERS='.$refimage_gid.'&WIDTH='.$thumbsize.'&HEIGHT='.$thumbsize.'&SRS=EPSG:4326&FORMAT=JPEG&BBOX=';
    $img='<img height="'.$thumbsize.'" width="'.$thumbsize.'" src="'.$wmsthumb.$lonmin.','.$latmin.','.$lonmax.','.$latmax.'">';
    $tiles='<a href="javascript:void(0);" onClick="writemapthumb('.$thumbsize.','.$lonmin.','.$latmin.','.$lonmax.','.$latmax.','.$i.',\''.$refimage_gid.'\');" id="thumb'.$i.'">Thumbnail</a>';

    if(preg_match('/.*'.$username.'.*/',$row["approver"])){
	$approvestr = "<td><form method='post' action='".$_SERVER['SCRIPT_NAME']."'><input type='hidden' name='unapprove' value='1'><input type='hidden' name='gid' value='".$row["gid"]."'><input name='submit_unapproval' type='submit' value='Cancel approval'></form></td>";
    }else{
	$approvestr = "<td><form method='post' action='".$_SERVER['SCRIPT_NAME']."'><input type='hidden' name='approve' value='1'><input type='hidden' name='gid' value='".$row["gid"]."'>"
	    ."<input name='submit_approval' type='submit' value='a'>"
	    ."</form></td>";   
    }
    //    if(preg_match('/.*kimijimasatomi.*/',$row["approver"])) $num_approve++;
    if( ! is_null($row["end_ts"]) && $row["end_ts"] != '9999-12-31 00:00:00') $num_finished++;

    $paidstr = '';
    if( is_null($paid_timestamp) || $paid_timestamp == '9999-12-31 00:00:00') {
	$paidstr = "<td><form method='post' action='".$_SERVER['SCRIPT_NAME']."'>"
	    . "<input type='hidden' name='paid' value='1'>"
	    . "<input type='hidden' name='gid' value='".$row["gid"]."'>"
	    . "<input type='hidden' name='filter_finished' value='".$filter_finished."'>"
	    . "<input type='hidden' name='filter_approved' value='".$filter_approved."'>"
	    . "<input type='hidden' name='filter_paid' value='".$filter_paid."'>"
	    . "<input name='submit_paid' type='submit' value='paid'>"
	    . "</form></td>";
    }else{
	$paidstr = "<td>Paid<br><form method='post' action='".$_SERVER['SCRIPT_NAME']."'>"
	    . "<input type='hidden' name='unpaid' value='1'>"
	    . "<input type='hidden' name='gid' value='".$row["gid"]."'>"
	    . "<input type='hidden' name='filter_finished' value='".$filter_finished."'>"
	    . "<input type='hidden' name='filter_approved' value='".$filter_approved."'>"
	    . "<input type='hidden' name='filter_paid' value='".$filter_paid."'>"
	    . "<input name='submit_unpaid' type='submit' value='Mark as unpaid'>"
	    . "</form></td>";
    }

    $html = $html . "<tr align='center'><td>" . $row["username"] . "</td>"
	. "<td>" . $row["gid"] . "</td>"
	. "<td>" . preg_replace('/\.[0-9]*$/','',$row["start_ts"]) . "</td>"
	. "<td>" . preg_replace('/\.[0-9]*$/','',$row["end_ts"]) . "</td>"
	. "<td>" . preg_replace('/\.[0-9]*$/','',$row["worktime"]) . "</td>"
	//. "<td>" . $row["worktime_sec"] . "</td>"
	. "<td>" . $row["nfeature"] . "</td>"
	. "<td>" . preg_replace('/\.[0-9]*$/','',$row["avg"]) . "</td>"
	. "<td>" . preg_replace('/\.[0-9]*$/','',$row["stddev"]) . "</td>"
	//. "<td>" . $tiles . "</td>"
	. "<td><a href='wms.tms.parallel.v2.php?lonmin=". $row["lonmin"] ."&latmin=". $row["latmin"] ."&lonmax=". $row["lonmax"] ."&latmax=". $row["latmax"] ."&qid=".$row["gid"]."&refimage_gid=".$row["refimage_gid"]."'>Revise</a>" . "</td>"
	. "<td><form method='post' action='".$_SERVER['SCRIPT_NAME']."'>"
	. "<input type='hidden' name='unfinished' value='1'>"
	. "<input type='hidden' name='gid' value='".$row["gid"]."'>"
	. "<input type='hidden' name='filter_finished' value='".$filter_finished."'>"
	. "<input type='hidden' name='filter_approved' value='".$filter_approved."'>"
	. "<input type='hidden' name='filter_paid' value='".$filter_paid."'>"
	. "<input name='submit_unfinished' type='submit' value='u'>"
	. "</form></td>"
	. $approvestr
	. "<td>".$row["approver"]."</td>"
	//. $paidstr
	. "<td><form method='post' action='".$_SERVER['SCRIPT_NAME']."' onSubmit='return check_reassign()'>"
	. "<input type='hidden' name='reassign' value='1'>"
	. "<input type='hidden' name='gid' value='".$row["gid"]."'>"
	. "<input type='hidden' name='filter_finished' value='".$filter_finished."'>"
	. "<input type='hidden' name='filter_approved' value='".$filter_approved."'>"
	. "<input type='hidden' name='filter_paid' value='".$filter_paid."'>"
	."<input name='submit_reassign' type='submit' value='r'>"
	. "</form></td></tr>"
	;
}
if (!($rs = pg_exec("SELECT DISTINCT * FROM dawei_assignments_approval WHERE approver LIKE '%kimijimasatomi%';"))) {die;}
$num_approve = pg_num_rows($rs);
if (!($rs = pg_exec("SELECT DISTINCT * FROM dawei_assignment WHERE end_ts IS NOT NULL AND end_ts != '9999-12-31 00:00:00' AND end_ts > '2013-11-10 00:00:00';"))) {die;}
$num_finished = pg_num_rows($rs);
?>

<html>
  <head>
    <meta charset="UTF-8">
    <title>
      Assignments
    </title>
    <script type="text/javascript"> 
    <!-- 

    function check_reassign(){
    	    if(window.confirm('Really reassign?')){
    	    	    return true;
    	    }
    	    else{
    	    	    window.alert('Cancelled');
    	    	    return false;
    	    }
	}
function writemapthumb(thumbsize,lonmin,latmin,lonmax,latmax,rn,refimage_gid){
    //wmsthumb='http://guam.csis.u-tokyo.ac.jp/cgi-bin/mapserv?map=/var/www/dawei/landsat.map&SERVICE=WMS&REQUEST=GetMap&VERSION=1.1.1&LAYERS='+refimage_gid+',vi_urban_polygon,vi_urban_line,vi_unknown_polygon,vi_unknown_line,vi_revise_polygon,vi_revise_line,vi_pending_polygon,vi_pending_line&WIDTH='+thumbsize+'&HEIGHT='+thumbsize+'&SRS=EPSG:4326&FORMAT=JPEG&BBOX=';
    wmsthumb='http://guam.csis.u-tokyo.ac.jp/cgi-bin/mapserv?map=/var/www/dawei/landsat.map&SERVICE=WMS&REQUEST=GetMap&VERSION=1.1.1&LAYERS='+refimage_gid+'&WIDTH='+thumbsize+'&HEIGHT='+thumbsize+'&SRS=EPSG:4326&FORMAT=JPEG&BBOX=';
    img='<img height="'+thumbsize+'" width="'+thumbsize+'" src="'+wmsthumb+lonmin+','+latmin+','+lonmax+','+latmax+'">';
    //    document.thumb.src=wmsthumb+lonmin+','+latmin+','+lonmax+','+latmax;
    document.getElementById('thumb'+rn).innerHTML = img;
}

    // -->
</script>
<style type="text/css">
    <!--
td {
     font-size:0.7em;
     }
td.item_header {
                 font-size:0.7em;
                 }
th {
     font-size:0.5em;
     }
/* --- 全体の背景・テキスト --- */
body {
       margin: 0;
       padding: 0;
       background-color: #ffffff; /* ページの背景色 */
       color: #000000; /* 全体の文字色 */
       //font-size: 100%; /* 全体の文字サイズ */
       }
ul {
     list-style-type: none;
     font-size:0.8em;
     }
a:link { color: #0000ff; }
a:visited { color: #800080; }
a:hover { color: #ff0000; }
a:active { color: #ff0000; }
#container {
             //width: 1200px; /* ページの幅 */
             margin: 0 auto; /* センタリング */
             background-color: #ffffff; /* メインカラムの背景色 */
             border-left: 1px #c0c0c0 solid; /* 左の境界線 */
             border-right: 1px #c0c0c0 solid; /* 右の境界線 */
             }
#header {
          background-color: #ffe080; /* ヘッダの背景色 */
          }
#nav {
       float: left;
       width: 120px; /* サイドバーの幅 */
       }
#content {
           float: left;
           //width: 80%; /* メインカラムの幅 */
           }
#footer {
          clear: left; /* フロートのクリア */
          width: 100%;
          background-color: #ffe080; /* フッタの背景色 */
          }
    // -->

    </style>

    <style type="text/css" title="currentStyle">
      @import "DataTables/css/demo_page.css"; 
@import "DataTables/css/header.ccss";
      @import "DataTables/css/demo_table.css";
    </style>
    <script type="text/javascript" language="javascript" src="DataTables/js/jquery.js"></script>
    <script type="text/javascript" language="javascript" src="DataTables/js/jquery.dataTables.js"></script>
    <script type="text/javascript" charset="utf-8">
    $(document).ready(function() {
	$('#example').dataTable({
		"paging":   false,
		    "ordering": false,
		    "info":     false
	});
    } );
    </script>
  </head>
  <body>
    <div id="container">
      <div id="header">
	<a href="assignregion.php">Back to assignment</a> | <a href="<?php echo $_SERVER['SCRIPT_NAME'];?>?sbu=1">Sort by User name</a> | <a href="<?php echo $_SERVER['SCRIPT_NAME'];?>">Sort by Start time and Finished time</a><br>Partial match filter by keywords <form style="display: inline" name="searchkeyword" method="POST" action="<?php echo $_SERVER['SCRIPT_NAME'];?>"><input type=text name="searchkeyword" /><input type=submit value="filter" />(e.g. LM11400501972356AAA04 or 6472-3679; to remove filter, click "filter" without any input.)</form>
	<br><a href="?page=<?php if($page==1){echo 1;}else{echo $page-1;};?>">prev</a> Page <?php echo $page;?> <a href="?page=<?php echo $page+1;?>">next</a>
      </div>
      <div id="nav">
	<ul>
	  <li><a href="<?php echo $_SERVER['SCRIPT_NAME'];?>">All</a></li>
	  <li><a href="" onclick="document.finished.submit();return false;">Finished</a>
	    <form name="finished" method="POST" action="<?php echo $_SERVER['SCRIPT_NAME'];?>">
	      <input type=hidden name="filter_finished" value="1">
	    </form>
	  </li>
	  <li><a href="" onclick="document.approved.submit();return false;">Approved</a>
	    <form name="approved" method="POST" action="<?php echo $_SERVER['SCRIPT_NAME'];?>">
	      <input type=hidden name="filter_approved" value="1"></form>
	  </li>
	  <li><a href="" onclick="document.paid.submit();return false;">Paid</a>
	    <form name="paid" method="POST" action="<?php echo $_SERVER['SCRIPT_NAME'];?>">
	      <input type=hidden name="filter_paid" value="1"></form>
	  </li>
	  <li>
	  </li>
	  <!--<li>Total: </li>-->
	  <li>Finished: <?php echo $num_finished;?></li>
	  <li>Approved: <?php echo $num_approve;?></li>
	</ul>
      </div>
      <div id="content">
	<!--<table border='1' style='border-width: thin; border-style: solid; border-collapse: collapse'>-->
	  <table ellpadding="0" cellspacing="0" border="1" class="display" width="100%">
	  <thead>
	  <tr>
            <th>
              User name
            </th>
            <th>
              Region #
            </th>
            <th>
              Start time
            </th>
            <th>
              Finished time
            </th>
            <th>
              Effective worktime
            </th>
            <!--<th>
		Effective worktime (sec.)
		</th>-->
            <th>
              # of features
            </th>
            <th width="2em">
              Average drawing time by feature (sec.)
            </th>
            <th width="2em">
              Std. deviation of drawing time by feature (sec.)
            </th>
            <!--<th width="3em">
              Overview: Click to show thumbnail
            </th>-->
            <th>
              Revise
            </th>
            <th width="2em">
              Mark as unfinished
            </th>
            <th width="2em">
              Approve
            </th>
            <th>
              Approved by
            </th>
            <!--<th>
		Payment
		</th>-->
            <th width="2em">
              Reassign
            </th></thead>
<tbody>
	  </tr><?php echo $html ?>
</tbody>
	</table>
      </div>
    </div>
  </body>
</html>

