(function () {
  'use strict';

  class Agnosticon {
    #data = {};
    constructor() {
      if (Agnosticon.instance) {
        return Agnosticon.instance;
      }
      this.#data = globalThis.AgnosticonData || {};
      Agnosticon.instance = this;
    }
    get icons() {
      return this.#data.icons || {};
    }

    /**
     * Fetch icons based on a query.
     * @param {string|null} query - The search query for icons.
     * @returns {object|null} - Best matching icon or all icons if no query is provided.
     */
    getIcons(query = '') {
      console.log('*** query:', query);
      if (!query) return Object.values(this.icons);
      if (query in this.icons) {
        return [this.icons[query]];
      }
      const [, prefix = ''] = query.match(/^([a-z_-]+)\:/i)?.[0] || [];
      const rest = query.slice(prefix.length);
      const [name = '', variant = ''] = rest.split(/[:]]+/);
      console.log('*** prefix:', prefix);
      console.log('*** rest:', rest);
      console.log('*** name:', name);
      console.log('*** variant:', variant);
      const queryTokens = name.toLowerCase().split(/[:-_\s]+/); // Split query into tokens

      const scoredIcons = Object.values(this.icons).map(icon => {
        const iconTokens = icon.name.toLowerCase().split(/[-_\s]+/);

        // Calculate relevance score
        let score = 0;
        queryTokens.forEach(qToken => {
          const matchIndex = iconTokens.indexOf(qToken);
          if (matchIndex !== -1) {
            score += 1; // Exact token match
          } else {
            // Partial match score, add based on relevance
            score += iconTokens.some(iToken => iToken.startsWith(qToken) || iToken.includes(qToken)) ? 0.5 : 0;
          }
        });
        return {
          icon,
          score
        };
      });

      // Sort by relevance (higher score first) and return the first match
      const bestMatches = scoredIcons.sort((a, b) => b.score - a.score).filter(match => match.score > 0).map(match => match.icon);
      return bestMatches[0];
    }

    /**
     * Get metadata for a specific icon based on query.
     * @param {string} query - The search query for an icon.
     * @returns {object} - The metadata of the matching icon.
     */
    getIconMeta(query) {
      const icon = this.getIcons(query);
      return icon || {};
    }
    search(query = '') {
      return this.getIcons(query);
    }
    find(query = '') {
      const result = this.getIcons(query)[0];
      console.log('find result:', result);
      return result;
    }
  }

  // Ensure a single global instance
  const instance = new Agnosticon();
  // Object.freeze(instance);

  globalThis.agnosticon = instance;

  function _extends() {
    return _extends = Object.assign ? Object.assign.bind() : function (n) {
      for (var e = 1; e < arguments.length; e++) {
        var t = arguments[e];
        for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]);
      }
      return n;
    }, _extends.apply(null, arguments);
  }

  // Regexps involved with splitting words in various case formats.
  const SPLIT_LOWER_UPPER_RE = /([\p{Ll}\d])(\p{Lu})/gu;
  const SPLIT_UPPER_UPPER_RE = /(\p{Lu})([\p{Lu}][\p{Ll}])/gu;
  // Used to iterate over the initial split result and separate numbers.
  const SPLIT_SEPARATE_NUMBER_RE = /(\d)\p{Ll}|(\p{L})\d/u;
  // Regexp involved with stripping non-word characters from the result.
  const DEFAULT_STRIP_REGEXP = /[^\p{L}\d]+/giu;
  // The replacement value for splits.
  const SPLIT_REPLACE_VALUE = "$1\0$2";
  // The default characters to keep after transforming case.
  const DEFAULT_PREFIX_SUFFIX_CHARACTERS = "";
  /**
   * Split any cased input strings into an array of words.
   */
  function split(value) {
      let result = value.trim();
      result = result
          .replace(SPLIT_LOWER_UPPER_RE, SPLIT_REPLACE_VALUE)
          .replace(SPLIT_UPPER_UPPER_RE, SPLIT_REPLACE_VALUE);
      result = result.replace(DEFAULT_STRIP_REGEXP, "\0");
      let start = 0;
      let end = result.length;
      // Trim the delimiter from around the output string.
      while (result.charAt(start) === "\0")
          start++;
      if (start === end)
          return [];
      while (result.charAt(end - 1) === "\0")
          end--;
      return result.slice(start, end).split(/\0/g);
  }
  /**
   * Split the input string into an array of words, separating numbers.
   */
  function splitSeparateNumbers(value) {
      const words = split(value);
      for (let i = 0; i < words.length; i++) {
          const word = words[i];
          const match = SPLIT_SEPARATE_NUMBER_RE.exec(word);
          if (match) {
              const offset = match.index + (match[1] ?? match[2]).length;
              words.splice(i, 1, word.slice(0, offset), word.slice(offset));
          }
      }
      return words;
  }
  /**
   * Convert a string to camel case (`fooBar`).
   */
  function camelCase(input, options) {
      const [prefix, words, suffix] = splitPrefixSuffix(input, options);
      const lower = lowerFactory(options?.locale);
      const upper = upperFactory(options?.locale);
      const transform = pascalCaseTransformFactory(lower, upper);
      return (prefix +
          words
              .map((word, index) => {
              if (index === 0)
                  return lower(word);
              return transform(word, index);
          })
              .join("") +
          suffix);
  }
  function lowerFactory(locale) {
      return (input) => input.toLocaleLowerCase(locale);
  }
  function upperFactory(locale) {
      return (input) => input.toLocaleUpperCase(locale);
  }
  function pascalCaseTransformFactory(lower, upper) {
      return (word, index) => {
          const char0 = word[0];
          const initial = index > 0 && char0 >= "0" && char0 <= "9" ? "_" + char0 : upper(char0);
          return initial + lower(word.slice(1));
      };
  }
  function splitPrefixSuffix(input, options = {}) {
      const splitFn = options.split ?? (options.separateNumbers ? splitSeparateNumbers : split);
      const prefixCharacters = options.prefixCharacters ?? DEFAULT_PREFIX_SUFFIX_CHARACTERS;
      const suffixCharacters = options.suffixCharacters ?? DEFAULT_PREFIX_SUFFIX_CHARACTERS;
      let prefixIndex = 0;
      let suffixIndex = input.length;
      while (prefixIndex < input.length) {
          const char = input.charAt(prefixIndex);
          if (!prefixCharacters.includes(char))
              break;
          prefixIndex++;
      }
      while (suffixIndex > prefixIndex) {
          const index = suffixIndex - 1;
          const char = input.charAt(index);
          if (!suffixCharacters.includes(char))
              break;
          suffixIndex = index;
      }
      return [
          input.slice(0, prefixIndex),
          splitFn(input.slice(prefixIndex, suffixIndex)),
          input.slice(suffixIndex),
      ];
  }

  const {
    __: __$1
  } = wp.i18n;
  const {
    TextControl: TextControl$1,
    Popover
  } = wp.components;
  const {
    useState,
    useRef
  } = wp.element;
  const COMPONENT_SLUG = 'icon-control';
  const IconAutoSuggest = ({
    value,
    onChange
  }) => {
    const [searchResults, setSearchResults] = useState([]);
    const [isPopoverVisible, setIsPopoverVisible] = useState(false);
    const inputRef = useRef(null);
    const handleSearch = search => {
      if (!search) {
        setSearchResults([]);
        setIsPopoverVisible(false);
        return;
      }
      fetch(`${window.location.origin}/wp-admin/admin-ajax.php?action=agnosticon_search&search=${encodeURIComponent(search)}`).then(response => response.json()).then(response => {
        if (response.success) {
          setSearchResults(response.data);
          setIsPopoverVisible(response.data.length > 0);
        }
      }).catch(error => {
        console.error('AJAX error:', error);
      });
    };
    const handleSelect = iconData => {
      onChange(iconData.id);
      setSearchResults([]);
      setIsPopoverVisible(false);
    };
    return /*#__PURE__*/React.createElement("div", {
      className: `${COMPONENT_SLUG}__wrapper`
    }, /*#__PURE__*/React.createElement(TextControl$1, {
      ref: inputRef,
      label: __$1("Icon", "agnosticon"),
      value: value,
      onChange: newValue => {
        onChange(newValue);
        handleSearch(newValue);
      },
      placeholder: __$1("Search for an icon...", "menu-plus"),
      autoComplete: "off",
      className: `${COMPONENT_SLUG}__input`
    }), isPopoverVisible && /*#__PURE__*/React.createElement(Popover, {
      anchorRef: inputRef?.current?.inputRef,
      onClose: () => setIsPopoverVisible(false),
      className: `${COMPONENT_SLUG}__popover`
    }, /*#__PURE__*/React.createElement("ul", {
      className: `${COMPONENT_SLUG}__results-list`
    }, searchResults.map(iconData => {
      try {
        iconData.code = String.fromCodePoint(`0x${iconData?.char}`);
      } catch (e) {
        iconData.code = iconData?.char;
      }
      const iconHtml = `<i
                              style="${iconData.style}"
                              data-agnosticon-code="${iconData.code}"
                            ></i>`;
      return /*#__PURE__*/React.createElement("li", {
        key: iconData.id,
        className: `${COMPONENT_SLUG}__results-list-item`,
        onClick: () => handleSelect(iconData)
      }, /*#__PURE__*/React.createElement("label", {
        dangerouslySetInnerHTML: {
          __html: iconHtml
        }
      }), iconData.name);
    }))));
  };
  const IconControl = ({
    label,
    value,
    onChange
  }) => /*#__PURE__*/React.createElement(IconAutoSuggest, {
    value: value,
    onChange: onChange
  });

  const {
    __
  } = wp.i18n;
  const {
    TextControl,
    PanelBody,
    RangeControl,
    __experimentalBoxControl: BoxControl,
    __experimentalUnitControl: UnitControl,
    __experimentalBorderBoxControl: BorderBoxControl,
    __experimentalToolsPanel: ToolsPanel,
    __experimentalToolsPanelItem: ToolsPanelItem,
    FontSizePicker
  } = wp.components;
  const {
    Fragment
  } = wp.element;
  const {
    addFilter
  } = wp.hooks;
  const {
    InspectorControls,
    __experimentalBorderRadiusControl: BorderRadiusControl
  } = wp.blockEditor;
  const {
    createHigherOrderComponent
  } = wp.compose;
  const iconDefaults = {
    size: 16,
    color: '',
    backgroundColor: '',
    borderColor: '',
    // borderRadius: {
    //   topLeft: "50%",
    //   topRight: "50%",
    //   bottomRight: "50%",
    //   bottomleft: "50%",
    // },
    padding: {
      top: "0px",
      right: "0px",
      bottom: "0px",
      left: "0px"
    },
    gap: {
      bottom: "0px"
    }
  };

  // Step 1: Add Custom Attributes to List Block
  const addListCustomAttributes = settings => {
    if (settings.name !== 'core/list') {
      return settings;
    }
    return {
      ...settings,
      attributes: {
        ...settings.attributes,
        icon: {
          type: 'object',
          default: iconDefaults
        }
      }
    };
  };
  addFilter('blocks.registerBlockType', 'benignware/list/custom-attributes', addListCustomAttributes);

  // Step 2: Add Controls in the Inspector Panel
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
        icon = {}
      } = attributes;
      const resetAllStyles = () => {
        setAttributes({
          icon: {
            ...iconDefaults,
            id: icon.id
          }
        });
      };
      const fontSizes = [{
        name: 'Small',
        size: 12,
        slug: 'small'
      }, {
        name: 'Normal',
        size: 16,
        slug: 'normal'
      }, {
        name: 'Big',
        size: 26,
        slug: 'big'
      }];
      return /*#__PURE__*/React.createElement(Fragment, null, /*#__PURE__*/React.createElement(InspectorControls, null, /*#__PURE__*/React.createElement(PanelBody, {
        title: __("Icon", "agnosticon"),
        initialOpen: true
      }, /*#__PURE__*/React.createElement(IconControl, {
        value: icon.id,
        onChange: newIconId => setAttributes({
          icon: {
            ...icon,
            id: newIconId
          }
        })
      }))), /*#__PURE__*/React.createElement(InspectorControls, {
        group: "styles"
      }, /*#__PURE__*/React.createElement(ToolsPanel, {
        label: __('Icon', 'agnosticon'),
        resetAll: resetAllStyles
      }, /*#__PURE__*/React.createElement(ToolsPanelItem, {
        hasValue: () => !!icon.size,
        label: __("Icon Size", "agnosticon"),
        onDeselect: () => setAttributes({
          icon: {
            ...icon,
            size: iconDefaults.size
          }
        })
      }, /*#__PURE__*/React.createElement(FontSizePicker, {
        __next40pxDefaultSize: true,
        fallbackFontSize: 16,
        value: icon.size,
        fontSizes: fontSizes,
        onChange: newSize => setAttributes({
          icon: {
            ...icon,
            size: newSize
          }
        }),
        withReset: false,
        withSlider: true
      })), /*#__PURE__*/React.createElement(ToolsPanelItem, {
        hasValue: () => !!icon.color,
        label: __("Icon Color", "agnosticon"),
        onDeselect: () => setAttributes({
          icon: {
            ...icon,
            color: iconDefaults.color
          }
        })
      }, /*#__PURE__*/React.createElement(TextControl, {
        label: __("Color", "agnosticon"),
        type: "color",
        value: icon.color,
        onChange: newColor => setAttributes({
          icon: {
            ...icon,
            color: newColor
          }
        })
      })), /*#__PURE__*/React.createElement(ToolsPanelItem, {
        hasValue: () => !!icon.backgroundColor,
        label: __("Icon Background Color", "agnosticon"),
        onDeselect: () => setAttributes({
          icon: {
            ...icon,
            backgroundColor: iconDefaults.backgroundColor
          }
        })
      }, /*#__PURE__*/React.createElement(TextControl, {
        label: __("Background Color", "agnosticon"),
        type: "color",
        value: icon.backgroundColor,
        onChange: newBgColor => setAttributes({
          icon: {
            ...icon,
            backgroundColor: newBgColor
          }
        })
      })), /*#__PURE__*/React.createElement(ToolsPanelItem, {
        hasValue: () => Object.values(icon.padding).some(v => parseFloat(v) > 0),
        label: __("Icon Padding", "agnosticon"),
        onDeselect: () => setAttributes({
          icon: {
            ...icon,
            padding: iconDefaults.padding
          }
        })
      }, /*#__PURE__*/React.createElement(BoxControl, {
        label: __("Padding", "agnosticon"),
        units: [{
          value: "px",
          label: "px"
        }],
        values: icon.padding,
        sides: ["horizontal"],
        onChange: newValue => setAttributes({
          icon: {
            ...icon,
            padding: newValue
          }
        }),
        allowReset: true
      })), /*#__PURE__*/React.createElement(ToolsPanelItem, {
        hasValue: () => Object.entries(icon.gap).some(([key, value]) => parseFloat(value) > 0),
        label: __("Icon Gap", "agnosticon"),
        onDeselect: () => setAttributes({
          icon: {
            ...icon,
            gap: iconDefaults.gap
          }
        })
      }, /*#__PURE__*/React.createElement(BoxControl, {
        label: __("Gap", "agnosticon"),
        values: icon.gap,
        sides: ["bottom"],
        splitOnAxis: true,
        onChange: newValue => setAttributes({
          icon: {
            ...icon,
            gap: newValue
          }
        }),
        allowReset: true
      })))), /*#__PURE__*/React.createElement(BlockEdit, props));
    };
  };
  addFilter('editor.BlockEdit', 'benignware/list/with-inspector-controls', withListInspectorControls);

  // Define the type-to-attribute mapping
  const renderTypeCriteria = {
    grid: ['size', 'borderColor', 'backgroundColor', 'padding', 'gap']
  };

  // Utility function to determine the type based on criteria
  const determineRenderType = (icon, criteria) => {
    return Object.keys(criteria).find(type => {
      const attributes = criteria[type];
      return attributes.some(attr => {
        const value = icon[attr];
        return value && (typeof value !== 'object' || Object.values(value).some(v => parseFloat(v) > 0));
      });
    }) || 'default'; // Default type if none match
  };

  // Step 3: Add Inline Styles to List Wrapper
  const withIconListWrapperProps = createHigherOrderComponent(BlockListBlock => {
    return props => {
      if (props.name !== 'core/list') {
        return /*#__PURE__*/React.createElement(BlockListBlock, props);
      }
      const {
        attributes
      } = props;
      const {
        icon = {}
      } = attributes;

      // const gridProps = ['borderColor', 'backgroundColor', 'padding', 'gap'];
      // const isGrid = Object.entries(icon)
      //   .filter(([key, value]) => value && gridProps.includes(key))
      //   .some(([key, value]) => typeof value !== 'object' || Object.values(value).some(v => parseFloat(v) > 0));

      // const className = 'agnosticon-list' + (isGrid ? ' is-grid' : '');
      // console.log('isAdvanced:', isAdvanced);

      // Determine the type and generate the class name
      const renderType = determineRenderType(icon, renderTypeCriteria);
      const className = `agnosticon-list is-${renderType}`;
      let iconData = icon.id ? window.agnosticon.find(icon.id) : null;
      if (iconData) {
        iconData = Object.fromEntries(Object.entries(iconData).map(([key, value]) => [camelCase(key), value]));
        try {
          iconData.code = String.fromCodePoint(`0x${iconData?.char}`);
        } catch (e) {
          iconData.code = iconData?.char;
        }
      }
      const data = {
        ...iconData,
        ...icon
      };
      const wrapperProps = {
        ...props.wrapperProps,
        className,
        style: {
          '--agnosticon-char': data.char || '',
          '--agnosticon-code': data.code ? `'${data.code}'` : '',
          '--agnosticon-font-family': data.fontFamily,
          '--agnosticon-font-weight': data.fontWeight,
          '--agnosticon-font-size': `${data.size}px`,
          '--agnosticon-color': data.color,
          '--agnosticon-background-color': data.backgroundColor,
          '--agnosticon-border-color': data.borderColor,
          // '--agnosticon-border-radius': `${data.borderRadius}`,
          '--agnosticon-padding': `${data.padding?.left || '0px'}`,
          '--agnosticon-gap': `${data.gap?.bottom || '0px'}`
        }
      };
      return /*#__PURE__*/React.createElement(BlockListBlock, _extends({}, props, {
        wrapperProps: wrapperProps
      }));
    };
  }, 'withIconListWrapperProps');
  addFilter('editor.BlockListBlock', 'benignware/list-wrapper', withIconListWrapperProps);

  (function (richText, blockEditor, element, components) {

    const formatName = 'agnosticon/inline-icon';
    const attributeName = 'data-agnosticon-id';
    const {
      useState,
      useEffect
    } = element;
    const {
      Popover,
      Button,
      TextControl,
      __experimentalVStack: VStack,
      __experimentalHStack: HStack
    } = components;
    const {
      RichTextToolbarButton
    } = blockEditor;

    // Register the custom format type
    richText.registerFormatType(formatName, {
      title: 'Inline Icon',
      // object: true,
      tagName: 'span',
      className: 'agnosticon-icon',
      // A class name for styling
      attributes: {
        // 'data-agnosticon-query': '',
        'data-agnosticon-id': '',
        'data-agnosticon-char': '',
        style: '',
        class: ''
        // role: 'img',
        // title: '',
      },
      edit({
        value,
        onChange,
        isObjectActive,
        contentRef,
        //
        isActive
      }) {
        const activeObject = value && isObjectActive ? richText.getActiveObject(value) : null;
        const activeAttributes = {
          ...activeObject?.attributes,
          ...activeObject?.unregisteredAttributes
        };
        const activeValue = activeAttributes[attributeName] || '';
        const [isOpen, setOpen] = useState(isObjectActive);
        const [name, setName] = useState(activeValue);

        // Effect to set the custom name if the format is active
        useEffect(() => {
          if (isObjectActive) {
            setOpen(true);
          } else {
            setOpen(false);
          }
        }, [isObjectActive]);
        useEffect(() => {
          setName(activeValue);
        }, [activeValue]);
        const applyFormat = () => {
          let newValue;
          const iconData = name ? window.agnosticon?.find(name) : null;
          try {
            iconData.code = String.fromCodePoint(`0x${iconData?.char}`);
          } catch (e) {
            iconData.code = iconData?.char;
          }
          const newAttributes = {
            [attributeName]: name,
            'data-agnosticon-char': iconData?.char || '',
            'data-agnosticon-code': iconData?.code || '',
            // 'data-agnosticon-id': icon?.id || '',
            style: iconData?.style || ''
            // class: icon?.class || '',
            // role: 'img',
            // title: icon?.name || name,
          };
          if (!isActive) {
            newValue = richText.insertObject(value, {
              type: formatName,
              attributes: newAttributes
            });
          } else if (name) {
            newValue = richText.applyFormat(value, {
              type: formatName,
              attributes: newAttributes
            });
          } else {
            newValue = richText.remove(value);
          }
          onChange(newValue);
          setOpen(false);
        };
        const removeFormat = () => {
          const newValue = richText.remove(value);
          onChange(newValue);
          setOpen(false);
        };

        // Function to handle opening the popover
        const handleOpenPopover = () => {
          setOpen(true);
        };
        const isPopoverOpen = isOpen;
        const popoverAnchor = richText.useAnchor({
          editableContentElement: contentRef.current,
          settings: {
            title: 'Inline Icon'
          }
        });
        return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement(RichTextToolbarButton, {
          icon: "edit",
          title: "Inline Icon",
          onClick: handleOpenPopover,
          isActive: isObjectActive
        }), isPopoverOpen && /*#__PURE__*/React.createElement(Popover, {
          className: "icon-control__popover",
          placement: "bottom",
          focusOnMount: true,
          anchor: popoverAnchor,
          shift: true,
          noArrow: false,
          offset: 10
        }, /*#__PURE__*/React.createElement(VStack, {
          spacing: 1,
          style: {
            width: '300px',
            padding: '10px'
          }
        }, /*#__PURE__*/React.createElement(IconControl, {
          label: "Icon",
          value: name,
          onChange: newName => setName(newName)
        }), /*#__PURE__*/React.createElement(HStack, null, /*#__PURE__*/React.createElement(Button, {
          isPrimary: true,
          onClick: applyFormat
        }, "Apply"), /*#__PURE__*/React.createElement(Button, {
          isSecondary: true,
          onClick: removeFormat
        }, "Remove")))));
      }
    });
  })(window.wp.richText, window.wp.blockEditor, window.wp.element, window.wp.components);

})();
