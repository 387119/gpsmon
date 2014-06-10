var carsicon="";
var i;
for (i=1;i<=94;i++){
 carsicon=carsicon+'<a href="javascript:setcaricon('+i+');"><img src=/images/cars/car'+i+'.png></a>';
};
    $(function(){
      $( "#tabs" ).tabs({
        cookie: { expires:1 }
      });
      $("#newcar, #newtracker, #newuser").button();
      $("#dialog").dialog({modal:true,autoOpen: false});
      $("#cartarirovka").dialog({modal:true,autoOpen: false});
      $("#dialog_icon").dialog({modal:true,autoOpen: false});
      $('#dialog_icon').dialog({ title: 'Выбор иконки'});
      $('#dialog_icon').html(carsicon);
      $('#dialog_icon').dialog( 'option', 'height', 330 );
      $('#dialog_icon').dialog( 'option', 'width', 540);
    });
function cartar(id){
// тарировка бака с датчиком уровня топлива
$('#cartarirovka').dialog('open');
}
function caruser_send(id){
//сохраняем доступы пользователей к машине
$('#caruserform').submit();
}
function usercar_send(id){
//сохраняем доступы пользователя к машинам
$('#usercarform').submit();
}

function caruser (id){
   $("#dialog").html("");
   $('#dialog').dialog({ title: 'Выбор пользователей'});
   $('#dialog').dialog( 'option', 'height', 300 );
   $('#dialog').dialog( 'option', 'width', 400);
   $.ajax({
   url: '/admin/caruser_form.php?c='+id,
    success: function (data, textStatus) {
     $("#dialog").html(data);
     $('#dialog').dialog('open');
    }
   });
 
}

function usercar (id){
   $("#dialog").html("");
   $('#dialog').dialog({ title: 'Выбор машин'});
   $('#dialog').dialog( 'option', 'height', 300 );
   $('#dialog').dialog( 'option', 'width', 400);
   $.ajax({
   url: '/admin/usercar_form.php?u='+id,
    success: function (data, textStatus) {
     $("#dialog").html(data);
     $('#dialog').dialog('open');
    }
   });
 
}

function setcaricon (id){
 document.getElementById('imgcar').src='/images/cars/car'+id+'.png';
 document.getElementById('cicon').value='car'+id+'.png';
 $('#dialog_icon').dialog('close');
}
function caricon (){
 $('#dialog_icon').dialog('open');
}

function carform_post(){
 var cidf=document.getElementById('cidf').value;
 var cname=document.getElementById('cname').value;
 var cgosnum=document.getElementById('cgosnum').value;
 var cicon=document.getElementById('cicon').value;
 var ctracker=document.getElementById('ctracker').value;
 var cmaxspeed=document.getElementById('cmaxspeed').value;
 var cdutlitr1=document.getElementById('cdutlitr1').value;
 var cdutmin1=document.getElementById('cdutmin1').value;
 var cdutmax1=document.getElementById('cdutmax1').value;
 var cdutlitr2=document.getElementById('cdutlitr2').value;
 var cdutmin2=document.getElementById('cdutmin2').value;
 var cdutmax2=document.getElementById('cdutmax2').value;
 var txt;
 if (cname!=''){
  $.ajax({
   url: '/admin/caredit.php?t=s&u='+cidf+'&name='+cname+'&gosnum='+cgosnum+'&icon='+cicon+'&tracker='+ctracker+'&maxspeed='+cmaxspeed+'&dutlitr1='+cdutlitr1+'&dutmin1='+cdutmin1+'&dutmax1='+cdutmax1+'&dutlitr2='+cdutlitr2+'&dutmin2='+cdutmin2+'&dutmax2='+cdutmax2,
    success: function (data, textStatus) {
    if (data == 1){
     if (cidf!=-1)txt="данные машины успешно изменены";
      else txt="Новая машина успешно создана";
     alert (txt);
     $('#dialog').text('');
     $('#dialog').dialog({ title: 'false'});
     $('#dialog').dialog('close');
     window.location.reload();
    }
    else alert ('Ошибка: '+data);
   },
   error: function (data, status, msg) {
    alert('error loading ajax data');
   }
  });
  }//if all null
  else alert ("Ошибка, поле название должно быть заполненно");
}


