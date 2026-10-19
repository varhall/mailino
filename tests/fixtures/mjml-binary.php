<?php

// Fake MJML CLI (mjml -i -s) for integration tests: reads MJML from stdin, writes HTML to stdout

$mjml = stream_get_contents(STDIN);

if (str_contains($mjml, '<mj-invalid')) {
    fwrite(STDERR, "Line 1 (mj-invalid) - Element mj-invalid doesn't exist\n");
    exit(1);
}

echo '<html><body>' . strip_tags($mjml) . '</body></html>';
