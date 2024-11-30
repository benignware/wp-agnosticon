import './IconControl.css';

const { __ } = wp.i18n;
const { TextControl, Popover } = wp.components;
const { useState, useRef } = wp.element;

const COMPONENT_SLUG = 'icon-control';

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

        fetch(`${window.location.origin}/wp-admin/admin-ajax.php?action=agnosticon_search&search=${encodeURIComponent(search)}`)
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
                        {searchResults.map((icon) => {
                            const code = String.fromCodePoint(`0x${icon.char}`);

                            return (
                                <li
                                    key={icon.id}
                                    className={`${COMPONENT_SLUG}__results-list-item`}
                                    onClick={() => handleSelect(icon)}
                                >
                                    <label
                                        dangerouslySetInnerHTML={{
                                            __html: `<i class="${icon.class}" style="${icon.style}">${code}</i>`,
                                        }}
                                    ></label>
                                    {icon.name}
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
