var modalTmpl;

(function() {
	'use strict';
	
	var viewElement = null;
	var routes = {};
	
	// The route registering function:
	function route (path, templateId) {
	  routes[path] = {templateId: templateId};
	}

	function router () {
		viewElement = viewElement || document.getElementById('view');
		var url = location.hash.slice(1) || '/';
		var route = routes[url];
		if (!route) {
			window.location.replace('#/');
			$('#error-404').modal('show');
			return;
		}
		$('.tmpl').css('display', 'none');
		$('#' + route.templateId).css('display', 'inherit');
		$('.active').removeClass('active');
		$('#' + route.templateId + '-link').addClass('active');
	}

	function makeModal(id, header, body) {
		if (!modalTmpl) {
			$.ajax('../templates/modal.html', {
				dataType: 'html',
				async: false,
				complete: function(response){
					modalTmpl = response.responseText;
				}
			});
		}
		var modal = $(modalTmpl.replace(/\{\{modal\-id\}\}/g, id)
			.replace('{{header}}', header)
			.replace('{{body}}', body));
		modal.modal({show: false});
		$('.modal-dialogs').append(modal);
	}
	
	window.addEventListener('hashchange', router);
	window.addEventListener('load', function() {
		
		var img=new Image();
		img.src='assets/ajax-loader.gif';
			
		var params;
		

		function show_spinner () {
		  $("#spinner-front").addClass("show");
		  $("#spinner-back").addClass("show");
		}
		
		function hide_spinner () {
		  $("#spinner-front").removeClass("show");
		  $("#spinner-back").removeClass("show");
		}
		
		
		var self = this;
		$.ajax('./api/params.php', {
			dataType: 'json',
			async: false,
			complete: function(response) {
				self.params = response.responseJSON;
			}
		});
		
		if (this.params.power == 'off') {
			makeModal('power-off', ' Nog even geduld a.u.b.', 'De digitale stembus opent binnekort, nog even geduld dus!');
			$('#power-off').modal('show');
			return;
		}
		
		
		var ul = $('<ul class="navbar-nav mr-auto"></ul>');
		$('#navbar').append(ul);
		
		var navLinks = [
			{id: 'home',       label: 'Home'},
			{id: 'vote',       label: 'Stemmen'}
		];
		
		$.each(navLinks, function(i, el) {
			var url = (el.id === 'home' ? '/' : '/' + el.id);
			var tmpl = '../templates/' + el.id + '.html';
			ul.append($('<li class="nav-item"><a class="nav-link" href="#'+url+'">'+el.label+'</a>'));
			$.ajax(tmpl, {
				dataType: 'html',
				async: false,
				complete: function(response) {
					$('#view').append($('<div id="'+el.id+'" class="tmpl">'+response.responseText+'</div>'));
				}
			});
			route(url, el.id);
		});
		router();
		
		var url = new URL(window.location.href);
		var code = url.searchParams.get("code");
		if (code) {
			$('#code').val(code);
		}
		var email = url.searchParams.get("confirm-email");
		if (email) {
			$('#confirm-email').val(email);
		}

		makeModal('error-404', 'Ongeldige pagina.', 'De pagina die je probeert te bezoeken bestaat niet.');
		makeModal('vote-form-feedback', 'Stemformulier', '');
		
		
		$(':input').each(function(i, el){
			if ($(el).attr('id') && !$(el).attr('name')) $(el).attr('name', $(el).attr('id'));
		})
		
		$('[data-toggle="tooltip"]').tooltip();
				
		$('#vote-form').submit(function(e){
			if (this.checkValidity() === false) {
				event.preventDefault();
				event.stopPropagation();
			}
			this.classList.add('was-validated');
			if (this.checkValidity() == false) return;
			
			event.preventDefault();
			event.stopPropagation();
			
			show_spinner();
			$.getJSON( "api/vote.php", $(this).serializeArray(), function(data, textStatus, jqXHR) {
				if (data.found==false) {
					$($('#vote-form-feedback').find('.modal-body')).html('<p>Het is helaas niet gelukt om je stem te ontvangen. Hieronder zie je de reden:</p><p><code>'+data.reason+'</code></p>')
				} else {
					var emailMessage = jqXHR.getResponseHeader('X-Error-Message');
					if (emailMessage) {
						$($('#vote-form-feedback').find('.modal-body')).html('<p>Je stem is uitgebracht en vastgelegd in ons systeem.</p>');
					} else {
						$($('#vote-form-feedback').find('.modal-body')).html('<p>Je stem is uitgebracht en vastgelegd in ons systeem. We hebben een bevestiging van je stem naar je e-mailadres (' + data['confirm-email'] + ') verzonden.</p><p>Dank je wel voor je stem en hopelijk zien we elkaar op de Algemene Ledenvergadering!');
					}
					$('#code').val('');
					$('#confirm-email').val('');
					window.location.replace('#/');
				}
				hide_spinner();
				$('#vote-form-feedback').modal('show');
			})
			.fail(function(){
				alert('Het stemmen is niet gelukt door een technische fout.');
				hide_spinner();
			});
		});
			

	}, false);
})();

