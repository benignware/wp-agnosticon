// (function (richText, blockEditor, element, components) {
//   'use strict';

//   const { useState, useEffect, useRef } = element;
//   const { Popover, Button, TextControl } = components;
//   const { RichTextToolbarButton, useBlockProps } = blockEditor;
//   const formatName = 'myplugin/inline-icon';

//   // Register the custom format type
//   richText.registerFormatType(formatName, {
//     title: 'Inline Icon',
//     tagName: 'i',
//     className: 'my-inline-icon', // A class name for styling
//     edit({ isActive, value, onChange }) {
//       const [isOpen, setOpen] = useState(false);
//       const [name, setName] = useState('');
//       const popoverRef = useRef();

//       // Effect to set the custom name if the format is active
//       useEffect(() => {
//         if (isActive) {
//           const activeFormats = richText.getActiveFormat(value);
//           const activeFormat = activeFormats ? activeFormats.find((format) => format.type === formatName) : null;

//           if (activeFormat && activeFormat.attributes && activeFormat.attributes['data-custom-name']) {
//             setName(activeFormat.attributes['data-custom-name']);
//           }
//         } else {
//           setName(''); // Clear the name if not active
//         }
//       }, [isActive, value]);

//       const applyFormat = () => {
//         let newValue;

//         if (name) {
//           newValue = richText.toggleFormat(value, {
//             type: formatName,
//             attributes: { 'data-custom-name': name, style: 'outline: 1px solid red;' },
//           });
//         } else {
//           newValue = richText.removeFormat(value, formatName);
//         }

//         onChange(newValue);
//         setOpen(false);
//       };

//       // Function to handle opening the popover
//       const handleOpenPopover = () => {
//         setOpen(true);
//         setTimeout(() => {
//           const selection = richText.getSelection();
//           if (selection) {
//             const { left, top, height } = selection.getBoundingClientRect();
//             // Set popover position directly below selected text
//             popoverRef.current.style.transform = `translate(${left}px, ${top + height}px)`;
//           }
//         }, 0);
//       };

//       return (
//         <>
//           <RichTextToolbarButton
//             icon="edit"
//             title="Add Inline Icon"
//             onClick={handleOpenPopover}
//             isActive={isActive}
//           />
//           {isOpen && (
//             <Popover ref={popoverRef} position="middle center">
//               <div>
//                 <TextControl
//                   label="Custom Name"
//                   value={name}
//                   onChange={(newName) => setName(newName)}
//                 />
//                 <Button isPrimary onClick={applyFormat}>
//                   Apply
//                 </Button>
//                 <Button isSecondary onClick={() => setOpen(false)}>
//                   Cancel
//                 </Button>
//               </div>
//             </Popover>
//           )}
//         </>
//       );
//     },
//   });
// })(window.wp.richText, window.wp.blockEditor, window.wp.element, window.wp.components);
