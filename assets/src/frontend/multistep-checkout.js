let isCheckoutShippingFieldsValid = () => {
	let checkoutBillingFields = document.querySelector('.field--checkout_shipping'),
		requiredFields = checkoutBillingFields.querySelectorAll('.validate-required'),
		isValid = true;

	requiredFields.forEach(field => {
		let _input = field.querySelector('input, select');
		if ('' === _input.value) {
			isValid = false;
		}
	});

	let sent_to = document.querySelector('[name="item_sent_to"]:checked');
	if (!(sent_to && sent_to.value)) {
		isValid = false;
	}

	return isValid;
}

let isCheckoutBillingFieldsValid = () => {
	let checkoutBillingFields = document.querySelector('.field--checkout_billing'),
		requiredFields = checkoutBillingFields.querySelectorAll('.validate-required'),
		isValid = true;

	requiredFields.forEach(field => {
		let _input = field.querySelector('input, select');
		if ('' === _input.value) {
			isValid = false;
		}
	});

	return isValid;
}

let activeTabSlug = function () {
	let activeTab = document.querySelector('.checkout-tab-item.is-current'),
		target = activeTab.getAttribute('data-target');
	return target ? target : '';
}

let activeTabIndex = function () {
	let allTabs = document.querySelectorAll('.checkout-tab-item'),
		activeTab = document.querySelector('.checkout-tab-item.is-current');
	let currentTabIndex = -1;
	Array.prototype.forEach.call(allTabs, (_tab, index) => {
		if (_tab === activeTab) {
			currentTabIndex = index;
		}
	});
	return currentTabIndex;
}

let getTabNav = function (indexNumber) {
	let allNavs = document.querySelectorAll('.checkout-tab-item');
	let nav = null;
	Array.prototype.forEach.call(allNavs, (_tab, index) => {
		if (index === indexNumber) {
			nav = _tab;
		}
	});
	return nav;
}

let activateTab = function (indexNumber) {
	let tabToActivate = getTabNav(indexNumber);
	if (!tabToActivate) {
		return;
	}
	let tabKey = tabToActivate.getAttribute('data-target'),
		targetTab = document.querySelector('[data-tab=' + tabKey + ']');

	if (!targetTab) {
		return;
	}

	let tabs = document.querySelectorAll('[data-tab]');
	tabs.forEach((_tab, index) => {
		_tab.style.display = (index === indexNumber) ? 'block' : 'none';
	});

	let allNavs = document.querySelectorAll('.checkout-tab-item'),
		totalNavItems = 0;
	Array.prototype.forEach.call(allNavs, (navItem, index) => {
		totalNavItems += 1;
		if (index < indexNumber) {
			navItem.classList.remove('is-current');
			navItem.classList.add('is-completed');
		} else if (index === indexNumber) {
			navItem.classList.remove('is-completed');
			navItem.classList.add('is-current');
		} else {
			navItem.classList.remove('is-current');
			navItem.classList.remove('is-completed');
		}
	});

	let navClass = '';
	if (indexNumber === (totalNavItems - 1)) {
		navClass = 'last-step'
	} else if (indexNumber === 0) {
		navClass = 'first-step';
	}

	let checkoutNavContainer = document.querySelector('.checkout-nav');
	checkoutNavContainer.classList.remove('last-step');
	checkoutNavContainer.classList.remove('first-step');
	if (navClass) {
		checkoutNavContainer.classList.add(navClass);
	}

	if (checkoutNavContainer.classList.contains('has-login-form')) {
		let nxtButton = checkoutNavContainer.querySelector('.button.button--checkout-next');
		if (indexNumber === 0) {
			nxtButton.innerHTML = 'Skip Login / Register'
		} else {
			nxtButton.innerHTML = 'Next'
		}
	}
}

let isFormDataValid = function () {
	if ('tab-shipping' === activeTabSlug() && !isCheckoutShippingFieldsValid()) {
		return false;
	}
	return !('tab-billing' === activeTabSlug() && !isCheckoutBillingFieldsValid());
}

// Handle tab selection
document.addEventListener('click', event => {
	let navToActivate = event.target.closest('.checkout-tab-item'),
		notificationDiv = document.querySelector('.checkout-notification');

	if (!navToActivate) {
		return;
	}

	let allNavs = document.querySelectorAll('.checkout-tab-item');
	let navToActivateIndex = -1;
	Array.prototype.forEach.call(allNavs, (_tab, index) => {
		if (_tab === navToActivate) {
			navToActivateIndex = index;
		}
	});

	if (activeTabIndex() > navToActivateIndex) {
		activateTab(navToActivateIndex);
		notificationDiv.style.display = '';
	} else {
		if (isFormDataValid()) {
			activateTab(navToActivateIndex);
			notificationDiv.style.display = '';
		} else {
			notificationDiv.style.display = 'block';
		}
	}

	let element = document.getElementById("yousaidit-checkout-tabs");
	element.scrollIntoView({behavior: "smooth", block: "start", inline: "start"});
});

window.addEventListener('load', event => {
	activateTab(0);
});

// handle sent to selection
document.addEventListener('click', event => {
	if (event.target.classList.contains('radio-item_sent_to')) {
		let sent_to = document.querySelector('.field--checkout_shipping');
		sent_to.style.display = 'block';
	}
});

// Pre, Next button
document.addEventListener('click', event => {
	let navClicked = '';
	if (event.target.closest('.button--checkout-pre')) {
		event.preventDefault();
		navClicked = 'pre';
	}
	if (event.target.closest('.button--checkout-next')) {
		event.preventDefault();
		navClicked = 'next';
	}

	if (navClicked) {
		let element = document.getElementById("yousaidit-checkout-tabs");
		element.scrollIntoView({behavior: "smooth", block: "start", inline: "start"});

		let activeIndex = activeTabIndex(),
			nexTabIndex = activeIndex,
			notificationDiv = document.querySelector('.checkout-notification');

		if (navClicked === 'pre') {
			nexTabIndex -= 1;
			activateTab(nexTabIndex);
		}

		if (navClicked === 'next') {
			if (isFormDataValid()) {
				nexTabIndex += 1;
				activateTab(nexTabIndex);
				notificationDiv.style.display = '';
			} else {
				notificationDiv.style.display = 'block';
			}
		}
	}
})

// Handle inline tab selection
document.addEventListener('click', event => {
	let navToActivate = event.target.closest('.checkout-inline-tabs__item');

	if (!navToActivate) {
		return;
	}

	let tabKey = navToActivate.getAttribute('data-inline_target'),
		targetTab = document.querySelector('[data-inline_tab=' + tabKey + ']');

	let tabs = document.querySelectorAll('[data-inline_tab]');
	tabs.forEach((_tab) => {
		_tab.style.display = (_tab === targetTab) ? 'block' : 'none';
	});

	let navs = document.querySelectorAll('[data-inline_target]');
	navs.forEach((_nav) => {
		if (_nav === navToActivate) {
			_nav.classList.add('is-current');
		} else {
			_nav.classList.remove('is-current');
		}
	});
});


// add column span
jQuery(jQuery(document).find('tr.shipping th')).attr('colspan', '2');

jQuery(document).ajaxComplete(function () {
	jQuery(jQuery(document).find('tr.shipping th')).attr('colspan', '2');
});
