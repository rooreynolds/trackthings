<!doctype html>
<head>
   <meta charset="utf-8">
   <title>Things</title>
   <link rel="stylesheet" href="css/style.css"/>
   <link href="favicon.png" rel="shortcut icon"/>
   <script src="js/jquery-1.7.1.js"></script>
   <script src="js/raphael-min.js"></script>
   <script src="js/morris.min.js"></script>
   <script src="js/bootstrap/transition.js"></script>
   <script src="js/bootstrap/collapse.js"></script>
</head>
<body>
<div class="navbar navbar-fixed-top">
   <div class="navbar-inner">
      <div class="container">
         <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
         </a>
         <a class="brand" href="#">Things</a>
         <div class="nav-collapse">
            <ul id="nav" class="nav">
            </ul>
         </div><!--/.nav-collapse -->
      </div>
   </div>
</div>
<div class="container">

<?php

function printTitle($title, $count) {
   echo "<h3 id='" . strtolower($title) . "'>$title " . ($count ? "($count)" : "") . "</h3>\n";
   echo "<script>document.getElementById('nav').innerHTML += \"<li><a href='#" . strtolower($title) . "'>" . $title . "</a></li>\";</script>\n";
}

function getSqlResults($db, $sqlQuery) {
   $result = $db->query($sqlQuery);
  	return $result->fetchAll(PDO::FETCH_ASSOC);
}

function printDateTable($db, $title, $sqlQuery) { 
   $arrValues = getSqlResults($db, $sqlQuery);
   if ($arrValues) {
      $count = count($arrValues);
      print "<div class='row span12'>\n";
      printTitle($title, $count);
      echo "<ul>\n";
      foreach ($arrValues as $row){
         $areastring = "";
         if ($row['project'] || $row['area']) {
            $areastring = "[" . $row['project'] . $row['area']. "] ";
         }
         print "<li>" . $row['date'] . ": " . $areastring . $row['title'] . "</li>\n";
      }
      echo "</ul>\n";
      print "</div>\n\n";
   }
}

function printItemList($db, $title, $sqlQuery) { 
   $arrValues = getSqlResults($db, $sqlQuery);
   if ($arrValues) {
      $count = count($arrValues);
      print "<div class='row span12'>\n";
      printTitle($title, $count);
      // print "<pre>$sqlQuery</pre>";
      echo "<ul>\n";
      $result = $db->query($sqlQuery);
      $arrValues = $result->fetchAll(PDO::FETCH_ASSOC);
      foreach ($arrValues as $row){
         $areastring = "";
         if ($row['project'] || $row['area']) {
            $areastring = "[" . $row['project'] . $row['area']. "] ";
         }
         print "<li>$areastring" . $row['title'] . "</li>\n";
      }
      echo "</ul>\n";
      print "</div>\n\n";
   }
}



function createHistoryChart($db) { 
   print "<div class='row span12'>\n";
   printTitle("Created and completed per day");
   $sqlGetView = "select date(ZSTOPPEDDATE, 'unixepoch', '+31 years') as date, count(Z_PK) as completed from ZTHING where ZSTATUS = 3 group by date;";
   $result = $db->query($sqlGetView);
   $arrValuesCompleted = $result->fetchAll(PDO::FETCH_ASSOC);
   $sqlGetView = "select date(ZCREATIONDATE, 'unixepoch', '+31 years') as date, count(Z_PK) as created from ZTHING where ZCREATIONDATE != '' group by date;";
   $result = $db->query($sqlGetView);
   $arrValuesCreated = $result->fetchAll(PDO::FETCH_ASSOC);
   $dates = array();
   foreach ($arrValuesCreated as $row){
      $dates[$row['date']][0] = $row['created'];
   }
   foreach ($arrValuesCompleted as $row){
      $dates[$row['date']][1] = $row['completed'];
   }
   //array may not be fully populated, so make sure it's sorted in to order
   array_multisort(array_keys($dates), $dates);
   //list the dates, number of tasks created and completed 
   print "<div class='graph' id='historychart'></div>\n";
   print "<script>\n";
   print "Morris.Line({\n";
   print "element: 'historychart', data: [\n";
   foreach ($dates as $i => $values) {
    //print $i . "," . ($dates[$i][0] ? : 0) . "," . ($dates[$i][1] ? : 0) . "<br/>\n";
      print "{d: '$i', a: " . ($dates[$i][0] ? : 0) . ", b: " . ($dates[$i][1] ? : 0) . "},\n"; 
   }    
   print "],\n";
   print "xkey: 'd',  ykeys: ['a', 'b'], labels: ['Created', 'Completed'], lineColors: ['#167f39','#04294c'], lineWidth: 2 });\n";
   print "</script>\n";
   print "</div>\n\n";
   // print "<div>\n";
   // print json_encode($dates);
   // print "</div>\n";
}

