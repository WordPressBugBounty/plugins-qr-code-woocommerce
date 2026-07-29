'use strict';

/**
 * Generate a QR code into #product_qrcode_{id}.
 *
 * @param {string} permalink Target URL encoded into the QR.
 * @param {string|number} id Product / coupon / variation ID.
 */
function genqrcode(permalink, id) {
	if (typeof wooqr_options === 'undefined' || !wooqr_options || !wooqr_options.qr_options) {
		return;
	}

	var target = document.getElementById('product_qrcode_' + id);
	if (!target || typeof kjua !== 'function') {
		return;
	}

	if (target.getAttribute('data-wooqr-ready') === '1') {
		return;
	}

	var opts = Object.assign({}, wooqr_options.qr_options);
	opts.text = String(permalink || '');

	if (opts.image && typeof opts.image === 'string') {
		var wooqrImg = document.createElement('img');
		wooqrImg.src = opts.image;
		opts.image = wooqrImg;
	}

	target.appendChild(kjua(opts));
	target.setAttribute('data-wooqr-ready', '1');
}

/**
 * Initialize QR nodes that declare data-wooqr-text (no inline scripts).
 */
function wooqrInitFromDom() {
	var nodes = document.querySelectorAll('.product_qrcode[data-wooqr-text]');
	Array.prototype.forEach.call(nodes, function (el) {
		var id = el.getAttribute('data-wooqr-id') || String(el.id || '').replace(/^product_qrcode_/, '');
		var text = el.getAttribute('data-wooqr-text') || '';
		if (id && text) {
			genqrcode(text, id);
		}
	});
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', wooqrInitFromDom);
} else {
	wooqrInitFromDom();
}
