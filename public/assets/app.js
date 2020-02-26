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
		
		$('.tmpl').each(function(i, el) {
			el = $(el);
			var tmpl = '../templates/' + el.attr('id') + '.html';
			$.ajax(tmpl, {
				dataType: 'html',
				async: false,
				complete: function(response) {
					el.append($(response.responseText));
				}
			});
		});
		
		$.getJSON('/api/kandidaten.php', {}, function(kandidaten){
			$.each(kandidaten, function(k, kandidaat) {
				var displayNaam;
				if (kandidaat.is_group) {
					displayNaam = kandidaat.naam + ', ' + kandidaat.anderen[0].naam + ', ' + kandidaat.anderen[1].naam + ' (bestuur)';
					$('#individuele-kandidaten').append('<li>' + displayNaam + '</li>');
					$('#kandidaat').append('<option value="' + kandidaat.code + '">' + displayNaam + '</option>');
				} else {
					displayNaam = kandidaat.naam + ' ('+ kandidaat.rol +')';
					$('#bestuurs-kandidaten').append('<li>' + displayNaam + '</li>');
					$('#kandidaat').append('<option value="' + kandidaat.code + '">' + displayNaam + '</option>');
				}
			});
		});

		
		makeModal('no-user-detected', 'Je moet eerst inloggen.', 'Als je wilt stemmen, je stem wilt controleren of je stem wilt annuleren dan moet je inloggen.');
		makeModal('timeout-warning', 'Inactiviteit geconstateerd.', 'Om veiligheidsredenen verloopt je login automatisch na een bepaalde periode van incativiteit. Als je niks doet zul je spoeding afgemeld worden en zul je onieuw moeten inloggen.');
		makeModal('error-404', 'Ongeldige pagina.', 'De pagina die je probeert te bezoeken bestaat niet.');
		makeModal('login-form-feedback', 'Inloggen is niet gelukt', '');
		makeModal('vote-form-feedback', 'Stemformulier', '');
		makeModal('confirm-login', 'Je bent succesvol ingelogd', '');
		
		['home', 'login', 'vote', 'kandidaten', 'uitleg'].each(function(i, el){
			console.log(i);
			console.log(el);
		});

		
		route('/', 'home', function () {});
		route('/login', 'login', function () {});
		route('/vote', 'vote', function () {});
		route('/kandidaten', 'kandidaten', function () {});
		route('/uitleg', 'uitleg', function () {});
		// route('*', 'error404', function () {alert(1);});
		
		router();

		
		$(':input').each(function(i, el){
			if ($(el).attr('id') && !$(el).attr('name')) $(el).attr('name', $(el).attr('id'));
		})
		
		$('[data-toggle="tooltip"]').tooltip();
		
	
	
	
		$('#login-form').submit(function(e){
			e.preventDefault();
			var isValid = this.checkValidity();
			this.classList.add('was-validated');
			if (isValid == false) {
				return;
			}
			
			$.getJSON( "api/login.php", $(this).serializeArray(), function(data, textStatus, jqXHR) {
				if (data.found==false) {
					if (!data.nummer) {
						$($('#login-form-feedback').find('.modal-body')).html('We kunnen jouw lidmaatschap helaas niet vinden met dit nummer en e-mailadres.')
					} else {
						$($('#login-form-feedback').find('.modal-body')).html('Aan het door jou ingevoerde lidmaatschapsnummer is een e-mailadres gekoppeld dat lijkt op <strong><code>"' + data.email+ '"</code></strong>, probeer het nogmaals met een ander e-mailadres.');
					}
					$('#login-form-feedback').modal('show');
				} else {
					if (data.voted_on) {
						$($('#confirm-login').find('.modal-body')).html('Je bent succesvol ingelogd, maar je hebt je stem al eerder uitgebracht (op '+data.voted_on+') en je kunt je stem maar één keer uitbrengen.');
						window.location.replace('#/');
					} else {
						var emailMessage = jqXHR.getResponseHeader('X-Error-Message');
						if (emailMessage) {
							$($('#confirm-login').find('.modal-body')).html('<p>Je logingegevens kloppen, maar helaas is het op dit moment niet mogelijk om je een mail te sturen met de code die je nodig hebt om te stemmen.</p><p>Probeer het eventueel later nogmaals.</p><p><code>'+emailMessage+'</code></p>');
						} else {
							$($('#confirm-login').find('.modal-body')).html('<p>We hebben je een code gestuurd naar je e-mailadres ' + data.email + '. Je hebt deze code nodig om je stem uit te brengen. </p><p><strong>Let op:</strong> om veiligheidsredenen is deze code 12 uur geldig, daarna moet je opnieuw inloggen om een code te ontvangen.</p>');
						}
					  $(':input','#confirm-login')
					    .not(':button, :submit, :reset, :hidden')
					    .val('')
					    .prop('checked', false)
					    .prop('selected', false);
						window.location.replace('#/vote');
						$('#confirm-email').val(data.email);
					}
					$('#confirm-login').modal('show');
				}
			})
			.fail(function(){
				alert('Het inloggen is niet gelukt door een technische fout.');
			});
		});
		
		$('#vote-form').submit(function(e){
			e.preventDefault();
			if (this.checkValidity() == false) return;
			$.getJSON( "api/vote.php", $(this).serializeArray(), function(data, textStatus, jqXHR) {
				if (data.found==false) {
					$($('#vote-form-feedback').find('.modal-body')).html('<p>Het is helaas niet gelukt om je stem te ontvangen. Hieronder zie je de reden:</p><p><code>'+data.reason+'</code></p>')
				} else {
					var emailMessage = jqXHR.getResponseHeader('X-Error-Message');
					if (emailMessage) {
						$($('#vote-form-feedback').find('.modal-body')).html('<p>Je stem is uitgebracht en vastgelegd in ons systeem. Helaas is het op dit moment niet mogelijk om je een mail te sturen met de bevestiging.</p><p><code>'+emailMessage+'</code></p>');
					} else {
						$($('#vote-form-feedback').find('.modal-body')).html('<p>Je stem is uitgebracht en vastgelegd in ons systeem. We hebben een bevestiging van je stem naar je e-mailadres (' + data['confirm-email'] + ') verzonden.</p><p>Dank je wel voor je stem en hopelijk zien we elkaar op de Algemene Ledenvergadering!');
					}
				  $(':input','#vote-for')
				    .not(':button, :submit, :reset, :hidden')
				    .val('')
				    .prop('checked', false)
				    .prop('selected', false);
					window.location.replace('#/kandidaten');
				}
				$('#vote-form-feedback').modal('show');
			})
			.fail(function(){
				alert('Het stemmen is niet gelukt door een technische fout.');
			});
		});
			

	}, false);
})();

