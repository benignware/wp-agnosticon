import { camelCase } from 'change-case';

import './list.css';
import IconControl from "./IconControl.jsx";

const { __ } = wp.i18n;
const {
  TextControl,
  PanelBody,
  RangeControl,
  __experimentalBoxControl: BoxControl,
	__experimentalUnitControl: UnitControl,
	__experimentalBorderBoxControl: BorderBoxControl,
  __experimentalToolsPanel: ToolsPanel,
  __experimentalToolsPanelItem: ToolsPanelItem,
  FontSizePicker,
} = wp.components;
const { Fragment } = wp.element;
const { addFilter } = wp.hooks;
const {
  InspectorControls,
  __experimentalBorderRadiusControl: BorderRadiusControl,
} = wp.blockEditor;
const { createHigherOrderComponent } = wp.compose;

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
    left: "0px",
  },
  gap: {
    bottom: "0px",
  }
};

// Step 1: Add Custom Attributes to List Block
const addListCustomAttributes = (settings) => {
    if (settings.name !== 'core/list') {
        return settings;
    }

    return {
        ...settings,
        attributes: {
            ...settings.attributes,
            icon: {
                type: 'object',
                default: iconDefaults,
            },
        },
    };
};

addFilter(
    'blocks.registerBlockType',
    'benignware/list/custom-attributes',
    addListCustomAttributes
);

// Step 2: Add Controls in the Inspector Panel
const withListInspectorControls = (BlockEdit) => {
    return (props) => {
        if (props.name !== 'core/list') {
            return <BlockEdit {...props} />;
        }

        const { attributes, setAttributes } = props;
        const { icon = {} } = attributes;

        const resetAllStyles = () => {
          setAttributes({
            icon: {
              ...iconDefaults,
              id: icon.id,
            }
          });
        }

        const fontSizes = [
          {
            name: 'Small',
            size: 12,
            slug: 'small'
          },
          {
            name: 'Normal',
            size: 16,
            slug: 'normal'
          },
          {
            name: 'Big',
            size: 26,
            slug: 'big'
          }
        ];

        return (
            <Fragment>
              <InspectorControls>
                <PanelBody title={__("Icon", "agnosticon")} initialOpen={true}>
                  <IconControl
                      value={icon.id}
                      onChange={(newIconId) => setAttributes({ icon: { ...icon, id: newIconId } })}
                  />
                </PanelBody>
            </InspectorControls>
            <InspectorControls group="styles">
              <ToolsPanel label={__('Icon', 'agnosticon')} resetAll={resetAllStyles}>
                <ToolsPanelItem
                  hasValue={() => !!icon.size}
                  label={__("Icon Size", "agnosticon")}
                  onDeselect={() => setAttributes({ icon: { ...icon, size: iconDefaults.size } })}
                >
                  <FontSizePicker
                    __next40pxDefaultSize
                    fallbackFontSize={16}
                    value={icon.size}
                    fontSizes={fontSizes}
                    onChange={(newSize) => setAttributes({ icon: { ...icon, size: newSize } })}
                    withReset={false}
                    withSlider
                  />
                </ToolsPanelItem>
                <ToolsPanelItem
                  hasValue={() => !!icon.color}
                  label={__("Icon Color", "agnosticon")}
                  onDeselect={() => setAttributes({ icon: { ...icon, color: iconDefaults.color } })}
                >
                  <TextControl
                    label={__("Color", "agnosticon")}
                    type="color"
                    value={icon.color}
                    onChange={(newColor) => setAttributes({ icon: { ...icon, color: newColor } })}
                  />
                </ToolsPanelItem>
                <ToolsPanelItem
                  hasValue={() => !!icon.backgroundColor}
                  label={__("Icon Background Color", "agnosticon")}
                  onDeselect={() => setAttributes({ icon: { ...icon, backgroundColor: iconDefaults.backgroundColor } })}
                >
                  <TextControl
                    label={__("Background Color", "agnosticon")}
                    type="color"
                    value={icon.backgroundColor}
                    onChange={(newBgColor) => setAttributes({ icon: { ...icon, backgroundColor: newBgColor } })}
                  />
                </ToolsPanelItem>
                {/* <ToolsPanelItem
                  hasValue={() => !!icon.borderColor}
                  label={__("Icon Border Color", "agnosticon")}
                  onDeselect={() => setAttributes({ icon: { ...icon, borderColor: iconDefaults.borderColor } })}
                >
                  <TextControl
                    label={__("Border Color", "agnosticon")}
                    type="color"
                    value={icon.borderColor}
                    onChange={(newBorderColor) => setAttributes({ icon: { ...icon, borderColor: newBorderColor } })}
                  />
                </ToolsPanelItem> */}
                {/* <ToolsPanelItem
                  hasValue={() => Object.values(icon.borderRadius).some((v) => v !== iconDefaults.borderRadius[v])}
                  label={__("Icon Border Radius", "agnosticon")}
                  onDeselect={() => setAttributes({ icon: { ...icon, borderRadius: iconDefaults.borderRadius } })}
                >
                  <BorderRadiusControl
                    label={__("Border Radius", "agnosticon")}
                    unit="%"
                    units={[
                      { value: "%", label: "%" },
                      { value: "px", label: "px" },
                    ]}
                    value={icon.borderRadius}
                    onChange={(newBorderRadius) => setAttributes({ icon: { ...icon, borderRadius: newBorderRadius } })}
                  />
                </ToolsPanelItem> */}
                <ToolsPanelItem
                  hasValue={() => Object.values(icon.padding).some((v) => parseFloat(v) > 0)}
                  label={__("Icon Padding", "agnosticon")}
                  onDeselect={() =>
                    setAttributes({
                      icon: {
                        ...icon,
                        padding: iconDefaults.padding,
                      },
                    })
                  }
                >
                  <BoxControl
                    label={__("Padding", "agnosticon")}
                    units={[
                      { value: "px", label: "px" },
                    ]}
                    values={icon.padding}
                    sides={["horizontal"]}
                    onChange={(newValue) => setAttributes({ icon: { ...icon, padding: newValue } })}
                    allowReset={true}
                  />
                </ToolsPanelItem>
                <ToolsPanelItem
                  hasValue={() => Object.entries(icon.gap).some(([key, value]) => parseFloat(value) > 0)}
                  label={__("Icon Gap", "agnosticon")}
                  onDeselect={() =>
                    setAttributes({
                      icon: { ...icon, gap: iconDefaults.gap },
                    })
                  }
                >
                  <BoxControl
                    label={__("Gap", "agnosticon")}
                    values={icon.gap}
                    sides={["bottom"]}
                    splitOnAxis={true}
                    onChange={(newValue) => setAttributes({ icon: { ...icon, gap: newValue } })}
                    allowReset={true}
                  />
                </ToolsPanelItem>
              </ToolsPanel>
            </InspectorControls>
            <BlockEdit {...props} />
          </Fragment>
        );
    };
};

