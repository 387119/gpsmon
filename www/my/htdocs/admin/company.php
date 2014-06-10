<?php
require_once "./config.php";

if ($secure>=90){
echo "<script>
function clientformnew(){
 $('#dialog').html('Название:<input id=inputnewcomp><br><button onclick=\"makenewclient();\">Создать</button>');
 $('#dialog').dialog({title:'Новое предприятие'});
 $('#dialog').dialog('open');
};

function makenewclient(){
var nn=$('#inputnewcomp').val();
$.ajax({
 url: '/admin/company_new.php?n='+nn,
  success: function (data, textStatus) {
   if (data.length>0){
    alert (data);
   }
   else{
    alert ('Новый клиент успешно создан');
    window.location.reload();
   }
  }
});
};

function delclient(cid){
if(confirm('Вы действительно хотите удалить компанию номер '+cid+'?')){
$.ajax({
 url: '/admin/company_del.php?c='+cid,
  success: function (data, textStatus) {
    alert (data+' предприяте успешно удалёно, но данные которые были у предприятия не удалены, их отчистку надо проводить вручную');
    window.location.reload();
  }
});
}
}
</script>";
echo "<div id=atab><table><tr><th>id</th><th>Название</th></tr>";
$res=pg_query("select * from clients order by company_name;");
while ($res1=pg_fetch_array($res)){
 extract($res1,EXTR_OVERWRITE);
 echo "<tr><td>$clientid</td><td>$company_name</td><td><a href=\"javascript:delclient($clientid);\">удалить</a></td></tr>";
}

echo "</table>
<button id=newcar onclick=\"clientformnew();\">Новый клиент</button>
</div>";

}

?>


