    function chkbox_s(chklist_n,param){
        var p = param;
        if (p==0){
            var nn = chklist_n;
            var checked = $('#checklist'+nn).is(':checked');
            if (checked == true){
                $('#d_'+nn).css({'background':'url("images/theme/unchecked.png") no-repeat','width':'14px','height':'14px','background-position':'center'});
                $('#checklist'+nn).attr('checked', false);
//                check_avto();
            }
            else if(checked == false){
                $('#d_'+nn).css({'background':'url("images/theme/checked.png") no-repeat','width':'14px','height':'14px','background-position':'center'});
                $('#checklist'+nn).attr('checked', true);
//                check_avto();
            }
        }
        else if (p==1){
            var ng = chklist_n;
            var checked = $('#checkgrp'+ng).is(':checked');
            if (checked == true){
                $('#grp'+ng).css({'background':'url("images/theme/unchecked.png") no-repeat','width':'21px','height':'14px','background-position':'center'});
                $('#checkgrp'+ng).attr('checked', false);
//                ch_all(0);
//                display_ingrups(ng);
            }
            else{
                $('#grp'+ng).css({'background':'url("images/theme/checked.png") no-repeat','width':'21px','height':'14px','background-position':'center'});
                $('#checkgrp'+ng).attr('checked', true);
//                ch_all(1);
//                display_ingrups(ng);
            }
        }
    }
var markforcenter;

 function center_map (lat,lon,zoom){
  if (markforcenter != undefined){
   markforcenter.setMap(null);
  }
  map.setCenter(new google.maps.LatLng(lat,lon));
  map.setZoom(zoom);
  markforcenter=new google.maps.Marker({
   position: new google.maps.LatLng(lat,lon),
   map: map,
   title:"Стоянка"
  });

  google.maps.event.addListener(markforcenter, 'click', function(event) {
    markforcenter.setMap(null);
  });

 }
