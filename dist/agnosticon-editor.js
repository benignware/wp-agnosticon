(function () {
    'use strict';

    const {
      __
    } = wp.i18n;
    const {
      TextControl,
      PanelBody
    } = wp.components;
    const {
      Fragment,
      useState
    } = wp.element;
    const {
      addFilter
    } = wp.hooks;
    const {
      InspectorControls
    } = wp.blockEditor;

    // Step 1: Add Icon Attribute to List Block
    const addListIconAttribute = settings => {
      if (settings.name !== 'core/list') {
        return settings;
      }
      return {
        ...settings,
        attributes: {
          ...settings.attributes,
          icon: {
            type: 'object',
            default: {}
          }
        }
      };
    };
    addFilter('blocks.registerBlockType', 'benignware/list/icon-attribute', addListIconAttribute);

    // Step 2: Create Icon Autosuggest Component
    const IconAutoSuggest = ({
      value,
      onChange
    }) => {
      const [searchResults, setSearchResults] = useState([]);
      const handleSearch = search => {
        if (!search) {
          setSearchResults([]);
          return;
        }
        fetch(`${window.location.origin}/wp-admin/admin-ajax.php?action=agnosticon_search&search=${encodeURIComponent(search)}`).then(response => response.json()).then(response => {
          if (response.success) {
            setSearchResults(response.data);
          }
        }).catch(error => {
          console.error('AJAX error:', error);
        });
      };
      const handleSelect = iconData => {
        onChange(iconData);
        setSearchResults([]);
      };
      return /*#__PURE__*/React.createElement(Fragment, null, /*#__PURE__*/React.createElement(TextControl, {
        label: __("Icon", "benignware"),
        value: value ? value.name : '',
        onChange: newValue => {
          onChange(newValue);
          handleSearch(newValue);
        },
        placeholder: __("Search for an icon...", "benignware")
      }), searchResults.length > 0 && /*#__PURE__*/React.createElement("ul", {
        className: "components-autocomplete__results"
      }, searchResults.map(icon => /*#__PURE__*/React.createElement("li", {
        key: icon.id,
        className: "components-autocomplete__result",
        onClick: () => handleSelect(icon)
      }, /*#__PURE__*/React.createElement("span", {
        className: icon.class,
        style: {
          marginRight: '10px'
        }
      }), " ", icon.name))));
    };

    // Step 3: Add Inspector Controls to List Block
    const withListInspectorControls = BlockEdit => {
      return props => {
        if (props.name !== 'core/list') {
          return /*#__PURE__*/React.createElement(BlockEdit, props);
        }
        const {
          attributes,
          setAttributes
        } = props;
        const {
          icon
        } = attributes;
        return /*#__PURE__*/React.createElement(Fragment, null, /*#__PURE__*/React.createElement(InspectorControls, null, /*#__PURE__*/React.createElement(PanelBody, {
          title: __("Icon Settings", "benignware"),
          initialOpen: true
        }, /*#__PURE__*/React.createElement(IconAutoSuggest, {
          value: icon,
          onChange: newIcon => setAttributes({
            icon: newIcon
          })
        }))), icon && icon.entity && /*#__PURE__*/React.createElement("i", {
          style: {
            marginRight: 'calc(var(--wp--style--block-gap, 0.5em) * -1 + 0.5em)',
            fontStyle: 'normal'
          },
          dangerouslySetInnerHTML: {
            __html: icon.entity
          }
        }), /*#__PURE__*/React.createElement(BlockEdit, props));
      };
    };
    addFilter('editor.BlockEdit', 'benignware/list/with-inspector-controls', withListInspectorControls);

})();
