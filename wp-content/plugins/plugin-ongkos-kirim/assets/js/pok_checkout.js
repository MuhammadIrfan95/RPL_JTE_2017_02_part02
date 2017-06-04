 
(function($){
	var local = pok_checkout_data;
	//load returning user data
	pok_load_returning_user_data = function() {
		if (parseInt(local.billing_state) && local.billing_country == 'ID') {
			$('#billing_state').val(local.billing_state);
			if (parseInt(local.billing_city)) {
				$('#billing_city').on('options_loaded', function(e, city_list) {
					if (city_list[local.billing_city]) {
						if (!local.enableDistrict) $('#billing_city').addClass('update_totals_on_change');
						$('#billing_city').val(local.billing_city).trigger('change');
					}
				});
				if (parseInt(local.billing_district) && local.enableDistrict) {
					$('#billing_district').on('options_loaded', function(e, district_list) {
						if (district_list[local.billing_district]) {
							$('#billing_district').addClass('update_totals_on_change').val(local.billing_district).trigger('change');
						}
					});
				}
			}
		}
		if (parseInt(local.shipping_state) && local.shipping_country == 'ID') {
			$('#shipping_state').val(local.shipping_state);
			if (parseInt(local.shipping_city)) {
				$('#shipping_city').on('options_loaded', function(e, city_list) {
					if (city_list[local.shipping_city]) {
						if (!local.enableDistrict) $('#shipping_city').addClass('update_totals_on_change');
						$('#shipping_city').val(local.shipping_city).trigger('change');
					}
				});
				if (parseInt(local.shipping_district) && local.enableDistrict) {
					$('#shipping_district').on('options_loaded', function(e, district_list) {
						if (district_list[local.shipping_district]) {
							$('#shipping_district').addClass('update_totals_on_change').val(local.shipping_district).trigger('change');
						}
					});
				}
			}
		}
	}

	//check country
	pok_check_country = function(context) {
		if (context === 'billing') {
			$('#billing_country').val(local.billing_country);
		} else if (context === 'shipping') {
			$('#shipping_country').val(local.shipping_country);
		}
		$('#'+context+'_country').on('change',function(){
			$('#'+context+'_country_field, #'+context+'_state_field').addClass('pok_loading');
			$('#'+context+'_country, #'+context+'_state').prop('disabled', true);
			$.ajax({
				type: 'POST',
				url: local.ajaxurl,
				data: {
					action: 'pok_change_country',
					country: $('#'+context+'_country').val() 
				},
				success: function(data){
					if (data == 'reload') {
						$('*').css('cursor','wait');
						location.reload();
					} else {
						$('#'+context+'_country_field, #'+context+'_state_field').removeClass('pok_loading');
						$('#'+context+'_country, #'+context+'_state').prop('disabled', false);
					}
				}
			});
		});
	}

	//load city list
	pok_load_city = function(context) {
		if (context !== 'billing' && context !== 'shipping') return;
		var state = $('#'+context+'_state').val();
		if (state) {
			$('#'+context+'_city_field, #'+context+'_district_field').prop('title',local.labelLoadingCity).addClass('pok_loading');
			$('#'+context+'_city, #'+context+'_district').prop('disabled', true);
			$.post(local.ajaxurl, {
				action: 'get_list_city',
				nextNonce: local.nextNonce,
				province_id: state
			}, function(data, status) {
				var arr = $.parseJSON(data);
				if (state != 0 && (status != "success" || Array.isArray(arr))) {
					$('#'+context+'_city_field').removeAttr('title').removeClass('pok_loading');
					$('#'+context+'_city').prop('disabled', false);
					if (confirm( local.labelFailedCity )) {
						return pok_load_city(context);
					}
					return;
				} 

				$('#'+context+'_city').val('').empty().append('<option value="">'+local.labelSelectCity+'</option>');
				$('#'+context+'_district').val('').empty().append('<option value="">'+local.labelSelectDistrict+'</option>'); 
				$.each(arr, function (i,v) {
					if (v != '' && v != '0') {
					   $('#'+context+'_city').append('<option value="'+i+'">'+v+'</option>');       
					}
				});
				
				$('#'+context+'_city_field, #'+context+'_district_field').removeAttr('title').removeClass('pok_loading');
				$('#'+context+'_city').prop('disabled', false).trigger('chosen:updated').trigger('options_loaded', arr);
				$('#'+context+'_district').prop('disabled', false).trigger('chosen:updated');
				if (!local.enableDistrict) {
					$('#'+context+'_city_field').addClass('update_totals_on_change');
				}
			});
		}
	}

	//load district list
	pok_load_district = function(context) {
		if (context !== 'billing' && context !== 'shipping') return;
		var city = $('#'+context+'_city').val();
		if (parseInt(city)) {
			$('#'+context+'_district_field').prop('title',local.labelLoadingDistrict).addClass('pok_loading');
			$('#'+context+'_district').prop('disabled',true);
			$.post(local.ajaxurl, {
				action: 'get_list_district',
				nextNonce: local.nextNonce,
				city_id: city        
			}, function(data,status) {
				var arr = $.parseJSON(data);
				if (city != 0 && (status != "success" || Array.isArray(arr))) {
					$('#'+context+'_district_field').removeAttr('title').removeClass('pok_loading');
					$('#'+context+'_district').prop('disabled', false);
					if (confirm( local.labelFailedDistrict )) {
						return pok_load_district(context);
					}
					return;
				}

				$('#'+context+'_district').val('').empty().append('<option value="">'+local.labelSelectDistrict+'</option>'); 
				$.each(arr, function (i,v) {
					if (v != '' && v != '0') {               
					   $('#'+context+'_district').append('<option value="'+i+'">'+v+'</option>');       
					}
				});
				$('#'+context+'_district_field').removeAttr('title').removeClass('pok_loading').addClass('update_totals_on_change');
				$('#'+context+'_district').prop('disabled', false).trigger('chosen:updated').trigger('options_loaded', arr);
			});
		}
	}

	pok_check_country('billing');
	pok_check_country('shipping');
	if (local.loadReturningUserData === 'yes') {
		pok_load_returning_user_data();
	}
	$('#billing_state').on('change',function() {
		pok_load_city('billing');
	});
	$('#shipping_state').on('change',function() {
		pok_load_city('shipping');
	});
	if (local.enableDistrict) {
		$('#billing_city').on('change',function() {
			pok_load_district('billing');
		});
		$('#shipping_city').on('change',function() {
			pok_load_district('shipping');
		});
	}

	// reorder hack
	var wrappers = $('.woocommerce-billing-fields__field-wrapper, .woocommerce-shipping-fields__field-wrapper');
	wrappers.each( function( index, wrapper ) {
		var orig_class = $(wrapper).attr('class');
		$(wrapper).removeClass(orig_class).addClass(orig_class+'-pok').find('.form-row').sort(function (a, b) {
			var fieldA = parseInt($(a).attr('data-priority')) || parseInt($(a).attr('data-sort'));
			var fieldB = parseInt($(b).attr('data-priority')) || parseInt($(b).attr('data-sort'));
			return (fieldA < fieldB) ? -1 : (fieldA > fieldB) ? 1 : 0;
		}).appendTo(wrapper);
	});
		

})(jQuery);