addFilter(
    'editor.BlockEdit',
    'benignware/list/with-inspector-controls',
    withListInspectorControls
);

// Define the type-to-attribute mapping
const renderTypeCriteria = {
  grid: ['size', 'borderColor', 'backgroundColor', 'padding', 'gap'],
};


// Utility function to determine the type based on criteria
const determineRenderType = (icon, criteria) => {
  return Object.keys(criteria).find((type) => {
    const attributes = criteria[type];
    return attributes.some((attr) => {
      const value = icon[attr];
      return value && (typeof value !== 'object' || Object.values(value).some(v => parseFloat(v) > 0));
    });
  }) || 'default'; // Default type if none match
};


// Step 3: Add Inline Styles to List Wrapper
const withIconListWrapperProps = createHigherOrderComponent((BlockListBlock) => {
    return (props) => {
        if (props.name !== 'core/list') {
            return <BlockListBlock {...props} />;
        }

        const { attributes } = props;
        const { icon = {} } = attributes;

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
          iconData = Object.fromEntries(Object.entries(iconData).map(([key, value]) => (
            [camelCase(key), value]
          )));
      
          try {
            iconData.code = String.fromCodePoint(`0x${iconData?.char}`);
          } catch(e) {
            iconData.code = iconData?.char;
          }  
        }

        const data = { ...iconData, ...icon };

        const wrapperProps = {
            ...props.wrapperProps,
            className,
            style: {
                '--agnosticon-char': data.char || '',
                '--agnosticon-code': data.code ? `'${data.code}'` : '',
                '--agnosticon-font-family': `"${data.fontFamily}"`,
                '--agnosticon-font-weight': data.fontWeight,
                '--agnosticon-font-size': `${data.size}px`,
                '--agnosticon-color': data.color,
                '--agnosticon-background-color': data.backgroundColor,
                '--agnosticon-border-color': data.borderColor,
                // '--agnosticon-border-radius': `${data.borderRadius}`,
                '--agnosticon-padding': `${data.padding?.left || '0px'}`,
                '--agnosticon-gap': `${data.gap?.bottom || '0px'}`,
            },
        };

        return <BlockListBlock {...props} wrapperProps={wrapperProps} />;
    };
}, 'withIconListWrapperProps');

addFilter(
  'editor.BlockListBlock',
  'benignware/list-wrapper',
  withIconListWrapperProps
);
