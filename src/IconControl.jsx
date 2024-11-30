import './IconControl.css';

const { __ } = wp.i18n;
const { TextControl } = wp.components;
const { useState } = wp.element;

const COMPONENT_SLUG = 'icon-control';

// IconAutoSuggest component for icon search.
const IconAutoSuggest = ({ value, onChange }) => {
    const [searchResults, setSearchResults] = useState([]);

    const handleSearch = (search) => {
        if (!search) {
            setSearchResults([]);
            return;
        }

        fetch(`${window.location.origin}/wp-admin/admin-ajax.php?action=agnosticon_search&search=${encodeURIComponent(search)}`)
            .then((response) => response.json())
            .then((response) => {
                if (response.success) {
                    setSearchResults(response.data);
                }
            })
            .catch((error) => {
                console.error('AJAX error:', error);
            });
    };

    const handleSelect = (iconData) => {
      console.log('SELECT iconData:', iconData);
        onChange(iconData.id);
        setSearchResults([]);
    };

    return (
      <div className={`${COMPONENT_SLUG}__wrapper`}>
        <TextControl
          label={__("Icon", "agnosticon")}
          value={value}
          onChange={(newValue) => {
            console.log('newValue:', newValue);
              onChange(newValue);
              handleSearch(newValue);
          }}
          placeholder={__("Search for an icon...", "menu-plus")}
          autoComplete="off"
          className={`${COMPONENT_SLUG}__input`}
        />
        {searchResults.length > 0 && (
          <div className={`${COMPONENT_SLUG}__results`}>
            <ul className={`${COMPONENT_SLUG}__results-list`}>
                {searchResults.map((icon) => {
                  const code = String.fromCodePoint(`0x${icon.char}`);
                  const html = `<i
                    data-agnosticon-char="${code}"
                    class="${icon.class}"
                    style="${icon.style}"
                  > </i>`;
                  
                  return (
                    <li 
                        key={icon.id} 
                        className={`${COMPONENT_SLUG}__results-list-item`}
                        onClick={() => handleSelect(icon)}
                    >
                      <label dangerouslySetInnerHTML={{ __html: html }}></label> {icon.name}
                    </li>
                  )
                })}
            </ul>
          </div>
        )}
      </div>
    );
};

// Exporting IconControl as the main component for use in other parts of the application
const IconControl = ({ label, value, onChange }) => (
    <IconAutoSuggest
        value={value}
        onChange={onChange}
    />
);

export default IconControl;