function createHistoryChart_age($db) { 
   print "<div class='row span12'>\n";
   printTitle("Average age of completed per day");
   $sqlGetView = "select date(ZSTOPPEDDATE, 'unixepoch', '+31 years') as date, count(Z_PK) as completed, "
   . "round(julianday(datetime(avg(ZSTOPPEDDATE), 'unixepoch', '+31 years')) - julianday(datetime(avg(ZCREATIONDATE), 'unixepoch', '+31 years')),5) as age "
   . "from ZTHING where ZSTATUS = 3 group by date order by date";
   $result = $db->query($sqlGetView);
   $arrValuesCompleted = $result->fetchAll(PDO::FETCH_ASSOC);
   $dates = array();
   foreach ($arrValuesCompleted as $row){
      $dates[$row['date']][0] = $row['completed'];
      $dates[$row['date']][1] = $row['age'];
   }
   //array may not be fully populated, so make sure it's sorted in to order
   array_multisort(array_keys($dates), $dates);
   //list the dates, number of tasks created and completed 
   print "<div class='graph' id='historychartage'></div>\n";
   print "<script>\n";
   print "Morris.Line({\n";
   print "element: 'historychartage', data: [\n";
   foreach ($dates as $i => $values) {
    //print $i . "," . ($dates[$i][0] ? : 0) . "," . ($dates[$i][1] ? : 0) . "<br/>\n";
   	print "{d: '$i', a: " . ($dates[$i][0] ? : 0) . ", b: " . ($dates[$i][1] ? : 0) . "},\n";	
   }    
   print "],\n";
   print "xkey: 'd',  ykeys: ['b', 'a'], labels: ['Average age', 'Completed'], lineWidth: 2 });\n";
   print "</script>\n";
   print "</div>\n\n";
   // print "<div>\n";
   // print json_encode($dates);
   // print "</div>\n";
}


