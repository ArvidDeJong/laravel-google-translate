# Security policy

This package sends your Google Cloud API key and your content to an external API, so a way to leak
the key, to send it to another host than Google's, or to get it into a log counts as a security
issue.

## Supported versions

Only the latest minor release of 1.x receives security fixes. Upgrade before reporting.

## Reporting a vulnerability

Please do **not** open a public issue. Report it privately instead:

- via [GitHub private vulnerability reporting](https://github.com/ArvidDeJong/laravel-google-translate/security/advisories/new), or
- by email to info@arvid.nl.

Include the package version, the Laravel version and the steps that show the problem. Leave your
API key out.

You will get a reply within a week. Once a fix is released, the advisory is published and you are
credited, unless you prefer not to be.

## Out of scope

- Translated HTML comes from an external service. Sanitise it before you render it unescaped, the
  same way you sanitise the source; what Google returns for a given input is not a vulnerability in
  this package.
- The trait creates rows with `create()` from the attributes your code passes. Which user may start
  a translation, and what they may pass as additional attributes, is up to the host application.
- Costs caused by calling the API more often than you intended. Put the calls behind your own
  authorisation and rate limits.
