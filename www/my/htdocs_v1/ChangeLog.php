<?php
 exec('cd /opt/gpsmon/repo ; git log --no-merges --format="Date: %cd%nAuthor: %an < %ae >%n%B"',$x);
 foreach ($x as $k => $v){
  echo "<div style='font-family:sans-serif;'>";
  if (strcmp(substr($v,0,7),"Author:")==0)echo "<span style='color:77aa33;font-weight:bold;'>";
  if (strcmp(substr($v,0,5),"Date:")==0)echo "<br><span style='color:3377aa;font-weight:bold;'>";
  echo $v;
  echo "</div>";
 }
?>