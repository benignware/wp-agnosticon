const { TextControl, Autocomplete } = wp.components;
const { useState } = wp.element;

const AutoSuggest = ({ label, value, onChange }) => {
    // Predefined set of fake items
    const fakeItems = [
        { label: "Apple", value: "apple" },
        { label: "Banana", value: "banana" },
        { label: "Cherry", value: "cherry" },
        { label: "Date", value: "date" },
        { label: "Elderberry", value: "elderberry" },
    ];

    const [suggestions, setSuggestions] = useState([]);

    // Handle input change
    const handleInputChange = (newValue) => {
        onChange(newValue); // Update the parent state

        if (newValue) {
            const filteredItems = fakeItems.filter((item) =>
                item.label.toLowerCase().includes(newValue.toLowerCase())
            );
            setSuggestions(filteredItems);
        } else {
            setSuggestions([]);
        }
    };

    return (
        <Autocomplete
            completers={[
                {
                    name: "fake-items-completer",
                    triggerPrefix: "",
                    options: suggestions.map((item) => ({
                        name: item.label,
                        value: item.value,
                    })),
                    getOptionLabel: ({ name }) => name,
                    getOptionKeywords: ({ value }) => [value],
                },
            ]}
        >
            {({ isOpen, listBoxId, activeId }) => (
                <TextControl
                    label={label}
                    value={value}
                    onChange={handleInputChange}
                    placeholder="Type to search..."
                    aria-autocomplete="list"
                    aria-expanded={isOpen}
                    aria-owns={listBoxId}
                    aria-activedescendant={activeId}
                />
            )}
        </Autocomplete>
    );
};

// Example Usage
const AutoSuggestExample = () => {
    const [inputValue, setInputValue] = useState("");

    return (
        <AutoSuggest
            label="Search Fruits"
            value={inputValue}
            onChange={setInputValue}
        />
    );
};

export default AutoSuggestExample;
