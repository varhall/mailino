# Changelog

## Unreleased

### Security
- `MailExchange` no longer disables SSL certificate verification globally via `stream_context_set_default()`. Before this fix, sending a mail disabled verification for the whole PHP process. `setVerifySsl()` is deprecated and has no effect. To disable verification for SMTP only, configure `mail: context: ssl:` in Nette mail configuration.
- New `Attachment::fromUrl()` for remote files. Passing a URL to `addFile()` / `Attachment::fromFile()` is deprecated.

### Fixed
- `MailDecorator::addCc()` / `addBcc()` lost recipient name when mail was extended.
- Plain-text template was HTML-escaped (`&` → `&amp;`). It is now rendered with text content type.
- Attachments from URL were always empty because the temp file was deleted before it was read.
- `onError` event was not triggered for `\Error` (e.g. `TypeError`), because only `\Exception` was caught.
- `getSubject()` / `getFrom()` / `getTemplate()` on a new mail failed with "must not be accessed before initialization". Subject and template now default to an empty string. A missing sender throws `InvalidStateException` with a clear message.
- Applying `Prefix` more than once produced `[TST] [TST] subject`.
- Removed unused `$prefix` parameter from `MailDecorator::setSubject()`.
- `Recipient::setName()` accepts `null`.
- MJML API requests had no timeout and could hang forever. The default is 30 s (connect 10 s).
- `curl_close()` deprecation on PHP 8.5 when using MJML API.

### Added
- `mjml.api` options: `url` (custom MJML API endpoint with the same contract as mjml.io), `timeout`, `connect_timeout`, `validation_level` (`soft` | `strict`).
- Configuration of built-in extensions (`prefix`, `mjml.api`, `mjml.binary`) is validated when the container is compiled. `priority` is validated to 1–5 and `headers` to strings.
- Subject can be defined in the HTML template using `{define subject}...{/define}`. Without a subject, Nette uses the HTML `<title>`.
- The plain-text template is optional and is generated from HTML when missing.
- Embedded images: `embed_dir` config option and `IMail::setBasePath()`.
- `temp_dir` config option (compiled template cache for the default Latte factory).
- `NetteLatteEngineFactory`: uses the application Latte factory (translator, filters).
- `strict` config option: template warnings (undefined variable) throw `ErrorException`.
- `default_extensions` config option: extensions applied by `Mailino::create()`.
- `Mailino::preview()` returns a `Preview` (subject, HTML, plain text) without sending.
- `MailExchange::createMessage()` converts `IMail` to `Nette\Mail\Message`.
- `Attachment::fromContent()` creates an attachment from content in memory, and `addFile()` accepts an `Attachment` instance.
- `IMail::clearTo()`, `clearCc()`, `clearBcc()`, `clearReplyTo()`, `removeHeader()`.
- `MjmlDecorator` caches rendered HTML, so repeated `getHtmlBody()` calls do not call MJML again.
- Sending without any recipient throws `InvalidStateException` before calling the mailer.

### Changed
- Requires PHP 8.1+ (dependencies already required it). Dependency constraints changed from `>=` to `^`.
- `IMail` has new methods (see Added). Custom classes implementing `IMail` directly (not through `MailDecorator`) must implement them.
- `Attachment` content type is detected from content (`finfo`), with `application/octet-stream` as a fallback.
- Not registered extension class passed to `extend()` throws `InvalidArgumentException` instead of `MissingServiceException`.
- PHPStan (level 6) added to QA and CI, plus a CI job with the lowest dependencies on PHP 8.1.
- Coding standard switched from the abandoned `ninjify/qa` (`make cs` failed on newer phpcs) to PSR-12 (`phpcs.xml`, `squizlabs/php_codesniffer`). Code was reformatted: braces for all control structures, `elseif`, LF line endings enforced by `.gitattributes`. `make cs` runs in CI.
