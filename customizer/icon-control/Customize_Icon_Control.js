(function (wp) {
    'use strict';

    // Extend wp.customize.Control for the 'icon' type
    wp.customize.controlConstructor['icon'] = wp.customize.Control.extend({
        ready: function () {
            const control = this;
            const container = control.container[0];

            // Get the input, preview, and reset button elements
            const input = container.querySelector('.icon-search');
            const preview = container.querySelector('.icon-preview');
            const resetButton = container.querySelector('.reset-icon');

            // Create a new container for the results box
            const results = document.createElement('div');
            results.className = 'icon-results';
            document.body.appendChild(results); // Append to body for higher positioning

            // Function to position the results box
            const positionResultsBox = () => {
                const inputRect = input.getBoundingClientRect();

                results.style.position = 'absolute';
                results.style.top = `${inputRect.bottom + window.scrollY}px`; // Adjust for scrolling
                results.style.left = `${inputRect.left}px`;
                results.style.width = `${inputRect.width}px`; // Match input width
                results.style.zIndex = '99999999'; // Ensure it appears on top
            };

            // Handle input changes for the search field
            input.addEventListener('input', function () {
                const searchValue = input.value.trim();

                // Clear previous results
                results.innerHTML = '';

                // Hide results if input is empty
                if (searchValue.length === 0) {
                    results.style.display = 'none';
                    return;
                }

                // Fetch icons from the AJAX endpoint
                fetch(`${wp.ajax.settings.url}?action=agnosticon_search&search=${encodeURIComponent(searchValue)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data) {
                            displayIcons(data.data);
                        } else {
                            results.style.display = 'none'; // Hide if no data found
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching icons:', error);
                    });
            });

            function getIconHTML(iconData) {
                let code = '';

                try {
                    code = String.fromCodePoint(`0x${iconData?.char}`);
                } catch (e) {
                    code = iconData?.char;
                }

                return `
                    <i style="${iconData.style}" data-agnosticon-code="${code}"></i>
                `;
            }

            // Display the fetched icons
            function displayIcons(icons) {
                results.style.display = 'block'; // Show results
                positionResultsBox(); // Adjust position
                results.innerHTML = ''; // Clear previous results

                Object.values(icons).forEach(iconData => {
                    const iconHTML = getIconHTML(iconData) + `<span>${iconData.name}</span>`;

                    const iconElement = document.createElement('div');
                    iconElement.className = 'icon-result';
                    iconElement.innerHTML = iconHTML;
                    iconElement.addEventListener('click', () => selectIcon(iconData));
                    results.appendChild(iconElement);
                });
            }

            // Handle icon selection
            function selectIcon(iconData) {
                const iconHTML = getIconHTML(iconData);
                input.value = iconData.id; // Store the icon ID in the input field
                preview.innerHTML = iconHTML; // Show the icon preview
                results.innerHTML = ''; // Clear results after selection
                results.style.display = 'none'; // Hide results after selection

                // Store the value in the Customizer
                control.setting.set(iconData.id); // Store the icon ID in the Customizer setting
                input.classList.add('has-icon'); // Remove the 'has-icon' class
            }

            // Handle reset button click
            if (resetButton) {
                resetButton.addEventListener('click', function() {
                    input.value = ''; // Reset the input field
                    preview.innerHTML = ''; // Clear the icon preview
                    results.innerHTML = ''; // Clear any search results
                    results.style.display = 'none'; // Hide results
                    
                    input.classList.remove('has-icon'); // Remove the 'has-icon' class

                    if (control.setting.get() !== '') {
                        control.setting.set(''); // Reset the setting in the Customizer
                    }
                });
            }

            // Position the results box on input focus
            input.addEventListener('focus', positionResultsBox);

            // Position the results box on window resize to ensure it stays aligned
            window.addEventListener('resize', positionResultsBox);
        }
    });
})(wp);
