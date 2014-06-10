
function show_fade_message (type,text)
{
 $().toastmessage('showToast',{
 text: text,
 position: 'top-right',
 stayTime: 30000,
 type: type
 });
}
function get_fade_messages ()
{
$.ajax({
         url: 'jq/fademess.php',
         dataType: "json",
         success: function (result) {
            $(result.regions).each(function() {
		show_fade_message ($(this).attr('type'),$(this).attr('text'));
            });
             }
      });

}

$(document).ready(function(){
 setInterval("get_fade_messages()", 30000);
});

