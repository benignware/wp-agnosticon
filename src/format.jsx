import IconControl from "./IconControl.jsx";

(function (richText, blockEditor, element, components) {
  'use strict';
  
  const formatName = 'agnosticon/inline-icon';
  const attributeName = 'data-agnosticon-id';

  const { useState, useEffect } = element;
  const {
    Popover,
    Button,
    TextControl,
    __experimentalVStack: VStack,
    __experimentalHStack: HStack
  } = components;
  const { RichTextToolbarButton } = blockEditor;
  
  // Register the custom format type
  richText.registerFormatType(formatName, {
    title: 'Inline Icon',
    // object: true,
    tagName: 'span',
    className: 'agnosticon-icon', // A class name for styling
    attributes: {
      // 'data-agnosticon-query': '',
      'data-agnosticon-id': '',
      'data-agnosticon-char': '',
      style: '',
      class: '',
      // role: 'img',
      // title: '',
    },
    edit({
      value,
      onChange,
      isObjectActive,
      contentRef,
      //
      isActive,
    }) {
      const activeObject = value && isObjectActive ? richText.getActiveObject(value) : null;

      const activeAttributes = {
        ...activeObject?.attributes,
        ...activeObject?.unregisteredAttributes,
      }
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
        const icon = name ? window.agnosticon?.find(name) : null;

        const newAttributes = {
          [attributeName]: name,
          'data-agnosticon-char': icon?.char ? String.fromCodePoint(`0x${icon.char}`) : '',
          // 'data-agnosticon-id': icon?.id || '',
          style: icon?.style || '',
          // class: icon?.class || '',
          // role: 'img',
          // title: icon?.name || name,
        }
        
        if ( !isActive ) {
          newValue = richText.insertObject(value, {
            type: formatName,
            attributes: newAttributes,
          });
        } else if (name) {
          newValue = richText.applyFormat(value, {
            type: formatName,
            attributes: newAttributes,
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
      }

      // Function to handle opening the popover
      const handleOpenPopover = () => {
        setOpen(true);
      };
      const isPopoverOpen = isOpen;
      const popoverAnchor = richText.useAnchor( {
        editableContentElement: contentRef.current,
        settings: {
          title: 'Inline Icon',
        },
      } );

      return (
        <>
          <RichTextToolbarButton
            icon="edit"
            title="Inline Icon"
            onClick={handleOpenPopover}
            isActive={ isObjectActive }
          />
          {isPopoverOpen && (
              <Popover
                className="icon-control__popover"
                placement="bottom"
                focusOnMount={ true }
                anchor={ popoverAnchor }
                shift={true}
                noArrow={false}
                offset={10}
              >
                 <VStack spacing={ 1 } style={{ width: '300px', padding: '10px' }}>
                    <IconControl
                      label="Icon"
                      value={name}
                      onChange={(newName) => setName(newName)}
                    />
                    <HStack>
                      <Button isPrimary onClick={applyFormat}>
                        Apply
                      </Button>
                      <Button isSecondary onClick={removeFormat}>
                        Remove
                      </Button>
                    </HStack>
                  </VStack>
            </Popover>
          )}
        </>
      );
    },
  });
})(window.wp.richText, window.wp.blockEditor, window.wp.element, window.wp.components);
