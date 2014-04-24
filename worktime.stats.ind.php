<?php
include 'init.php';
$sql = $db->prepare("
select *
    ,avg_hr*nor as reward
    ,totaltime 
from (select dawei_assignment.username
        ,avg(extract(epoch from sum))/3600 as avg_hr
	,stddev(extract(epoch from sum))/3600 as stddev_hr
	,sum(1) as nor
    from dawei_worktime_by_region
	,dawei_assignment 
	,(select distinct qid,username from dawei_approval) as dawei_approval
    where dawei_worktime_by_region.gid = dawei_assignment.gid
	and dawei_worktime_by_region.username = dawei_assignment.username
	and dawei_assignment.gid = dawei_approval.qid
	and dawei_approval.username='kimijimasatomi'
    group by dawei_assignment.username
        ) as t
    ,dawei_worktime
where t.username = dawei_worktime.username
  and t.username = ?
;"
		    ,array('text')
		    ,array('text','float','float','float','float','timestamp'));

if (!($rs = $sql->execute(array($username)))){die;}
$html = $html . "<table border='1' style='border-width: thin; border-style: solid; border-collapse: collapse'><tr><th>User name</th><th>Mean worktime per region (hour)</th><th>Standard deviation (hour)</th><th># of regions finished</th><th>Total rewards (hour)</th><th>Total worktime (hour)</th></tr>";
while($row = $rs->fetchRow()){
	$html = $html . "<tr align='center'><td>" . $row[0] . "</td>"
	    . "<td>" . round($row[1],4) . "</td>"
	    . "<td>" . round($row[2],4) . "</td>"
	    . "<td>" . $row[3] . "</td>"
	    . "<td>" . round($row[1]*$row[3],4) . "</td>"
	    . "<td>" . ereg_replace('\.[0-9]*$','',$row[5]) . "</td>"
	    . "</tr>"
	    ;
}
$html = $html . "</table>"

?>
    <html>
    <head>
    <meta charset="UTF-8">
    <title>Total work time</title></head>

    <style type="text/css">
    <!--
    td {font-size:0.8em;}
    td.item_header {font-size:0.8em;}
th {font-size:0.8em;}
// -->
</style>
<body>
<?php echo $html ?>
<b>Total worktime (hour) </b> is updated every three hours.</br>
<b>Total rewards (hour)</b> is updated once a day at 20:00GMT.

		       </body>
		       </html>