function carformnew(){
 $('#dialog').dialog({ title: 'Добавление новой машины'});
 $('#dialog').html('<div id=atab><input id=cidf name=cidf value="-1" type=hidden><input id=cicon name=cicon value="car1.png" type=hidden><table>\
 <tr><td>Название</td><td><input id=cname name=cname value=""></td></tr>\
 <tr><td>гос. номер</td><td><input id=cgosnum name=cgosnum value=""></td></tr>\
 <tr><td>tracker</td><td><input id=ctracker name=ctracker value=""></td></tr>\
 <tr><td>макс. скорость</td><td><input id=cmaxspeed name=cmaxspeed value=""></td></tr>\
 <tr><td>литраж 1 бака</td><td><input id=cdutlitr1 name=cdutlitr1 value=""></td></tr>\
 <tr><td>мин частота 1 бака</td><td><input id=cdutmin1 name=cdutmin1 value=""></td></tr>\
 <tr><td>макс частота 1 бака</td><td><input id=cdutmax1 name=cdutmax1 value=""></td></tr>\
 <tr><td>литраж 2 бака</td><td><input id=cdutlitr2 name=cdutlitr2 value=""></td></tr>\
 <tr><td>мин частота 2 бака</td><td><input id=cdutmin2 name=cdutmin2 value=""></td></tr>\
 <tr><td>макс частота 2 бака</td><td><input id=cdutmax2 name=cdutmax2 value=""></td></tr>\
 <tr><td>иконка</td><td><img id=imgcar src="/images/cars/car1.png"><button onclick="caricon();">сменить</button></td></tr>\
 </table>\
 <button onclick="carform_post();">Создать</button>\
 </div>');
 $('#dialog').dialog( 'option', 'height', 300 );
 $('#dialog').dialog( 'option', 'width', 400);
 $('#dialog').dialog('open');

}

function carformedit(id){
var txt="",cname="",cgosnum="",cicon="",ctracker="";
  $.ajax({
   url: '/admin/caredit.php?t=g&u='+id,
   dataType: "json",
    success: function (result) {
          if (result.type == 'error') {
            alert('error of update data, please push F5 for update web page');
            return(false);
          }
          else{
            $(result.regions).each(function() {
              cname=$(this).attr('name');
              cgosnum=$(this).attr('gosnum');
              cicon=$(this).attr('icon');
              ctracker=$(this).attr('tracker');
              cmaxspeed=$(this).attr('maxspeed');
              cdutlitr1=$(this).attr('dutlitr1');
              cdutmin1=$(this).attr('dutmin1');
              cdutmax1=$(this).attr('dutmax1');
              cdutlitr2=$(this).attr('dutlitr2');
              cdutmin2=$(this).attr('dutmin2');
              cdutmax2=$(this).attr('dutmax2');
 $('#dialog').html('<div id=atab><input id=cidf name=cidf value="'+id+'" type=hidden><input id=cicon name=cicon value="'+cicon+'" type=hidden><table>\
 <tr><td>название</td><td><input id=cname name=cname value="'+cname+'"></td></tr>\
 <tr><td>гос. номер</td><td><input id=cgosnum name=cgosnum value="'+cgosnum+'"></td></tr>\
 <tr><td>tracker</td><td><input id=ctracker name=ctracker value="'+ctracker+'"></td></tr>\
 <tr><td>макс. скорость</td><td><input id=cmaxspeed name=cmaxspeed value="'+cmaxspeed+'"></td></tr>\
 <tr><td>литраж 1 бака</td><td><input id=cdutlitr1 name=cdutlitr1 value="'+cdutlitr1+'"></td></tr>\
 <tr><td>мин частота 1 бака</td><td><input id=cdutmin1 name=cdutmin1 value="'+cdutmin1+'"></td></tr>\
 <tr><td>макс частота 1 бака</td><td><input id=cdutmax1 name=cdutmax1 value="'+cdutmax1+'"></td></tr>\
 <tr><td>литраж 2 бака</td><td><input id=cdutlitr2 name=cdutlitr2 value="'+cdutlitr2+'"></td></tr>\
 <tr><td>мин частота 2 бака</td><td><input id=cdutmin2 name=cdutmin2 value="'+cdutmin2+'"></td></tr>\
 <tr><td>макс частота 2 бака</td><td><input id=cdutmax2 name=cdutmax2 value="'+cdutmax2+'"></td></tr>\
 <tr><td>иконка</td><td><img id=imgcar src="/images/cars/'+cicon+'"><button onclick="caricon();">сменить</button></td></tr>\
 </table>\
 <button onclick="carform_post();">Сохранить</button>\
 </div>');

              });
             }
   },
   error: function (data, status, msg) {
    alert('error loading ajax data');
   }
  });
//////
 $('#dialog').dialog({ title: 'Редактирование машины №'+id});
 $('#dialog').dialog( 'option', 'height', 600 );
 $('#dialog').dialog( 'option', 'width', 400);
 $('#dialog').dialog('open');

}

