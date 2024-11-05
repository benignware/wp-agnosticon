import resolve from '@rollup/plugin-node-resolve';
import commonjs from '@rollup/plugin-commonjs';
import babel from '@rollup/plugin-babel';

export default [
    {
        input: 'src/editor.jsx', // Editor script entry point
        output: [{
            file: 'dist/agnosticon-editor.js',
            format: 'iife',
            name: 'Agnosticon',
            globals: {
                'react': 'wp.element', // Use WordPress' React version
                'react-dom': 'wp.element' // Adjust if necessary
            }
        }],
        plugins: [
            resolve(),
            commonjs(),
            babel({
                babelHelpers: 'bundled',
                presets: ['@babel/preset-react'],
                exclude: 'node_modules/**'
            }),
        ],
        external: ['react', 'react-dom'] // Exclude React from the editor bundle
    }
];
