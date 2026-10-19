#!/usr/bin/env bash
# Runs the tests of the JavaScript logic of the unavailability page in several timezones.
# Needs only Node (version 20 or newer). Usage: local/entities/tests/js/run.sh
dir="$(cd "$(dirname "$0")" && pwd)"
status=0
for tz in Europe/Vienna America/New_York UTC Pacific/Auckland; do
    for test in "$dir"/*.test.mjs; do
        if ! TZ="$tz" node --import "$dir/register.mjs" "$test" > /dev/null 2>&1; then
            echo "FAILED: $(basename "$test") in $tz"
            status=1
        fi
    done
done
[ "$status" -eq 0 ] && echo "All JavaScript tests passed in 4 timezones."
exit "$status"
