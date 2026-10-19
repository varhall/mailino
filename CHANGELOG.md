# Changelog

## 2.0.0 (unreleased)

A major version with breaking changes. See [UPGRADE-2.0.md](UPGRADE-2.0.md) for details and the upgrade checklist.

### Changed
- `Mail` is a serializable value object (template, data, recipients, attachments, headers, extensions) without DI container and Latte engine. The `IMail` interface is removed.
- `MailRenderer` renders templates and applies extensions. It returns the immutable `RenderedMail`.
- Extensions process `RenderedMail` (`IExtension::process()`) instead of decorating the mail. `MailDecorator` and decorators are removed. `extend()` returns the same instance and ignores duplicates.
- Exchanges send `Mail`, subclasses implement `deliver(RenderedMail)`. `onSuccess` receives `RenderedMail` and `onError` receives `\Throwable`.
- `Mailino::preview()` returns `RenderedMail` (`Preview` is removed).
- `Recipient` and `Attachment` are immutable.
- Own MJML renderers (`MjmlApiRenderer`, `MjmlBinaryRenderer`) and `MjmlException` replace `qferr/mjml-php`, which is no longer a dependency.
- Requires PHP 8.2. All files use `declare(strict_types=1)`. PHPStan runs at level 8.

### Added
- Sending through a queue: `QueueExchange`, `IMailQueue` adapter, `QueueWorker` and JSON `MailSerializer`.
- The subject can come from `<mj-title>` of MJML templates.
- `Mail::getExtensions()`, `hasExtension()`, `removeExtension()`, `clearFiles()` and `Attachment::withName()`.

### Removed
- Deprecated 1.x API: `IMail`, `MailDecorator`, `IExtension::extend()`, `MailExchange::setVerifySsl()` / `isVerifySsl()` / `createMessage()`, URL as an attachment path, the DI container in `Mail` and `Mailino` constructors, and `Preview`.

## 1.3.1 (unreleased)

### Fixed
- `MjmlBinaryRenderer` emitted a "Broken pipe" notice when the MJML process exited before reading the whole input. The process error is reported instead.
- CI code coverage uses PCOV instead of phpdbg, and tests start child PHP processes with the PHP CLI binary even when they run under phpdbg.

## 1.3.0 (2026-10-19)

The last feature release of the 1.x line. It is maintained in the `v1.x` branch (bug fixes only). See [UPGRADE-2.0.md](UPGRADE-2.0.md) for the next major version.

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
- The MJML binary renderer could deadlock when MJML wrote large output to stderr, and it had no timeout. It is replaced by an own `MjmlBinaryRenderer`, which reads stdout and stderr concurrently and kills the process after `timeout`.

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
- `IExchange` interface, plus `NullExchange` (keeps messages in memory) and `LogExchange` (writes `.eml` files). The default exchange is the named service `mailino.exchange`, so it can be replaced in configuration.
- `MessageFactory` converts `IMail` to `Nette\Mail\Message` and is shared by all exchanges.
- `ExtensionRegistry` (service `mailino.registry`): all services implementing `IExtension` are registered automatically. Mails created by `Mailino` no longer hold the DI container.
- `mjml.binary` options: `binary` accepts a list (command and arguments, executed without shell), and a new `timeout` option.

### Changed
- Requires PHP 8.1+ (dependencies already required it). Dependency constraints changed from `>=` to `^`.
- `IMail` has new methods (see Added). Custom classes implementing `IMail` directly (not through `MailDecorator`) must implement them.
- `Attachment` content type is detected from content (`finfo`), with `application/octet-stream` as a fallback.
- Not registered extension class passed to `extend()` throws `InvalidArgumentException` instead of `MissingServiceException`.
- PHPStan (level 6) added to QA and CI, plus a CI job with the lowest dependencies on PHP 8.1.
- Autoloading switched from classmap to PSR-4 (directories renamed to `DI/`, `Entities/`, `Exchanges/`, `Extensions/`). Class names and namespaces are unchanged.
- Services of the extension are named with its prefix (`mailino.exchange`, `mailino.registry`, `mailino.template_factory`), so the extension also works when it is registered under another name.
- Coding standard switched from the abandoned `ninjify/qa` (`make cs` failed on newer phpcs) to PSR-12 (`phpcs.xml`, `squizlabs/php_codesniffer`). Code was reformatted: braces for all control structures, `elseif`, LF line endings enforced by `.gitattributes`. `make cs` runs in CI.

### Deprecated (removed in 2.0)
- `MailDecorator` and `IExtension::extend()`: extensions will process the rendered mail instead of decorating it.
- `IMail::getHtmlBody()` / `getPlainBody()`: use `Mailino::preview()`.
- Passing the DI container to `Mail` / `Mailino` constructors: extensions are resolved by `ExtensionRegistry`.
- `MailExchange::setVerifySsl()` / `isVerifySsl()`.
- URL as a path in `addFile()` / `Attachment::fromFile()`: use `Attachment::fromUrl()`.
