<?php
 $gl_hint="";
function ahint ($type,$text){// ******************** функция добавления уведомлений и ошибок
 global $gl_hint;
 $class="dokk";
 if ($type==-1)$class="derr";
 $gl_hint=$gl_hint."<div id=$class>$text</div>\n";
}
function shint ($type){// *************************** функция отчистки уведомлений и ошибок, и вывода их
 global $gl_hint;
 if (strcmp($type,"clear")==0)$gl_hint="";
 echo "<div>$gl_hint</div>";
}
function getdesclatlng($lat1,$lon1,$lat2,$lon2){
  $R=6371;
  $d="";
  settype($glat,"float");
  settype($glon,"float");
  $rlat=deg2rad($lat2-$lat1);
  $rlon=deg2rad($lon2-$lon1);
  $rlat1=deg2rad($lat1);
  $rlat2=deg2rad($lat2);
  $a=sin($rlat/2)*sin($rlat/2)+sin($rlon/2)*sin($rlon/2)*cos($rlat1)*cos($rlat2);
  $c=2*atan2(sqrt($a),sqrt(1-$a));
  $d=$R*$c*1000;
  $d= (int) ($d);
  settype($d,"int");
  return $d;
}
?>
