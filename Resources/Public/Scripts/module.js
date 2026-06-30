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
});
