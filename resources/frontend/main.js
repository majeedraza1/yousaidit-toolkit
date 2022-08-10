import './variation-swatches';
// import '@/web-components/DynamicCardCanvas.js'
// import '@/web-components/DynamicCardBackground.js'
// import '@/web-components/DynamicCardLayer.js'

let $ = window.jQuery;

$(document).on('click', '[name="_show_rude_card"]', function () {
	$.blockUI();
	let value = $(this).val();
	$.ajax({
		method: 'POST',
		url: wc_add_to_cart_params.ajax_url,
		data: {
			action: 'show_rude_card',
			value: value,
		},
		success: function (response) {
			if (response.should_reload) {
				window.location.reload();
			} else {
				$.unblockUI();
			}
		},
		error: function () {
			$.unblockUI();
		}
	});
});

$(document).on('click', '[name="_show_rude_card_dialog"]', function () {
	$.blockUI();
	let value = $(this).val();
	$.ajax({
		method: 'POST',
		url: wc_add_to_cart_params.ajax_url,
		data: {
			action: 'show_rude_card_dialog',
			value: value,
		},
		success: function (response) {
			if (response.should_reload) {
				window.location.reload();
			} else {
				$.unblockUI();
			}
		},
		error: function () {
			$.unblockUI();
		}
	});
});

jQuery('.designer_dropdown_product_cat').on('change', function () {
	let url = new URL(window.location.href);

	if (jQuery(this).val()) {
		url.searchParams.set('product_cat', jQuery(this).val());
	} else {
		if (url.searchParams.has('product_cat')) {
			url.searchParams.delete('product_cat');
		}
	}
	location.href = url.toString();
});
if (jQuery().selectWoo) {
	let designer_dropdown_product_cat = function () {
		jQuery('.designer_dropdown_product_cat').selectWoo({
			placeholder: 'Select a category',
			minimumResultsForSearch: 5,
			width: '100%',
			allowClear: true,
			language: {
				noResults: function () {
					return 'No matches found';
				}
			}
		});
	};
	designer_dropdown_product_cat();
}

let widget = $('.yousaidit-top-bar').find('.widget');
widget.each(function (index, element) {
	setTimeout(function () {
		let title = $(element).find('.widget-title');
		$(element).find('.select2-selection__placeholder').html(title.html());
		$(element).find('.select2-selection__rendered').html(title.html());
	}, 310);
});

function fixShippingColspanIssue() {
	let cartForm = $(document).find('.cart-collaterals'),
		shippingTr = cartForm.find('tr.shipping th');
	if (shippingTr) {
		setTimeout(() => {
			$(shippingTr).attr('colspan', '0')
		}, 310);
	}
}

$(document).ajaxComplete(() => {
	fixShippingColspanIssue();
});
$(document).on('wc_update_cart', () => {
	fixShippingColspanIssue();
});

let initialPriceHtml = $('body').find('.yousaidit-single-product__price-amount').html()
document.addEventListener('change.VariationSwatches', variation => {
	let body = $('body'),
		vPriceHtml = body.find('.woocommerce-variation-price'),
		priceDiv = body.find('.yousaidit-single-product__price-amount');
	if (vPriceHtml) {
		priceDiv.html(vPriceHtml.html());
	} else {
		priceDiv.html(initialPriceHtml);
	}
});
