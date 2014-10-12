$(function () {
	var tabContainers = $('div.tabs > div');
	tabContainers.hide().filter(':tab1').show();
	
	$('div.tabs ul.tabNavigation a').click(function () {
		tabContainers.hide();
		tabContainers.filter(this.hash).show();
		$('div.tabs ul.tabNavigation a').removeClass('selected');
		$(this).addClass('selected');
		return false;
	}).filter(':tab1').click();
	
	
});
