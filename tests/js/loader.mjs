// Node loader for the tests of the JavaScript of the unavailability page.
//
// The modules are AMD modules written as ES modules, which Moodle builds. In the tests they are used as
// they are: an import of 'local_entities/unavailability/<name>' is answered with amd/src/unavailability/<name>.js.
// Only modules that do not need Moodle (no core/... imports) can be tested this way.
const PREFIX = 'local_entities/unavailability/';
const SOURCE = new URL('../../amd/src/unavailability/', import.meta.url);

export const resolve = async(specifier, context, nextResolve) => {
    if (specifier.startsWith(PREFIX)) {
        return {url: new URL(`${specifier.slice(PREFIX.length)}.js`, SOURCE).href, format: 'module', shortCircuit: true};
    }
    return nextResolve(specifier, context);
};

export const load = async(url, context, nextLoad) => {
    return nextLoad(url, url.startsWith(SOURCE.href) ? {...context, format: 'module'} : context);
};
