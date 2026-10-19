// Registers the loader, so the tests can import the modules with the names Moodle uses.
// Run a test: node --import ./local/entities/tests/js/register.mjs local/entities/tests/js/<name>.test.mjs
import {register} from 'node:module';

register('./loader.mjs', import.meta.url);
