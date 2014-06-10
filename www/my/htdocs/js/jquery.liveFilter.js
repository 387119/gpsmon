/***********************************************************/
/*                    Плагин LiveFilter                     */
/*                      Версия: 1.2                       */
/*                      Mike Merritt                       */
/*             	   Обновлено: Apr 15th, 2010                 */
/***********************************************************/

(function($){
	$.fn.liveFilter = function (aType) {
		
		// Определяем, что будет фильтроваться.
		var filterTarget = $(this);
		var child;
		if ($(this).is('ul')) {
			child = 'li';
		} else if ($(this).is('ol')) {
			child = 'li';
		} else if ($(this).is('table')) {
			child = 'tbody tr';
		}
		
		// Определяем переменные
		var hide;
		var show;
		var filter;
		
		// событие для элемента ввода
		$('input.filter').keyup(function() {
			
			// Получаем значение фильтра
			filter = $(this).val();
			
			// Получаем то, что нужно спрятать, и то, что нужно показать
			hide = $(filterTarget).find(child + ':not(:Contains("' + filter + '"))');
			show = $(filterTarget).find(child + ':Contains("' + filter + '")')
			
			// Анимируем пункты, которые нужно спрятать и показать
			if ( aType == 'basic' ) {
				hide.hide();
				show.show();
			} else if ( aType == 'slide' ) {
				hide.slideUp(500);
				show.slideDown(500);
			} else if ( aType == 'fade' ) {
				hide.fadeOut(400);
				show.fadeIn(400);
			}
			
		});		
		
		// Пользовательское выражение для нечувствительной к регистру текста функции contains()
		jQuery.expr[':'].Contains = function(a,i,m){
		    return jQuery(a).text().toLowerCase().indexOf(m[3].toLowerCase())>=0;
		}; 

	}

})(jQuery);