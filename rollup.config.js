import { terser } from 'rollup-plugin-terser';

export default (async () => ({
    input: 'assets-dev/profile.js',
    output: {
        file: 'assets/profile.min.js',
        format: 'iife',
        plugins: [
            terser(),
        ],
        compact: true,
        sourcemap: 'hidden',
        strict: false,
    },
    strictDeprecations: true,
}))();