try{
   #you'll need to do a `ln ~/Library/Application\ Support/Cultured\ Code/Things\ beta/ThingsLibrary.db ThingsLibrary.db` wherever this is served
	$db = new PDO('sqlite:ThingsLibrary.db');
 	        
   printItemList($db, "Review", "select ZTHING.ZTITLE as title, "
      . "date(ZTHING.ZSTARTDATE, 'unixepoch', '+31 years', 'localtime') as startdate, "
      . "AREA.ZTITLE as area, PROJECT.ZTITLE as project "
      . "from ZTHING "
      . "LEFT OUTER JOIN ZTHING PROJECT on ZTHING.ZPROJECT = PROJECT.Z_PK "
      . "LEFT OUTER JOIN ZTHING AREA on ZTHING.ZAREA = AREA.Z_PK "
      . "WHERE startdate <= date('now') and ZTHING.ZSTARTDATE != '' and ZTHING.ZSTATUS != 3 and ZTHING.ZTRASHED = 0 and ZTHING.ZSTART = 2 "
      . "ORDER BY ZTHING.ZINDEX;");

 	printItemList($db, "Today", "select ZTHING.ZTITLE as title, " 
      . "AREA.ZTITLE as area, PROJECT.ZTITLE as project, "
 		. "date(ZTHING.ZCREATIONDATE, 'unixepoch', '+31 years', 'localtime') as createdate, "
 		. "round(julianday(date('now')) - julianday(datetime(ZTHING.ZCREATIONDATE, 'unixepoch', '+31 years', 'localtime')),20) as age "
 		. "FROM ZTHING "
      . "LEFT OUTER JOIN ZTHING PROJECT on ZTHING.ZPROJECT = PROJECT.Z_PK "
      . "LEFT OUTER JOIN ZTHING AREA on ZTHING.ZAREA = AREA.Z_PK "
      . "WHERE ZTHING.ZSTARTDATE != '' and ZTHING.ZSTATUS != 3 and ZTHING.ZTRASHED = 0 and ZTHING.ZSTART = 1 "
 		. "ORDER BY ZTHING.ZTODAYINDEX;");  	
 	printItemList($db, "Tomorrow", "select date(ZTHING.ZSTARTDATE, 'unixepoch', '+31 years', 'localtime') as date, "
      . "AREA.ZTITLE as area, PROJECT.ZTITLE as project, "
 		. "ZTHING.ZTITLE as title FROM ZTHING "
      . "LEFT OUTER JOIN ZTHING PROJECT on ZTHING.ZPROJECT = PROJECT.Z_PK "
      . "LEFT OUTER JOIN ZTHING AREA on ZTHING.ZAREA = AREA.Z_PK "
 		. "WHERE date = date('now', '+1 day') and ZTHING.ZSTARTDATE != '' and ZTHING.ZSTATUS != 3 and ZTHING.ZTRASHED = 0 and ZTHING.ZSTART = 2;");
 	printDateTable($db, "Approaching", "select date(ZTHING.ZDUEDATE, 'unixepoch', '+31 years', 'localtime') as date, "
      . "AREA.ZTITLE as area, PROJECT.ZTITLE as project, "
 		. "ZTHING.ZTITLE as title from ZTHING "
      . "LEFT OUTER JOIN ZTHING PROJECT on ZTHING.ZPROJECT = PROJECT.Z_PK "
      . "LEFT OUTER JOIN ZTHING AREA on ZTHING.ZAREA = AREA.Z_PK "
      . "where ZTHING.ZDUEDATE != '' AND ZTHING.ZDUEDATE != 63113904000 and ZTHING.ZSTATUS != 3 and ZTHING.ZTRASHED = 0 order by ZTHING.ZDUEDATE;");
  printItemList($db, "Completed today", "select date(ZTHING.ZSTOPPEDDATE, 'unixepoch', '+31 years', 'localtime') as date, "
      . "AREA.ZTITLE as area, PROJECT.ZTITLE as project, "
      . "ZTHING.ZTITLE as title from ZTHING "
      . "LEFT OUTER JOIN ZTHING PROJECT on ZTHING.ZPROJECT = PROJECT.Z_PK "
      . "LEFT OUTER JOIN ZTHING AREA on ZTHING.ZAREA = AREA.Z_PK "  
      . "where ZTHING.ZSTATUS = 3 and ZTHING.ZTRASHED = 0 and date = date('now') order by ZTHING.ZSTOPPEDDATE;");
  printItemList($db, "Completed yesterday", "select date(ZTHING.ZSTOPPEDDATE, 'unixepoch', '+31 years', 'localtime') as date, "
      . "AREA.ZTITLE as area, PROJECT.ZTITLE as project, "
      . "ZTHING.ZTITLE as title from ZTHING "
      . "LEFT OUTER JOIN ZTHING PROJECT on ZTHING.ZPROJECT = PROJECT.Z_PK "
      . "LEFT OUTER JOIN ZTHING AREA on ZTHING.ZAREA = AREA.Z_PK "  
      ." where ZTHING.ZSTATUS = 3 and ZTHING.ZTRASHED = 0 and date = date('now', '-1 day') order by ZTHING.ZSTOPPEDDATE;");
   printItemList($db, "Train", "SELECT THING.ZTITLE as title, "
      . "AREA.ZTITLE as area, PROJECT.ZTITLE as project "
      . "FROM Z_12TAGS " 
      . "JOIN ZTHING THING on THING.Z_PK = Z_12TAGS.Z_12NOTES "
      . "LEFT OUTER JOIN ZTHING PROJECT on THING.ZPROJECT = PROJECT.Z_PK "
      . "LEFT OUTER JOIN ZTHING AREA on THING.ZAREA = AREA.Z_PK "
      . "WHERE Z_12TAGS.Z_14TAGS = 67 and THING.ZSTATUS != 3 and THING.ZTRASHED = 0;"); #67 = Train
   printItemList($db, "Email", "SELECT THING.ZTITLE as title, "
      . "AREA.ZTITLE as area, PROJECT.ZTITLE as project "
      . "FROM Z_12TAGS " 
      . "JOIN ZTHING THING on THING.Z_PK = Z_12TAGS.Z_12NOTES "
      . "LEFT OUTER JOIN ZTHING PROJECT on THING.ZPROJECT = PROJECT.Z_PK "
      . "LEFT OUTER JOIN ZTHING AREA on THING.ZAREA = AREA.Z_PK "
      . "WHERE Z_12TAGS.Z_14TAGS = 293 and THING.ZSTATUS != 3 and THING.ZTRASHED = 0;"); #293 = Email
   printItemList($db, "Waiting For", "SELECT THING.ZTITLE as title, "
      . "AREA.ZTITLE as area, PROJECT.ZTITLE as project "
      . "FROM Z_12TAGS " 
      . "JOIN ZTHING THING on THING.Z_PK = Z_12TAGS.Z_12NOTES "
      . "LEFT OUTER JOIN ZTHING PROJECT on THING.ZPROJECT = PROJECT.Z_PK "
      . "LEFT OUTER JOIN ZTHING AREA on THING.ZAREA = AREA.Z_PK "
      . "WHERE (Z_12TAGS.Z_14TAGS = 60 OR Z_12TAGS.Z_14TAGS = 61 OR Z_12TAGS.Z_14TAGS = 63 OR Z_12TAGS.Z_14TAGS = 65) "
      . "and THING.ZSTATUS != 3 and THING.ZTRASHED = 0;"); #60 = Waiting For

   printDateTable($db, "Oldest", "
      SELECT ZTHING.ZTITLE as title, "
      . "AREA.ZTITLE as area, PROJECT.ZTITLE as project, "
      . "date(ZTHING.ZCREATIONDATE, 'unixepoch', '+31 years', 'localtime') as createdate, "
      . "round(julianday(datetime('now')) - julianday(datetime(ZTHING.ZCREATIONDATE, 'unixepoch', '+31 years', 'localtime')), 1) || ' days' as date "
      . "FROM ZTHING "
      . "LEFT OUTER JOIN ZTHING PROJECT on ZTHING.ZPROJECT = PROJECT.Z_PK "
      . "LEFT OUTER JOIN ZTHING AREA on ZTHING.ZAREA = AREA.Z_PK "
      . "WHERE ZTHING.ZSTATUS != 3 and ZTHING.ZCOMPACT != 0 and ZTHING.Z_ENT = 13 AND ZTHING.ZTRASHED = 0 " #ignore deleted, completed, tags, meta, ...
      . "AND ZTHING.ZSCHEDULER is null " # ignore things scheduled for the future (including repeating)
      . "AND NOT (ZTHING.ZSCHEDULER is null and ZTHING.ZSTART = 2) " # ignore 'someday' items
      . "AND ZTHING.Z_PK NOT IN (SELECT DISTINCT ZPROJECT FROM ZTHING WHERE ZPROJECT IS NOT NULL) " #ignore projects
      . "AND ZTHING.Z_PK NOT IN (SELECT DISTINCT ZAREA FROM ZTHING WHERE ZAREA IS NOT NULL) " #ignore areas
      . "ORDER BY ZTHING.ZCREATIONDATE ASC limit 10;"); #just show oldest 10

   #todo: some of the oldest things are actually Projects and Areas of responsibility. Show only items

 	createHistoryChart($db); 
   createHistoryChart_age($db);  
} catch( Exception $exception) {
   die("Exception: " . $exception->getMessage());
}
?> 
</div>
</body>
</html>