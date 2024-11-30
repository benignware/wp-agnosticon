import IconControl from "./IconControl.jsx";

const { __ } = wp.i18n;
const { TextControl, PanelBody, RangeControl, ColorPalette } = wp.components;
const { Fragment } = wp.element;
const { addFilter } = wp.hooks;
const { InspectorControls } = wp.blockEditor;
const { createHigherOrderComponent } = wp.compose;

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
                type: 'string',
                default: '',
            },
            bulletSize: {
                type: 'number',
                default: 16, // Default bullet size in px
            },
            bulletColor: {
                type: 'string',
                default: '', // Default to no color
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
        const { icon: iconId, bulletSize, bulletColor } = attributes;

        return (
            <Fragment>
                <InspectorControls>
                    <PanelBody title={__("Icon Settings", "benignware")} initialOpen={true}>
                        <IconControl
                            value={iconId}
                            onChange={(newIcon) => setAttributes({ icon: newIcon })}
                        />
                        <RangeControl
                            label={__("Bullet Size (px)", "benignware")}
                            value={bulletSize}
                            onChange={(newSize) => setAttributes({ bulletSize: newSize })}
                            min={8}
                            max={48}
                        />
                        <TextControl
                            label={__("Bullet Color", "benignware")}
                            value={bulletColor}
                            onChange={(newColor) => setAttributes({ bulletColor: newColor })}
                            type="color"
                        />
                    </PanelBody>
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

// Step 3: Add Inline Styles to List Wrapper
const withIconListWrapperProps = createHigherOrderComponent((BlockListBlock) => {
    return (props) => {
        if (props.name !== 'core/list') {
            return <BlockListBlock {...props} />;
        }

        const { attributes } = props;
        const { icon: iconId, bulletSize, bulletColor } = attributes;

        const icon = iconId ? window.agnosticon?.icons[iconId] : null;

        const wrapperProps = {
            ...props.wrapperProps,
            ...(icon ? {
                className: 'agnosticon-list',
                style: {
                    '--agnosticon-char': icon ? String.fromCodePoint(`0x${icon.char}`) : '',
                    '--agnosticon-font-family': icon ? icon.font_family : undefined,
                    '--agnosticon-font-weight': icon ? icon.font_weight : undefined,
                    '--agnosticon-bullet-size': `${bulletSize}px`,
                    '--agnosticon-bullet-color': bulletColor,
                },
            } : {}),
        };

        return <BlockListBlock {...props} wrapperProps={wrapperProps} />;
    };
}, 'withIconListWrapperProps');

addFilter(
    'editor.BlockListBlock',
    'benignware/list-wrapper',
    withIconListWrapperProps
);
