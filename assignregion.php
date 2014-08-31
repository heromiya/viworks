<?php
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    print('You are not authorized. Please reload and log in with your ID and password.');
}else {
    include 'init.php';
    $start=NULL;
    $end=NULL;
    $qid=NULL;
    $interrupt=NULL;
    $next=NULL;
    $prev=NULL;
    $offset=NULL;
    if(isset($_POST['start'])) $start=$_POST['start'];
    if(isset($_POST['end'])) $end=$_POST['end'];
    if(isset($_POST['qid'])) $qid=$_POST['qid'];
    if(isset($_POST['interrupt'])) $interrupt=$_POST['interrupt'];
    if(isset($_POST['next'])) $next=$_POST['next'];
    if(isset($_POST['prev'])) $prev=$_POST['prev'];
    if(isset($_POST['offset'])) $offset=$_POST['offset'];
    if($offset == ''){$offset=0;}
    if($next == 1){$offset++;}
    if($prev == 1){$offset--;}
    if ($end == 1){
	$sql = sprintf("update dawei_assignment set end_ts = now() where gid = '%s' and username = '%s' and (end_ts is null or end_ts > '2100-01-01');",$qid,$username);
	if (!($rs = pg_exec($sql))) { die; }
    }
    if ($interrupt == 1){
	$sql = sprintf("update dawei_assignment set start_ts = '' where gid = '%s' and username = '%s' and start_ts is not null;",$username,$qid);
	if (!($rs = pg_exec($sql))) { die; }
    }
    $sql = sprintf("
SELECT dawei_target.tilex || '-' ||dawei_target.tiley || '-' ||dawei_target.refimage_gid as gid
      ,start_ts
      ,dawei_target.tilex
      ,dawei_target.tiley
      ,dawei_target.refimage_gid
      ,ST_XMin(ST_Transform(the_geom,4326)) as lonmin
      ,ST_XMax(ST_Transform(the_geom,4326)) as lonmax
      ,ST_YMin(ST_Transform(the_geom,4326)) as latmin
      ,ST_YMax(ST_Transform(the_geom,4326)) as latmax
from dawei_assignment
    ,dawei_target
where dawei_assignment.tilex = dawei_target.tilex 
  and dawei_assignment.tiley = dawei_target.tiley
  and dawei_assignment.refimage_gid = dawei_target.refimage_gid
  and username = '%s'
  and (end_ts is null or end_ts > '2100-01-01')
  and dawei_target.priority = 1
order by seq
limit 1
;", $username);
    if (!($rs = pg_exec($sql))) { die; }
    $nrow = pg_num_rows($rs);
    if($nrow == 0){
	$row = pg_fetch_array(pg_exec("select dawei_target.tilex || '-' ||dawei_target.tiley || '-' ||dawei_target.refimage_gid as gid
                                             ,dawei_target.tilex
                                             ,dawei_target.tiley
                                             ,dawei_target.refimage_gid
                                       from dawei_target
                                       left join dawei_assignment
		                       on dawei_assignment.tilex = dawei_target.tilex
                                       and dawei_assignment.tiley = dawei_target.tiley
                                       and dawei_assignment.refimage_gid = dawei_target.refimage_gid

                                       where dawei_assignment.gid is null 
                                         and dawei_target.priority = 1
                                       limit 1;"),0);
	$TILEX=$row['tilex'];
	$TILEY=$row['tiley'];
	$REFIMAGE_GID=$row['refimage_gid'];
	$FIRST_TILE= $TILEX.'-'.$TILEY.'-'.$REFIMAGE_GID;
	pg_exec("insert into dawei_assignment (gid,username,typename,assign_ts,tilex,tiley,refimage_gid) values('".$FIRST_TILE."','".$username."', 'FIRST',now(),".$TILEX.",".$TILEY.",'".$REFIMAGE_GID."');");
	$NUM_TILES=2;
	$NEAREST_TILES = pg_exec("
SELECT t2.gid
      ,t2.tilex
      ,t2.tiley 
      ,t2.refimage_gid
from dawei_target as t1
    ,(SELECT dawei_target.tilex || '-' ||dawei_target.tiley || '-' ||dawei_target.refimage_gid as gid
            ,dawei_target.tilex
            ,dawei_target.tiley
            ,dawei_target.refimage_gid
            ,the_geom 
      FROM dawei_target
      left join dawei_assignment
        on dawei_assignment.tilex = dawei_target.tilex
       and dawei_assignment.tiley = dawei_target.tiley
       and dawei_assignment.refimage_gid = dawei_target.refimage_gid
      where dawei_assignment.gid is null
        and dawei_target.priority = 1
) as t2
where t1.tilex || '-'|| t1.tiley || '-' || t1.refimage_gid = '" . $FIRST_TILE . "' 
  and not ST_Equals(t1.the_geom, t2.the_geom)
 
order by ST_Distance(ST_Centroid(t1.the_geom),ST_Centroid(t2.the_geom)) 
limit " . $NUM_TILES  . ";");
	for ($i = 0; $i < pg_num_rows($NEAREST_TILES) ; $i++){
	    $row = pg_fetch_array($NEAREST_TILES, NULL, PGSQL_ASSOC);
	$TILEX=$row['tilex'];
	$TILEY=$row['tiley'];
	$REFIMAGE_GID=$row['refimage_gid'];
	    pg_exec("
insert into dawei_assignment (gid
                             ,username
                             ,typename
                             ,assign_ts
                             ,tilex
                             ,tiley
                             ,refimage_gid)
values('".$TILEX."-".$TILEY."-".$REFIMAGE_GID."'
         ,'".$username."'
         ,'NEAREST'
         ,now()
         ,".$TILEX."
         ,".$TILEY."
         ,'".$REFIMAGE_GID."');");
	}

	$RANDOM_TILES = pg_exec("
select dawei_target.tilex || '-' ||dawei_target.tiley || '-' ||dawei_target.refimage_gid as gid 
      ,dawei_target.tilex
      ,dawei_target.tiley
      ,dawei_target.refimage_gid
from dawei_target
left join dawei_assignment
 on dawei_assignment.tilex = dawei_target.tilex
 and dawei_assignment.tiley = dawei_target.tiley
 and dawei_assignment.refimage_gid = dawei_target.refimage_gid
where dawei_target.tilex || '-' ||dawei_target.tiley || '-' || dawei_target.refimage_gid != '" . $FIRST_TILE . "' 
  and dawei_assignment.gid is null 
  and dawei_target.priority = 1
order by RANDOM() 
limit " . $NUM_TILES . ";");
	for ($i = 0; $i < pg_num_rows($RANDOM_TILES) ; $i++){
	    $row = pg_fetch_array($RANDOM_TILES, NULL, PGSQL_ASSOC);
	    $TILEX=$row['tilex'];
	    $TILEY=$row['tiley'];
	    $REFIMAGE_GID=$row['refimage_gid'];
	    
	    pg_exec("insert into dawei_assignment (gid
                                            ,username
                                            ,typename,assign_ts,tilex,tiley,refimage_gid)
                            values('".$TILEX."-".$TILEY."-".$REFIMAGE_GID."'
                                   ,'".$username."'
                                   ,'RANDOM',now(),".$TILEX.",".$TILEY.",'".$REFIMAGE_GID."');");
	}

	$sql = sprintf("select dawei_assignment.gid
                          ,dawei_assignment.refimage_gid
                          ,ST_XMin(ST_Transform(the_geom,4326)) as lonmin
                          ,ST_XMax(ST_Transform(the_geom,4326)) as lonmax
                          ,ST_YMin(ST_Transform(the_geom,4326)) as latmin
                          ,ST_YMax(ST_Transform(the_geom,4326)) as latmax
                    from dawei_assignment
                    left join dawei_target
                    on dawei_assignment.tilex = dawei_target.tilex
                   and dawei_assignment.tiley = dawei_target.tiley
                   and dawei_assignment.refimage_gid = dawei_target.refimage_gid
                    where username = '%s'
                      and start_ts is null
                      and (end_ts is null or end_ts > '2100-01-01')
                        and dawei_target.priority = 1
                    order by seq
                    limit 1
;", $username);
	if (!($rs = pg_exec($sql))) { die; }
	$row = pg_fetch_array($rs,0);
	$qid = $row['gid'];
	$start_ts = sprintf('
<table align="center"><tr><td>
<form method="POST" action="'.$_SERVER['SCRIPT_NAME'].'" name="start">
<input type="hidden" name="qid" value="'.$qid.'">
<input type="hidden" name="start" value="1">
<input type="submit" value="Start correction">
</form></td></tr></table>
');
	$end_ts = '';
    }else{
	$row = pg_fetch_array($rs,0);
	$qid = $row['gid'];
	$start_ts = "Start time: ".ereg_replace("\.[0-9]*","",$row['start_ts']);
	$end_ts = '<input type="submit" value="Finished corrections">';
    }
    $lonmin=$row['lonmin'];
    $latmin=$row['latmin'];
    $lonmax=$row['lonmax'];
    $latmax=$row['latmax'];
    $refimage_gid=$row['refimage_gid'];
    $pixelsize=0.0001388888888888888888888;
    $thumbsize=360;
    //    $wmsthumb='http://'.$WEBHOST.'/cgi-bin/mapserv?map=/var/www/dawei/landsat.map&SERVICE=WMS&REQUEST=GetMap&VERSION=1.1.1&LAYERS='.$refimage_gid.',vi_urban_polygon,vi_urban_line,vi_unknown_polygon,vi_unknown_line,vi_revise_polygon,vi_revise_line,vi_pending_polygon,vi_pending_line&WIDTH='.$thumbsize.'&HEIGHT='.$thumbsize.'&SRS=EPSG:4326&FORMAT=JPEG&BBOX=';
    $wmsthumb='http://'.$WEBHOST.'/cgi-bin/mapserv?map=/var/www/dawei/landsat.map&SERVICE=WMS&REQUEST=GetMap&VERSION=1.1.1&LAYERS='.$refimage_gid.'&WIDTH='.$thumbsize.'&HEIGHT='.$thumbsize.'&SRS=EPSG:4326&FORMAT=JPEG&BBOX=';
    $tiles='<img height="360" width="360" src="'.$wmsthumb.$lonmin.','.$latmin.','.$lonmax.','.$latmax.'">';
    pg_close($cn);

print <<< EOF
	  <html>
	    <head>
	      <title>Global Urban Area Map: Correction</title>
	      <meta charset="UTF-8">
	    </head>
	    <body>
	      <div align="center">
		<table width='720'>
	<!--<tr><td colspan="2"><b>NOTICE:</b> The Global Urban Mapping System will be <b>interupted at 9:30AM-5:00PM (Japan Standard Time) on 12 January 2013</b> due to maintancenace activity by the University of Tokyo.</td><tr>-->
		  <tr>
		    <td>
		      <table style="border-width: thin; border-style: solid;border-collapse: collapse;text-align: center" border="1" cellpadding="5px">
			<tr>
			  <td colspan="2">Logged in as ${username}</td>
			</tr>
			<tr>
			  <th colspan="2">Region number</th>
			</tr>
			<tr>
			  <td  colspan="2" style="font-size: 100%;">
			    ${qid}
			  </td>
			</tr>
			<!--<tr>
			  <th rowspan="3">Quality assessment</th>
			  <td>Omission rate: $pa</td>
			</tr>
			<tr>
			  <td>Commission rate: $ua</td>
			</tr>
			<tr>
			  <td>Population: $es00pop</td>
			</tr>-->
			<tr>
			  <td colspan="2" style="font-size: 250%;">
			    <a href="wms.tms.parallel.v2.php?lonmin=${lonmin}&latmin=${latmin}&lonmax=${lonmax}&latmax=${latmax}&qid=${qid}&refimage_gid=${refimage_gid}">Start Web-GIS</a>
			  </td>
			</tr>
			<form method="POST" action="${_SERVER['SCRIPT_NAME']}" name="end">
			  <tr>
			    <td colspan="2">
			      <input type="hidden" name="qid" value="${qid}">
			      <input type="hidden" name="end" value="1">
			      <input type="submit" value="Finished corrections">
			    </td>
			  </tr>
			  <tr>
			    <td colspan="2">
			      <a href="achievements.php">Browse/revise achievements</a>
			    </td>
			  </tr>
			  <!--<tr>
			    <td colspan="2">
			      <a href="pending.php">Browse pending areas</a>
			    </td>
			  </tr>
			  <tr>
			    <td colspan="2">
			      <a href="revise.php">Browse areas reuquiring revise</a>
			    </td>
			  </tr>
			  <tr>
			    <td colspan="2">
			      <a href="worktime.stats.ind.php">Browse working/rewarded time</a>
			    </td>
			  </tr>-->
			</form>
		    </td>
		  </tr>
		  </table>
</td>
<td>
  <table>
    <tr>
      <th colspan="2">Overview</th>
    </tr>
    <tr>
      <td>
	${tiles}
      </td>
    </tr>
  </table>
</td>
</tr>
</table>
<table width="700" border="1" cellpadding="5">
  <tr><th>Updates of the Web-GIS</th><th>Practice sessions</th></tr>
  <tr>
    <td>
      <ul>
	<li>21 January 2013: Add reference layers from ArcGIS Online. Click [Reference layer] > [ArcGIS Imagery] or [ArcGIS Street] in the Web-GIS.</li>
	  <li>1 Sept. 2012: Add function to browse working time and rewarded time. Click <a href="worktime.stats.ind.php">Browse working/rewarded time</a> here, above, or <b>Function &rarr; Browse working/rewarded time</b> in the Web-GIS.</li>
<li>10 July 2012: Add function to mark 'pending' to leave areas for refinement by supervisors. Click <img src="icons/mActionIdentify_off.png"> to change attribute to 'pending' by Class field (located right of 'Function'). Pending areas are listed in <a href="pending.php">Browse pending areas</a></li>
<li>7 July 2012: Change in progress management of "Browse/revise achievements".</li>
<li>7 July 2012: Change projection of the ASTER map window as same as the Reference window. Click <b>Functions</b> &rarr; <b>Change Projection</b>.</li>
<li>7 July 2012: OpenLayers library have been upgraded to 2.12.</li>
</ul>
</td>

<td><p>Please use these sessions for your practice if you are beginner. Your works in these sessions here are not effected to the database. This allows you to try out functions of the Web-GIS in any ways. By clicking "Answer" link, good practices of the regions are shown with comparison to your practice. These may be helpful to know your works are correct.</p>
  <ul>
    <li><a href="wms.tms.parallel.practice.php?latmin=31.723799802243&lonmin=72.965696121177&latmax=31.745610552439&lonmax=72.984840054755&">Practice 1</a></li>

    <li><a href="wms.tms.parallel.practice.php?latmin=8.0596917670288&lonmin=4.676296493164&latmax=8.1186574500122&lonmax=4.7280523983154">Practice 2</a></li>
    <li><a href="wms.tms.parallel.practice.php?latmin=23.570852730677&lonmin=86.457957156932&latmax=23.592663480873&lonmax=86.47710109051">Practice 3</a></li>

  </ul>
</td>
</tr>

</table>
</div>
</body>
</html>
EOF;
}
?>
