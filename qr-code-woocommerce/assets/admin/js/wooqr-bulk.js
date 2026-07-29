document.addEventListener('DOMContentLoaded', function () {
	'use strict';

	if (typeof wooqr === 'undefined' || !wooqr.qr_options) {
		return;
	}

	var qrOptions = Object.assign({}, wooqr.qr_options);
	if (qrOptions.image && typeof qrOptions.image === 'string') {
		var wooqrImg = document.createElement('img');
		wooqrImg.src = qrOptions.image;
		qrOptions.image = wooqrImg;
	}

	var myHeaders = new Headers();
	myHeaders.append('X-WP-Nonce', wooqr.wp_rest);

	var requestOptions = {
		method: 'GET',
		headers: myHeaders,
		redirect: 'follow',
		credentials: 'same-origin'
	};

	var container = document.querySelector('#wooqr-data');
	if (!container) {
		return;
	}

	function setStatus(message) {
		var status = document.getElementById('wooqr-status');
		if (status) {
			status.textContent = message || '';
		}
	}

	function editUrl(postId) {
		var base = wooqr.admin_url || (document.location.origin + '/wp-admin/post.php');
		return base + (base.indexOf('?') >= 0 ? '&' : '?') + 'post=' + encodeURIComponent(postId) + '&action=edit';
	}

	function createEl(tag, className, text) {
		var el = document.createElement(tag);
		if (className) {
			el.className = className;
		}
		if (typeof text === 'string') {
			el.textContent = text;
		}
		return el;
	}

	function appendPrintButton(parent, productId) {
		var actions = createEl('div', 'wooqr_actions');
		var printBtn = createEl('div', 'button button-primary print-qr dashicons-before dashicons-print', 'Print');
		printBtn.setAttribute('data-product_id', String(productId));
		actions.appendChild(printBtn);
		parent.appendChild(actions);
		return actions;
	}

	function appendQr(productItem, permalink) {
		var opts = Object.assign({}, qrOptions);
		opts.text = permalink || '';
		var qrWrap = createEl('div', 'iqr-image');
		productItem.insertBefore(qrWrap, productItem.firstChild);
		if (typeof kjua === 'function') {
			qrWrap.appendChild(kjua(opts));
		}
	}

	function buildProductItem(p) {
		var productItem = createEl('li', 'result pro-item product-grid-item product_qrcode_content ptype-' + String(p.type || ''));
		productItem.id = 'result_' + p.id;
		productItem.setAttribute('data-proid', String(p.id));

		var idSpan = createEl('span', 'iid');
		var idLink = document.createElement('a');
		idLink.href = editUrl(p.id);
		idLink.textContent = '#' + p.id;
		idSpan.appendChild(idLink);
		idSpan.appendChild(document.createTextNode(' - ' + String(p.type || '')));

		var nameDiv = createEl('div', 'iname bulk_product-qr-code-title', p.name || '');
		var priceDiv = createEl('div', 'iprice bulk_product-qr-code-price');
		// price_html is trusted WooCommerce HTML for admins; keep via sanitizing parse.
		priceDiv.innerHTML = (p.price_html || '').replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, '');

		productItem.appendChild(idSpan);
		productItem.appendChild(nameDiv);
		productItem.appendChild(priceDiv);

		var actions = appendPrintButton(productItem, p.id);
		if (p.type === 'variable') {
			var showVar = createEl('div', 'button button-primary show-qr-variations dashicons-before dashicons-print', 'Show Variations');
			showVar.setAttribute('data-product_id', String(p.id));
			showVar.setAttribute('data-product_title', p.name || '');
			actions.appendChild(showVar);
		}

		appendQr(productItem, p.permalink);
		return productItem;
	}

	function buildVariationItem(p, parentId, parentName) {
		var productItem = createEl('li', 'result pro-item product-grid-item product_qrcode_content qr-variations');
		productItem.id = 'result_' + p.id;
		productItem.setAttribute('data-proid', String(p.id));

		var idSpan = createEl('span', 'iid');
		var idLink = document.createElement('a');
		idLink.href = editUrl(parentId);
		idLink.textContent = '#' + p.id;
		idSpan.appendChild(idLink);
		idSpan.appendChild(document.createTextNode(' - variation'));

		var nameDiv = createEl('div', 'iname bulk_product-qr-code-title', parentName || '');
		var attrs = createEl('div', 'vproduct-attrs');
		if (p.attributes && p.attributes.length) {
			p.attributes.forEach(function (attr) {
				var line = createEl('div', '', (attr.name || '') + ': ' + (attr.option || ''));
				attrs.appendChild(line);
			});
		}
		nameDiv.appendChild(attrs);

		var priceDiv = createEl('div', 'iprice bulk_product-qr-code-price');
		if (p.price) {
			priceDiv.textContent = String(wooqr.woo_currency || '') + String(p.price);
		} else {
			priceDiv.textContent = 'price not set';
		}

		productItem.appendChild(idSpan);
		productItem.appendChild(nameDiv);
		productItem.appendChild(priceDiv);
		appendPrintButton(productItem, p.id);
		appendQr(productItem, p.permalink);
		return productItem;
	}

	function createProductItem(pro_page) {
		pro_page = pro_page || 1;

		fetch(wooqr.wp_rest_url + 'wc/v3/products?per_page=30&orderby=id&order=asc&page=' + pro_page, requestOptions)
			.then(function (response) {
				if (response.status !== 200) {
					setStatus('Looks like there was a problem. Status Code: ' + response.status);
					return null;
				}
				return response.json().then(function (data) {
					return { data: data, response: response };
				});
			})
			.then(function (result) {
				if (!result) {
					return;
				}
				result.data.forEach(function (p) {
					container.appendChild(buildProductItem(p));
				});
				pro_page += 1;
				var totalPages = parseInt(result.response.headers.get('X-WP-TotalPages'), 10) || 1;
				if (pro_page <= totalPages) {
					setTimeout(function () {
						createProductItem(pro_page);
					}, 100);
				} else {
					var loader = document.getElementById('wooqr_loader');
					if (loader) {
						loader.style.display = 'none';
					}
				}
			})
			.catch(function () {
				setStatus('Fetch Error');
			});
	}

	createProductItem();

	document.addEventListener('click', function (e) {
		if (!(e.target && e.target.classList.contains('show-qr-variations'))) {
			return;
		}

		var pid = e.target.getAttribute('data-product_id');
		var pname = e.target.getAttribute('data-product_title') || '';
		e.target.classList.remove('show-qr-variations');

		function wooqr_fetch_variations(vpro_page) {
			vpro_page = vpro_page || 1;
			fetch(wooqr.wp_rest_url + 'wc/v3/products/' + encodeURIComponent(pid) + '/variations/?per_page=30&orderby=id&order=asc&page=' + vpro_page, requestOptions)
				.then(function (response) {
					if (response.status !== 200) {
						setStatus('Looks like there was a problem. Status Code: ' + response.status);
						return null;
					}
					return response.json().then(function (data) {
						return { data: data, response: response };
					});
				})
				.then(function (result) {
					if (!result) {
						return;
					}
					result.data.forEach(function (p) {
						var productItem = buildVariationItem(p, pid, pname);
						var currentli = document.getElementById('result_' + pid);
						if (currentli && currentli.parentNode) {
							currentli.parentNode.insertBefore(productItem, currentli.nextSibling);
						} else {
							container.appendChild(productItem);
						}
					});
					vpro_page += 1;
					var totalPages = parseInt(result.response.headers.get('X-WP-TotalPages'), 10) || 1;
					if (vpro_page <= totalPages) {
						setTimeout(function () {
							wooqr_fetch_variations(vpro_page);
						}, 100);
					} else {
						var loader = document.getElementById('wooqr_loader');
						if (loader) {
							loader.style.display = 'none';
						}
						if (e.target && e.target.parentElement) {
							e.target.parentElement.removeChild(e.target);
						}
					}
				})
				.catch(function () {
					setStatus('Fetch Error');
				});
		}

		wooqr_fetch_variations(1);
	});
});