////////////////////////////////////////////////////////////////////////////

function trackerform_post(){
 var tidf=document.getElementById('tidf').value;
 var tcar=document.getElementById('tcar').value;
 var timei=document.getElementById('timei').value;
 var tphone=document.getElementById('tphone').value;
 var tpasswd=document.getElementById('tpasswd').value;
 var tserialnum=document.getElementById('tserialnum').value;
 var txt;
 if (timei!=''){
  $.ajax({
   url: '/admin/trackeredit.php?t=s&u='+tidf+'&car='+tcar+'&imei='+timei+'&phone='+tphone+'&passwd='+tpasswd+'&serialnum='+tserialnum,
    success: function (data, textStatus) {
    if (data == 1){
     if (tidf!=-1)txt="данные трекера успешно изменены";
      else txt="Новый трекер успешно создан";
     alert (txt);
     $('#dialog').text('');
     $('#dialog').dialog({ title: 'false'});
     $('#dialog').dialog('close');
     window.location.reload();
    }
    else alert ('Ошибка: '+data);
   },
   error: function (data, status, msg) {
    alert('error loading ajax data');
   }
  });
  }//if all null
  else alert ("Ошибка, поле imei должно быть заполненно");
}


function trackerformnew(){
 $('#dialog').dialog({ title: 'Добавление нового трекера'});
 $('#dialog').html('<div id=atab><input id=tidf name=tidf value="-1" type=hidden><table>\
 <tr><td>car</td><td><input id=tcar name=tcar value=""></td></tr>\
 <tr><td>серийный №</td><td><input id=tserialnum name=tserialnum value=""></td></tr>\
 <tr><td>imei</td><td><input id=timei name=timei value=""></td></tr>\
 <tr><td>phone</td><td><input id=tphone name=tphone value=""></td></tr>\
 <tr><td>password</td><td><input id=tpasswd name=tpasswd value=""></td></tr>\
 </table>\
 <button onclick="trackerform_post();">Создать</button>\
 </div>');
 $('#dialog').dialog( 'option', 'height', 320 );
 $('#dialog').dialog( 'option', 'width', 400);
 $('#dialog').dialog('open');

}

