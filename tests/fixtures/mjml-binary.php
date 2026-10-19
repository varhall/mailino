<?php

// Fake MJML CLI (mjml -i -s) for integration tests: reads MJML from stdin, writes HTML to stdout

$mjml = stream_get_contents(STDIN);

if (str_contains($mjml, '<mj-invalid')) {
    fwrite(STDERR, "Line 1 (mj-invalid) - Element mj-invalid doesn't exist\n");
    exit(1);
}

if (str_contains($mjml, '<mj-sleep')) {
    sleep(10);
}

if (str_contains($mjml, '<mj-args')) {
    echo implode(' ', array_slice($argv, 1));
    exit(0);
}

if (str_contains($mjml, '<mj-noisy')) {
    // large stderr output (warnings) together with large stdout
    fwrite(STDERR, str_repeat('warning ', 200000));
}

echo '<html><body>' . strip_tags($mjml) . '</body></html>';
