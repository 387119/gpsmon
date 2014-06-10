<?php require_once("config.php");?>
        <script type="text/javascript" src="js/jquery.easing.js"></script>
        <script type="text/javascript" src="js/jquery.sweet-menu-1.0.js"></script>

		<script type="text/javascript"> 
function showmenu (menuname){
 if (menuname=='statusnow')menu_statusnow_show();
 if (menuname=='history')menu_history_show();
 if (menuname=='reports')menu_reports_show();
 if (menuname=='geozone')menu_geozone_show();
 if (menuname=='settings')menu_settings_show();
}
			$(document).ready(function(){ 
				
				$('#exampleMenu').sweetMenu({
					top: 300,
					padding: 7,
					iconSize: 20,
					easing: 'easeOutBounce',
					duration: 100,
					icons: [
						'images/menu/home.png',
						'images/menu/history.png',
						'images/menu/reports.png',
						'images/menu/geozone.png',
						'images/menu/adminconsole.png',
<?php if ($secure>=70)echo "'images/menu/adminconsole.png',"; ?>
						'images/menu/exit.png'
					]
				});
			});
		</script>
		
        <style type="text/css">
		
            .sweetMenuAnchor{
                border-top: 1px solid #ffffff;
                border-right: 1px solid #ffffff;
                border-bottom: 1px solid #ffffff;
				border-top-right-radius: 4px;
				-moz-border-radius-topright: 4px;
				border-bottom-right-radius: 4px;
				-moz-border-radius-bottomright: 4px;
				color: #000000;
				font-size: 12px;
				font-weight: bold;
				text-align: right;
				text-transform: uppercase;
				font-family: arial;
				text-decoration: none;
				background-color: #888888;
				opacity: 0.9;
			}
			
			.sweetMenuAnchor span{
				display: block;
				padding-top: 10px;
			}
			
        </style>
<div>
	<ul id="exampleMenu">
            <li><a href="javascript:showmenu('statusnow');">Общая информация</a></li>
            <li><a href="javascript:showmenu('history');">История движения</a></li>
            <li><a href="javascript:showmenu('reports');">Генерация отчётов</a></li>
            <li><a href="javascript:showmenu('geozone');">Управление геозонами</a></li>
            <li><a href="javascript:showmenu('settings');">Настройки</a></li>
<?php if ($secure>=70)echo "<li><a href=\"/admin/admin.php\" target=_blank>Административная консоль</a></li>"; ?>
<?php 
 $res=pg_query("select fam || ' ' ||name || ' '|| otch  as whostr from users where userid=$userid;");
 echo "<li><a href=\"index.php?typelogin=exitlogin\">Выход&nbsp;&nbsp;<font size=1>(".pg_result($res,0,"whostr").")</font>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a></li>";
?>
            
        </ul>
</div>
