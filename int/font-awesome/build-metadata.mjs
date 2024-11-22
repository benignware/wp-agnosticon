import fs from 'fs';
import path from 'path';
import yaml from 'js-yaml';

// Paths for Font Awesome metadata
const FA_ICONS_YML_PATH = path.resolve('node_modules/@fortawesome/fontawesome-free/metadata/icons.yml');
const DIST_METADATA_JSON_PATH = path.resolve('dist/fontawesome-metadata.json');

// Convert YAML to JSON
function convertYamlToJson() {
    if (!fs.existsSync(FA_ICONS_YML_PATH)) {
        console.error(`Font Awesome YAML metadata not found at ${FA_ICONS_YML_PATH}`);
        process.exit(1);
    }

    try {
        const yamlContent = fs.readFileSync(FA_ICONS_YML_PATH, 'utf8');
        const metadata = yaml.load(yamlContent);

        fs.writeFileSync(
            DIST_METADATA_JSON_PATH,
            JSON.stringify(metadata, null, 2),
            'utf8'
        );
        console.log('Font Awesome metadata converted to JSON:', DIST_METADATA_JSON_PATH);
    } catch (error) {
        console.error('Error converting YAML to JSON:', error);
        process.exit(1);
    }
}

// Execute the conversion
convertYamlToJson();