function trackerformedit(id){
var txt="",timei="",tcarid="",tphone="",tpasswd="",tserialnum="";
  $.ajax({
   url: '/admin/trackeredit.php?t=g&u='+id,
   dataType: "json",
    success: function (result) {
          if (result.type == 'error') {
            alert('error of update data, please push F5 for update web page');
            return(false);
          }
          else{
            $(result.regions).each(function() {
              tcarid=$(this).attr('carid');
              timei=$(this).attr('imei');
              tphone=$(this).attr('phone');
              tpasswd=$(this).attr('passwd');
              tserialnum=$(this).attr('serialnum');
 $('#dialog').html('<div id=atab><input id=tidf name=tidf value="'+id+'" type=hidden><table>\
 <tr><td>car</td><td><input id=tcar name=tcar value="'+tcarid+'"></td></tr>\
 <tr><td>серийный №</td><td><input id=tserialnum name=tserialnum value="'+tserialnum+'"></td></tr>\
 <tr><td>imei</td><td><input id=timei name=timei value="'+timei+'"></td></tr>\
 <tr><td>phone</td><td><input id=tphone name=tphone value="'+tphone+'"></td></tr>\
 <tr><td>password</td><td><input id=tpasswd name=tpasswd value="'+tpasswd+'"></td></tr>\
 </table>\
 <button onclick="trackerform_post();">Сохранить</button>\
 </div>');

              });
             }
   },
   error: function (data, status, msg) {
    alert('error loading ajax data');
   }
  });
//////
 $('#dialog').dialog({ title: 'Редактирование трекера №'+id});
 $('#dialog').dialog( 'option', 'height', 340 );
 $('#dialog').dialog( 'option', 'width', 450);
 $('#dialog').dialog('open');

}

////////////////////////////////////////////////////////////////////////////

function userform_post(){
 var uidf=document.getElementById('uidf').value;
 var ulogin=document.getElementById('ulogin').value;
 var ufam=document.getElementById('ufam').value;
 var uname=document.getElementById('uname').value;
 var uotch=document.getElementById('uotch').value;
 var usecure=document.getElementById('usecure').value;
 var upasswd1=document.getElementById('upasswd1').value;
 var upasswd2=document.getElementById('upasswd2').value;
 var txt;
 if ((ulogin!='')&&(ufam!='')&& (uname!='')&&(uotch!='')&&(usecure!='')&&(upasswd1!='')&&(upasswd2!='')){
 if (upasswd1==upasswd2){
  $.ajax({
   url: '/admin/useredit.php?t=s&u='+uidf+'&login='+ulogin+'&fam='+ufam+'&name='+uname+'&otch='+uotch+'&secure='+usecure+'&passwd='+upasswd1,
    success: function (data, textStatus) {
    if (data == 1){
     if (uidf!=-1)txt="данные пользователя успешно изменены";
      else txt="Новый пользователь успешно создан";
     alert (txt);
     $('#dialog').text('');
     $('#dialog').dialog({ title: 'false'});
     $('#dialog').dialog('close');
     window.location.reload();
    }
    else alert ('Ошибка: '+data);
   },
   error: function (data, status, msg) {
    alert('error loading ajax data');
   }
  });
  }//if passwd== passwd2
  else alert ("Ошибка, пароли пользователя не совпадают");
  }//if all null
  else alert ("Ошибка, все поля обязательны к заполнению");
}

