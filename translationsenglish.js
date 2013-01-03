$(document).ready(function(){

	$('#SumitChangeTranslationKey').click(function () {

		var type = "";

		$('#formTranslation input[type="checkbox"]').each(function() {
			if($(this).is(':checked'))
				type += $(this).attr('name')+',';
		});

		$('#details').css('background', '#999');
		$('#details').html('<img src="../modules/translationsenglish/ajax-loader.gif" alt="Loading..." style="margin:180px 0 0 340px;" /><ul></ul>');

		$.ajax({
			type: 'POST',
			url: params.link,
			data : {
				ajax: params.ajax,
				ajaxMode: params.ajax,
				controller: params.controller,
				tab: params.controller,
				configure: params.configure,
				token: params.token,
				module_name: params.module_name,
				action: params.action,
				type: type
			},
			success : function(res)
			{
				$('#details').css('background', '#fff');
				$('#details img').remove();
				$('#details ul').html(res);
			}
		});
	});

});
