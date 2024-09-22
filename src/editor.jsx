// const { __ } = wp.i18n;
// const { TextControl, PanelBody } = wp.components;
// const { Fragment, useState } = wp.element;
// const { addFilter } = wp.hooks;
// const { InspectorControls } = wp.blockEditor;

// // Add new attribute to the navigation-link block.
// const addIconAttribute = (settings) => {
//     if (settings.name !== 'core/navigation-link') {
//         return settings;
//     }

//     return {
//         ...settings,
//         attributes: {
//             ...settings.attributes,
//             icon: {
//                 type: 'object', // Change to object
//                 default: {},
//             },
//         },
//     };
// };

// addFilter(
//     'blocks.registerBlockType',
//     'menu-plus/navigation-link/icon-attribute',
//     addIconAttribute
// );

// // IconAutoSuggest component for icon search.
// const IconAutoSuggest = ({ value, onChange }) => {
//     const [searchResults, setSearchResults] = useState([]);

//     const handleSearch = (search) => {
//         if (!search) {
//             setSearchResults([]);
//             return;
//         }

//         fetch(`${window.location.origin}/wp-admin/admin-ajax.php?action=agnosticon_search&search=${encodeURIComponent(search)}`)
//             .then((response) => response.json())
//             .then((response) => {
//                 if (response.success) {
//                     setSearchResults(response.data);
//                 }
//             })
//             .catch((error) => {
//                 console.error('AJAX error:', error);
//             });
//     };

//     const handleSelect = (iconData) => {
//         onChange(iconData); // Save the whole icon object
//         setSearchResults([]); // Close suggestions on selection
//     };

//     return (
//         <Fragment>
//             <TextControl
//                 label={__("Icon", "menu-plus")}
//                 value={value ? value.name : ''}
//                 onChange={(newValue) => {
//                     onChange(newValue);
//                     handleSearch(newValue);
//                 }}
//                 placeholder={__("Search for an icon...", "menu-plus")}
//             />
//             {searchResults.length > 0 && (
//                 <ul className="components-autocomplete__results">
//                     {searchResults.map((icon) => (
//                         <li 
//                             key={icon.id} 
//                             className="components-autocomplete__result" 
//                             onClick={() => handleSelect(icon)} // Pass the whole icon object
//                         >
//                             <span className={icon.class} style={{ marginRight: '10px' }}></span> {icon.name}
//                         </li>
//                     ))}
//                 </ul>
//             )}
//         </Fragment>
//     );
// };

// // Add icon controls to the block inspector.
// const withInspectorControls = (BlockEdit) => {
//     return (props) => {
//         if (props.name !== 'core/navigation-link') {
//             return <BlockEdit {...props} />;
//         }

//         const { attributes, setAttributes } = props;
//         const { icon } = attributes;

//         console.log('icon.entity: ', icon.entity);

//         return (
//             <Fragment>
                
//                 <InspectorControls>
//                     <PanelBody title={__("Icon Settings", "menu-plus")} initialOpen={true}>
//                         <IconAutoSuggest
//                             value={icon}
//                             onChange={(newIcon) => setAttributes({ icon: newIcon })}
//                         />
//                     </PanelBody>
//                 </InspectorControls>
//                 {icon && icon.entity && (
//                     <i
//                         className={icon.class}
//                         style={{
//                             ...icon.style ? { fontFamily: icon.font_family } : {},
//                             // width: '24px',
//                             // height: '24px',
//                             // display: 'inline-block',
//                             // outline: '1px solid #ccc',
//                             // textAlign: 'center',
//                             // lineHeight: '24px',
//                             marginRight: 'calc(var(--wp--style--block-gap, 0.5em) * -1 + 0.5em)',
//                         }}
//                         // data-agnosticon-char={icon.char}
//                         dangerouslySetInnerHTML={{ __html: icon.entity }} // Use entity as inner HTML
//                     ></i>
//                 )}
//                 <BlockEdit {...props} />
//             </Fragment>
//         );
//     };
// };

// addFilter(
//     'editor.BlockEdit',
//     'menu-plus/navigation-link/with-inspector-controls',
//     withInspectorControls
// );
