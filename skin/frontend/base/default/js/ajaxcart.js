var AjaxcartForm = Class.create(VarienForm, {
	submit: function(btn, successCallback, errorCallback) {
		if (! successCallback || typeof successCallback != 'function') {
			throw new Error(Translator.translate('arg2 (Function successCallback) is required'));
		}

		try {
			// attempt to push event, discard if analytics unavailable
			var ev = {
				hitType: 'event',
				eventCategory: 'Cart',
				eventAction: 'Add to Cart'
			};
			var id = btn.form.action.match(/product\/(\d+)/);
			if (id.length == 2) {
				ev.value = parseInt(id[1]);
			}
			ga('send', ev);
		}
		catch (e) {}

		// workaround for paypal express
		var p = document.getElementById('pp_checkout_url');
		if (p && p.value) {
			return btn.form.submit();
		}

		if (! errorCallback || typeof errorCallback != 'function') {
			errorCallback = function(data, transport) {
				if (typeof data == 'string') {
					// presume connectivity error
					alert(data);
					if (transport) {
						console.log(transport);
					}
				}
				else {
					// error response from ajax
					alert(data.messages.join("\n"));
				}
			};
		}

		var lo = btn.innerHTML;
		var ln = '<span><span><em>'+Translator.translate('Loading...')+'</em></span></span>';
		btn.innerHTML = ln;

		new Ajax.Request(btn.form.action, {
			method: 'post',
			parameters: btn.form.serialize(),
			onFailure: function(transport) {
				btn.innerHTML = lo;
				err = Translator.translate('There was an error contacting the server - please try again shortly.');
				errorCallback(err, transport);
			},
			onSuccess: function(transport) {
				btn.innerHTML = lo;
				if (transport.status != 200) {
					return this.onFailure(transport);
				}
				try {
					var data = JSON.parse(transport.responseText);
				}
				catch (e) {
					err = Translator.translate('There was an error understanding the server response - please try again shortly.');
					errorCallback(err, transport);
					return;
				}

				if (data.success) {
					successCallback(data);
				}
				else {
					errorCallback(data);
				}
			}
		});
	}
});
