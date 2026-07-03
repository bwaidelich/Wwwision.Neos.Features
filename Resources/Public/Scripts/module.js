window.addEventListener("DOMContentLoaded", () => {
	document.addEventListener('click', event => {
		const show = event.target.closest('.show-option-editor');
		if (show) {
			event.preventDefault();

			const container = show.closest('.option');

			container.querySelector('.option-preview').classList.toggle('neos-hide');
			container.querySelector('.option-editor').classList.toggle('neos-hide');

			const template = container.querySelector('template');
			container.querySelector('.option-editor-container').replaceChildren(template.content.cloneNode(true));
			return;
		}

		const hide = event.target.closest('.hide-option-editor');
		if (hide) {
			event.preventDefault();

			const container = hide.closest('.option');

			container.querySelector('.option-preview').classList.toggle('neos-hide');
			container.querySelector('.option-editor').classList.toggle('neos-hide');

			container.querySelector('.option-editor-container').replaceChildren();
		}
	});

	const featureCheckboxes = Array.prototype.slice.call(document.querySelectorAll('input[data-feature-select]'));
	const submitButtons = Array.prototype.slice.call(document.querySelectorAll('button[form="form-batch-activate"]'));
	const checkboxesByFeatureId = {};
	featureCheckboxes.forEach(function (checkbox) {
		checkboxesByFeatureId[checkbox.value] = checkbox;
	});

	function updateSubmitButtons() {
		const anyChecked = featureCheckboxes.some(function (checkbox) {
			return checkbox.checked;
		});
		submitButtons.forEach(function (button) {
			button.disabled = !anyChecked;
		});
	}

	function checkDependencies(checkbox) {
		(checkbox.dataset.dependsOn || '').split(',').forEach(function (featureId) {
			const dependencyCheckbox = checkboxesByFeatureId[featureId.trim()];
			if (dependencyCheckbox && !dependencyCheckbox.checked) {
				dependencyCheckbox.checked = true;
				checkDependencies(dependencyCheckbox);
			}
		});
	}

	featureCheckboxes.forEach(function (checkbox) {
		checkbox.addEventListener('change', function () {
			if (checkbox.checked) {
				checkDependencies(checkbox);
			}
			updateSubmitButtons();
		});
	});

	Array.prototype.slice.call(document.querySelectorAll('input[data-select-group]')).forEach(function (groupCheckbox) {
		groupCheckbox.addEventListener('change', function () {
			const groupContainer = document.getElementById(groupCheckbox.dataset.selectGroup);
			if (!groupContainer) {
				return;
			}
			Array.prototype.slice.call(groupContainer.querySelectorAll('input[data-feature-select]')).forEach(function (checkbox) {
				checkbox.checked = groupCheckbox.checked;
				if (checkbox.checked) {
					checkDependencies(checkbox);
				}
			});
			updateSubmitButtons();
		});
	});

	updateSubmitButtons();
});
