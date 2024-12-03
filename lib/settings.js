(function () {
  const settingsWrap = document.querySelector('.agnosticon-settings');
  const searchInput = document.getElementById('agnosticon-settings-icon-search');
  const resultsContainer = document.getElementById('agnosticon-settings-results');
  const spinner = settingsWrap.querySelector('.agnosticon-settings-spinner');
  const countMessage = settingsWrap.querySelector('.agnosticon-settings-result-count');

  function formatIconSetTitle(fontFamily) {
    if (!fontFamily) return '';

    // Trim quotes and remove extra spaces
    fontFamily = fontFamily.replace(/^["']|["']$/g, '').trim();

    // Replace hyphens and underscores with spaces, and insert spaces before uppercase letters for camel case
    return fontFamily
      .replace(/[-_]/g, ' ') // Replace hyphens and underscores with spaces
      .replace(/([a-z])([A-Z])/g, '$1 $2') // Insert space before uppercase letters
      .split(' ') // Split into words
      .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()) // Capitalize each word
      .join(' '); // Join back into a string
  }

  // Debounce function to limit the rate of invoking the fetchIcons function
  function debounce(func, delay) {
    let timeout;
    return function (...args) {
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(this, args), delay);
    };
  }

  class IconPopover {
    constructor() {
      this.activePopover = null; // Reference to the active popover
      this.activeIconBox = null; // Reference to the active icon box

      // Check if spinner is found
      if (!spinner) {
        console.error('Spinner element not found!');
        return; // Early return if spinner is not found
      }

      // Bind event handlers
      this.handleClickOutside = this.handleClickOutside.bind(this);
      this.positionPopover = this.positionPopover.bind(this);
      this.handleResize = this.handleResize.bind(this);

      // Initial AJAX fetch
      this.fetchIcons('');

      // Debounced trigger search on input
      const debouncedSearch = debounce((value) => {
        this.fetchIcons(value);
      }, 300); // Adjust the delay as necessary

      searchInput.addEventListener('input', (event) => {
        debouncedSearch(event.target.value);
      });
    }

    // Function to fetch icons via AJAX
    fetchIcons(search) {
      const url = `${AgnosticonSettings.ajaxUrl}?action=agnosticon_search&search=${encodeURIComponent(search)}`;
      settingsWrap.classList.add('loading'); // Show spinner by adding loading class

      fetch(url)
        .then(response => response.json())
        .then(data => {
          settingsWrap.classList.remove('loading'); // Hide spinner by removing loading class
          if (data.success) {
            this.displayIcons(data.data);
          } else {
            this.displayNoResults();
          }
        })
        .catch(() => {
          settingsWrap.classList.remove('loading'); // Hide spinner on error
          this.displayNoResults(); // Display no results on error
        });
    }

    // Function to display icons grouped by font family
    displayIcons(icons) {
      resultsContainer.innerHTML = ''; // Clear current results

      const iconsByFontFamily = {};
      let totalResults = 0;

      Object.values(icons).forEach(icon => {
        const fontFamily = icon.font_family.replace(/^["']|["']$/g, '') || 'default';
        if (!iconsByFontFamily[fontFamily]) {
          iconsByFontFamily[fontFamily] = [];
        }
        iconsByFontFamily[fontFamily].push(icon);
        totalResults++; // Count total results
      });

      // Render each font family group
      Object.keys(iconsByFontFamily).forEach(fontFamily => {
        const fontFamilyContainer = document.createElement('div');
        fontFamilyContainer.className = 'agnosticon-settings-icon-font-group';

        const formattedFontFamily = formatIconSetTitle(fontFamily);

        const fontFamilyHeading = document.createElement('h2');
        fontFamilyHeading.textContent = formattedFontFamily;
        fontFamilyContainer.appendChild(fontFamilyHeading);

        const iconList = document.createElement('div');
        iconList.className = 'agnosticon-settings-icon-list';

        iconsByFontFamily[fontFamily].forEach(icon => {
          const iconElement = document.createElement('div');
          iconElement.className = 'agnosticon-settings-icon-item';

          // Create a box for the icon with a click event for the popover
          const iconBox = document.createElement('div');
          iconBox.className = 'agnosticon-settings-icon-box';
          iconBox.innerHTML = `<i style="${icon.style}">${icon.entity}</i>`;

          // Add click event for the icon box
          iconBox.addEventListener('click', (event) => {
            this.showPopover(icon, iconBox);
          });

          // Append the icon box and name
          iconElement.appendChild(iconBox);
          const iconName = document.createElement('span');
          iconName.textContent = icon.name;
          iconElement.appendChild(iconName);
          iconList.appendChild(iconElement);
        });

        fontFamilyContainer.appendChild(iconList);
        resultsContainer.appendChild(fontFamilyContainer);
      });

      // Show total results if any
      if (totalResults > 0) {
        this.displayResultsCount(totalResults);
      } else {
        this.displayNoResults();
      }
    }

    // Function to display number of results
    displayResultsCount(count) {
      countMessage.textContent = `Total Results: ${count}`;
    }

    // Function to display no results message
    displayNoResults() {
      countMessage.textContent = 'No results found.';
    }

    // Function to show the popover
    showPopover(icon, iconBox) {
      // Remove existing popover if any
      if (this.activePopover) {
        this.activePopover.remove();
      }

      const formattedFontFamily = formatIconSetTitle(icon.font_family);

      // Create the new popover
      const popover = document.createElement('div');
      popover.className = 'popover';
      popover.innerHTML = `
          <div class="popover-content">
              <div class="agnosticon-popover-header">
                  <i class="agnosticon-popover-icon" style="${icon.style};">${icon.entity}</i>
                  <div class="agnosticon-popover-description">
                      <div class="agnosticon-popover-title"><strong>${icon.name}</strong></div>
                      <div><small>${formattedFontFamily}</small></div>
                  </div>
              </div>
              <div style="position: relative; margin-top: 10px;">
                  <button class="agnosticon-settings-copy-icon-button">
                      <span class="dashicons dashicons-clipboard"></span>
                  </button>
                  <div class="agnosticon-settings-icon-markup">
                      <code class="agnosticon-code">&lt;i class="${icon.class}"&gt;&lt;/i&gt;</code>
                  </div>
              </div>
          </div>
      `;

      document.body.appendChild(popover);

      // Set active popover and icon box
      this.activePopover = popover;
      this.activeIconBox = iconBox;

      // Position the popover
      this.positionPopover();

      // Setup event listeners for resizing and clicking outside
      window.addEventListener('resize', this.handleResize);
      document.addEventListener('click', this.handleClickOutside);

      // Copy to clipboard function
      popover.querySelector('.agnosticon-settings-copy-icon-button').addEventListener('click', () => {
        const htmlToCopy = `<i class="${icon.class}"></i>`;
        navigator.clipboard.writeText(htmlToCopy).then(() => {
          // Optional: alert('Icon HTML copied to clipboard!');
        });
      });
    }

    // Remove popover when clicking outside
    handleClickOutside(event) {
      if (!this.activeIconBox.contains(event.target) && !this.activePopover.contains(event.target)) {
        this.activePopover.remove();
        this.activePopover = null; // Clear active popover reference
        this.activeIconBox = null; // Clear active icon box reference
        document.removeEventListener('click', this.handleClickOutside);
        window.removeEventListener('resize', this.handleResize); // Clean up resize listener
      }
    }

    // Function to position the popover centered above the icon box
    positionPopover() {
      if (this.activePopover && this.activeIconBox) {
        const rect = this.activeIconBox.getBoundingClientRect();
        this.activePopover.style.position = 'absolute';
        this.activePopover.style.top = `${rect.top + window.scrollY - this.activePopover.offsetHeight - 10}px`; // Position it above the icon box
        this.activePopover.style.left = `${rect.left + window.scrollX + (this.activeIconBox.offsetWidth / 2) - (this.activePopover.offsetWidth / 2)}px`; // Center it horizontally
      }
    }

    // Handle window resize to reposition popover
    handleResize() {
      this.positionPopover();
    }
  }

  // Initialize the IconPopover class
  new IconPopover();
})();


// Clear the caache
document.addEventListener('DOMContentLoaded', function () {
  const clearCacheButton = document.getElementById('agnosticon-clear-cache');

  if (clearCacheButton) {
      clearCacheButton.addEventListener('click', function () {
          clearCacheButton.textContent = 'Clearing...';

          fetch(`${AgnosticonSettings.ajaxUrl}?action=_agnosticon_clear`, {
              method: 'GET',
              credentials: 'same-origin',
          })
              .then(response => {
                  if (!response.ok) {
                      throw new Error('Failed to clear cache');
                  }
                  return response.json();
              })
              .then(data => {
                  if (data.success) {
                      // alert('Cache cleared successfully.');
                      location.reload();
                  } else {
                      alert('Failed to clear cache.');
                  }
              })
              .catch(error => {
                  alert('Error: ' + error.message);
              })
              .finally(() => {
                  clearCacheButton.textContent = 'Clear Cache';
              });
      });
  }
});
