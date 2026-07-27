(function () {
	'use strict';

	function $(selector, ctx) {
		return (ctx || document).querySelector(selector);
	}
	function $all(selector, ctx) {
		return Array.prototype.slice.call((ctx || document).querySelectorAll(selector));
	}

	function toast(message, isError) {
		var el = $('#vt-toast');
		if (!el) return;
		el.textContent = message;
		el.className = 'vt-toast vt-toast-visible' + (isError ? ' vt-toast-error' : '');
		window.clearTimeout(el._timer);
		el._timer = window.setTimeout(function () {
			el.className = 'vt-toast';
		}, 4000);
	}

	function post(action, data) {
		var body = new FormData();
		body.append('action', action);
		body.append('nonce', window.VT_DATA.nonce);
		Object.keys(data || {}).forEach(function (key) {
			body.append(key, data[key]);
		});
		return fetch(window.VT_DATA.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
			.then(function (r) { return r.json(); });
	}

	function reload() {
		window.location.reload();
	}

	function initTabs() {
		var tabs = $all('.vt-tab');
		tabs.forEach(function (tab) {
			tab.addEventListener('click', function () {
				tabs.forEach(function (t) { t.classList.remove('active'); });
				$all('.vt-panel').forEach(function (p) { p.classList.remove('active'); });
				tab.classList.add('active');
				var panel = document.querySelector('.vt-panel[data-panel="' + tab.getAttribute('data-tab') + '"]');
				if (panel) panel.classList.add('active');
			});
		});
	}

	function initRequestForm() {
		var form = $('#vt-request-form');
		if (!form) return;
		var warning = $('#vt-form-warning');

		function refreshPreview() {
			var start = form.start_date.value, end = form.end_date.value;
			if (!start || !end) return;
			post('vt_preview_days', {
				start_date: start,
				end_date: end,
				start_duration: form.start_duration.value,
				end_duration: form.end_duration.value
			}).then(function (res) {
				if (!res.success) return;
				$('#vt-preview-days').textContent = res.data.days;
				$('#vt-preview-balance').textContent = res.data.balance;
				$('#vt-preview-projected').textContent = res.data.projected;
			});
		}

		['start_date', 'end_date', 'start_duration', 'end_duration'].forEach(function (name) {
			form[name].addEventListener('change', refreshPreview);
		});

		$('#vt-save-draft').addEventListener('click', function () {
			post('vt_save_draft', formData(form)).then(function (res) {
				if (res.success) {
					form.request_id.value = res.data.request_id;
					toast('Draft saved.');
				} else {
					toast(res.data.message, true);
				}
			});
		});

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			warning.hidden = true;
			post('vt_submit_request', formData(form)).then(function (res) {
				if (res.success) {
					form.hidden = true;
					$('#vt-confirmation').hidden = false;
				} else {
					warning.textContent = res.data.message;
					warning.hidden = false;
				}
			});
		});
	}

	function formData(form) {
		var data = {};
		Array.prototype.forEach.call(form.elements, function (el) {
			if (el.name) data[el.name] = el.value;
		});
		return data;
	}

	function initActionButtons() {
		document.addEventListener('click', function (e) {
			var el = e.target;

			if (el.classList.contains('vt-action-withdraw')) {
				if (!confirm('Withdraw this request?')) return;
				post('vt_withdraw_request', { request_id: el.getAttribute('data-id') }).then(handleSimple);
			}
			if (el.classList.contains('vt-action-cancel')) {
				if (!confirm('Request a change/cancellation for this approved vacation?')) return;
				post('vt_request_cancellation', { request_id: el.getAttribute('data-id') }).then(handleSimple);
			}
			if (el.classList.contains('vt-action-decide')) {
				var decision = el.getAttribute('data-decision');
				var comments = '';
				if (decision === 'reject' || decision === 'more_info') {
					comments = window.prompt('Add a comment for the employee:') || '';
				}
				post('vt_decide', { request_id: el.getAttribute('data-id'), decision: decision, comments: comments }).then(handleSimple);
			}
			if (el.classList.contains('vt-action-cancel-decide')) {
				post('vt_decide_cancellation', { request_id: el.getAttribute('data-id'), approved: el.getAttribute('data-approved') }).then(handleSimple);
			}
		});
	}

	function handleSimple(res) {
		if (res.success) {
			toast(res.data.message);
			window.setTimeout(reload, 900);
		} else {
			toast(res.data.message, true);
		}
	}

	function initDelegationForm() {
		var form = $('#vt-delegation-form');
		if (!form) return;
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			post('vt_set_delegation', formData(form)).then(handleSimple);
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		initTabs();
		initRequestForm();
		initActionButtons();
		initDelegationForm();
	});
})();
