(function () {
    'use strict';

    var featureCheckboxes = Array.prototype.slice.call(document.querySelectorAll('input[data-feature-select]'));
    var submitButtons = Array.prototype.slice.call(document.querySelectorAll('button[form="form-batch-activate"]'));
    var checkboxesByFeatureId = {};
    featureCheckboxes.forEach(function (checkbox) {
        checkboxesByFeatureId[checkbox.value] = checkbox;
    });

    function updateSubmitButtons() {
        var anyChecked = featureCheckboxes.some(function (checkbox) {
            return checkbox.checked;
        });
        submitButtons.forEach(function (button) {
            button.disabled = !anyChecked;
        });
    }

    function checkDependencies(checkbox) {
        (checkbox.dataset.dependsOn || '').split(',').forEach(function (featureId) {
            var dependencyCheckbox = checkboxesByFeatureId[featureId.trim()];
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
            var groupContainer = document.getElementById(groupCheckbox.dataset.selectGroup);
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
})();
