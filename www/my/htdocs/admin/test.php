<?php
require_once "./config.php";
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no"  />
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<script src="/js/jquery.js" type="text/javascript"></script>
<script src=\"/js/jquery-ui-1.8.18.custom.min.js\"></script>
<script src=\"/js/jquery.cookie.js\"></script>
<link type=\"text/css\" href=\"/css/tabs/jquery-ui-1.8.18.custom.css\" rel=\"stylesheet\" />
<link type=\"text/css\" href=\"/css/table.css\" rel=\"stylesheet\" />
<style>
body {
 background:url('/images/bottom_texture.jpg');
 padding:0;
 margin:0;
 overflow: hidden;
}
.divtopgl{position:relative;left:0;top:0;bottom:0;right:0;background-attachment:fixed;background-image:url(/images/page_gl.png);background-repeat:no-repeat;background-position:top left;}
</style>
<?php
if ($secure>=50){

echo "
  <script>

    $(function(){
      $( \"#tabs\" ).tabs({
        cookie: { expires:1 }
      });
      $(\"#newcar, #newtracker, #newuser\").button();
      $(\"#dialog\").dialog({modal:true,autoOpen: false});
    });
  </script>
<script>
function delcar (obj,objtxt,id){
 var answer = confirm(\"Вы действительно хотите удалить \"+objtxt+\" \"+id);
 if (answer){
  $.ajax({
   url: '/admin/delrec.php?t='+obj+'&o='+id,
    success: function (data, textStatus) {
    if (data == 1){
      alert ('объект успешно удалён, для более полной информации просмотрите логи');
//     window.reload();
    }
   },
   error: function (data, status, msg) {
    document.getElementById(tres).innerHTML=\"error loading ajax data\";
   }
  });  
 }
}
</script>

<body cellspacing=0 cellpadding=0 style=\"padding:0;margin:0;\">
<div class='divtopgl'>";
$res=pg_query ("select company_name from clients where clientid in (select clientid from users where userid=$userid);");
  while ($resf=pg_fetch_array($res)){
   extract ($resf,EXTR_OVERWRITE);
 //   echo "<h1>$company_name</h1>";
  } 


echo "test
</div>
</body>";
}
?>