function userformnew(){
 $('#dialog').dialog({ title: 'Добавление нового пользователя'});
 $('#dialog').html('<div id=atab><input type=hidden id=uidf name=uidf value="-1"><table>\
 <tr><td>Логин</td><td><input id=ulogin name=ulogin value=""></td></tr>\
 <tr><td>Фамилия</td><td><input id=ufam name=ufam value=""></td></tr>\
 <tr><td>Имя</td><td><input id=uname name=uname value=""></td></tr>\
 <tr><td>Отчество</td><td><input id=uotch name=uotch value=""></td></tr>\
 <tr><td>Доступ</td><td><select id=usecure name usecure><option value="0">нет доступа</option><option value="10">Пользователь</option><option value="70">Администратор</option></select></td></tr>\
 <tr><td>Пароль</td><td><input type=password id=upasswd1 name=upasswd1 value=""></td></tr>\
 <tr><td>Подтверждение</td><td><input type=password id=upasswd2 name=upasswd2 value=""></td><tr>\
 </table>\
 <button onclick="userform_post();">Создать</button>\
 </div>');
 $('#dialog').dialog( 'option', 'height', 400 );
 $('#dialog').dialog( 'option', 'width', 450);
 $('#dialog').dialog('open');

}
function userformedit(id){
var sec=Array();
sec[0]="";
sec[10]="";
sec[70]="";
var txt="",ulogin="",ufam="",uname="",uotch="";
  $.ajax({
   url: '/admin/useredit.php?t=g&u='+id,
   dataType: "json",
    success: function (result) {
          if (result.type == 'error') {
            alert('error of update data, please push F5 for update web page');
            return(false);
          }
          else{
            $(result.regions).each(function() {
              ulogin=$(this).attr('login');
              ufam=$(this).attr('fam');
              uname=$(this).attr('name');
              uotch=$(this).attr('otch');
              sec[$(this).attr('sec')]="selected";
 $('#dialog').html('<div id=atab><input type=hidden id=upasswd1 name=upasswd1 value="notchange"><input type=hidden id=upasswd2 name=upasswd2 value="notchange"><input type=hidden id=uidf name=uidf value='+id+'><table>\
 <tr><td>Логин</td><td><input id=ulogin name=ulogin value="'+ulogin+'"></td></tr>\
 <tr><td>Фамилия</td><td><input id=ufam name=ufam value="'+ufam+'"></td></tr>\
 <tr><td>Имя</td><td><input id=uname name=uname value="'+uname+'"></td></tr>\
 <tr><td>Отчество</td><td><input id=uotch name=uotch value="'+uotch+'"></td></tr>\
 <tr><td>Доступ</td><td><select id=usecure name usecure><option value="0" '+sec[0]+' >нет доступа</option><option value="10" '+sec[10]+' >Пользователь</option><option value="70" '+sec[70]+' >Администратор</option></select></td></tr>\
 </table>\
 <button onclick="userform_post();">Сохранить</button>\
 </div>');

              });
             }
   },
   error: function (data, status, msg) {
    alert('error loading ajax data');
   }
  });
//////
 $('#dialog').dialog({ title: 'Редактирование пользователя '});
 $('#dialog').dialog( 'option', 'height', 330 );
 $('#dialog').dialog( 'option', 'width', 450);
 $('#dialog').dialog('open');

}

function chpwd(){
 var pwd1=document.getElementById('pwd1').value;
 var pwd2=document.getElementById('pwd2').value;
 var userid=document.getElementById('userid').value;
 if (pwd1==pwd2){
  if (pwd1!=''){
////////////
  $.ajax({
   url: '/admin/chpwd.php?u='+userid+'&p='+pwd1,
    success: function (data, textStatus) {
    if (data == 1){
     alert ('пароль пользователя успешно изменён');
     $('#dialog').text('');
     $('#dialog').dialog({ title: 'false'});
     $('#dialog').dialog('close');
    }
    else {
     alert ('Ошибка изменения пароля');
    }
   },
   error: function (data, status, msg) {
    alert('error loading ajax data');
   }
  });  

/////////////
  }
  else {alert ('Ошибка: пароль не может быть пустым');}
 }
  else {alert ('Ошибка: пароли не совпадают');}
};

function changepasswd(uid,login){
 $('#dialog').html('<div id=atab><table><tr><td>пароль:</td><td><input type=hidden name=userid id=userid value='+uid+'><input id=pwd1 name=pwd1 type=password></td></tr><tr><td>подтверждение:</td><td><input type=password id=pwd2 name=pwd2></td></tr></table><button onclick="chpwd();">сменить</button></div>');
 $('#dialog').dialog({ title: 'Смена пароля для ' + login});
 $('#dialog').dialog( 'option', 'height', 200 );
 $('#dialog').dialog( 'option', 'width', 450 );
 $('#dialog').dialog('open');
};

function delcar (obj,objtxt,id){
 var answer = confirm("Вы действительно хотите удалить "+objtxt+" "+id);
 if (answer){
  $.ajax({
   url: '/admin/delrec.php?t='+obj+'&o='+id,
    success: function (data, textStatus) {
    if (data == 1){
     alert ('объект успешно удалён, для более полной информации просмотрите логи, страница будет автоматически обновлена');
     window.location.reload();
    }
    else {
     alert ('Ошибка удаления. ' + data);
    }
   },
   error: function (data, status, msg) {
    document.getElementById(tres).innerHTML="error loading ajax data";
   }
  });  
 }
}
