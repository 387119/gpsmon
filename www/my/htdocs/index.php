<?php
require_once "config.php";
?>

<html DIR="LTR">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no"  /> <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<title>GPSmon</title>
<link rel='stylesheet' href='css/index.css' type='text/css'>
<link rel='stylesheet' href='css/my.tables.css' type='text/css'>
<link rel='stylesheet' href='css/my.messages.css' type='text/css'>
<link rel='stylesheet' href='css/my.tablesorter.css' type='text/css'>
<link rel='stylesheet' href='css/map.css' type='text/css'>
<link rel='stylesheet' href='jquery/css/redmond/jquery-ui-1.8.21.custom.css' type='text/css'>
<link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />

<script src="jquery/js/jquery-1.7.2.min.js" type="text/javascript"></script>
<script src="jquery/js/jquery-ui-1.8.21.custom.min.js" type="text/javascript"></script>
<script src="js/jquery.cookie.js" type="text/javascript"></script>
<script src="js/jquery.liveFilter.js" type="text/javascript"></script>
<script src="js/jquery.corner.js" type="text/javascript"></script>
<script src="js/jquery.tablesorter.js" type="text/javascript"></script>
<script src="js/jquery.toastmessage-min.js" type="text/javascript"></script>
<script type="text/javascript" src="js/flot/jquery.flot.min.js"></script>
<script src='jquery/development-bundle/ui/jquery.ui.core.js' type='text/javascript'></script>
<script src='jquery/development-bundle/ui/jquery.ui.widget.js' type='text/javascript'></script>
<script src='jquery/development-bundle/ui/jquery.ui.mouse.js' type='text/javascript'></script>
<script src='jquery/development-bundle/ui/jquery.ui.datepicker.js' type='text/javascript'></script>
<script src='jquery/development-bundle/ui/jquery.ui.selectable.js' type='text/javascript'></script>
<script src='js/onlinecars.js' type='text/javascript'></script>
<script src='js/fademess.js' type='text/javascript'></script>
</head>

<?php
if ($secure>=0){
 include_once "map.php";
 include_once "menu.php";
 include_once "menu/statusnow_st.php";
 include_once "menu/history_st.php";
 include_once "menu/reports_st.php";
 include_once "menu/geozone_st.php";
 include_once "menu/editcars_st.php";
 include_once "menu/settings_st.php";
}
else {
#include_once "login/login_head.php";
#include_once "login/login_panel.php";
include_once "login.php";
}
?>
</body>
</html>

