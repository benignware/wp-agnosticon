import './IconControl.css';

const { __ } = wp.i18n;
const { TextControl, Popover } = wp.components;
const { useState, useRef } = wp.element;

const COMPONENT_SLUG = 'icon-control';
const { ajaxurl } = window;

const BASE_URL = `${ajaxurl}/wp-admin/admin-ajax.php?action=agnosticon_search`;

const IconAutoSuggest = ({ value, onChange }) => {
    const [searchResults, setSearchResults] = useState([]);
    const [isPopoverVisible, setIsPopoverVisible] = useState(false);
    const inputRef = useRef(null);

    const handleSearch = (search) => {
        if (!search) {
            setSearchResults([]);
            setIsPopoverVisible(false);
            return;
        }

        const url = `${BASE_URL}&search=${encodeURIComponent(search)}`;

        fetch(url)
            .then((response) => response.json())
            .then((response) => {
                if (response.success) {
                    setSearchResults(response.data);
                    setIsPopoverVisible(response.data.length > 0);
                }
            })
            .catch((error) => {
                console.error('AJAX error:', error);
            });
    };

    const handleSelect = (iconData) => {
        onChange(iconData.id);
        setSearchResults([]);
        setIsPopoverVisible(false);
    };

    return (
        <div className={`${COMPONENT_SLUG}__wrapper`}>
            <TextControl
                ref={inputRef}
                label={__("Icon", "agnosticon")}
                value={value}
                onChange={(newValue) => {
                    onChange(newValue);
                    handleSearch(newValue);
                }}
                placeholder={__("Search for an icon...", "menu-plus")}
                autoComplete="off"
                className={`${COMPONENT_SLUG}__input`}
            />
            {isPopoverVisible && (
                <Popover
                    anchorRef={inputRef?.current?.inputRef}
                    onClose={() => setIsPopoverVisible(false)}
                    className={`${COMPONENT_SLUG}__popover`}
                >
                    <ul className={`${COMPONENT_SLUG}__results-list`}>
                        {searchResults.map((iconData) => {
                            try {
                              iconData.code = String.fromCodePoint(`0x${iconData?.char}`);
                            } catch(e) {
                              iconData.code = iconData?.char;
                            }

                            const iconHtml = `<i
                              style="${iconData.style}"
                              data-agnosticon-code="${iconData.code}"
                            ></i>`;

                            return (
                                <li
                                    key={iconData.id}
                                    className={`${COMPONENT_SLUG}__results-list-item`}
                                    onClick={() => handleSelect(iconData)}
                                >
                                    <label
                                        dangerouslySetInnerHTML={{
                                            __html: iconHtml,
                                        }}
                                    ></label>
                                    {iconData.name}
                                </li>
                            );
                        })}
                    </ul>
                </Popover>
            )}
        </div>
    );
};

const IconControl = ({ label, value, onChange }) => (
    <IconAutoSuggest value={value} onChange={onChange} />
);

export default IconControl;
