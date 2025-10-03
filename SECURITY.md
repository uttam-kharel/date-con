# Security Policy

## Supported versions

| Version | Supported |
|---|---|
| 1.x | ✅ |
| < 1.0 | ❌ |

## Reporting a vulnerability

Please **do not** open a public issue or pull request for security problems.

Report vulnerabilities privately instead, so they can be fixed and released
before they are disclosed:

- Open a private security advisory on GitHub (preferred):
  `https://github.com/sambat/nepali-calendar/security/advisories/new`
- Or email the maintainers directly if you have their address.

Include as much of the following as possible:

- The package and PHP / Laravel versions affected
- A description of the vulnerability and its impact
- A minimal reproduction
- Any suggested fix

You will receive a response as soon as possible. Please give us a reasonable
window to prepare a fix before disclosing the issue publicly.

## Scope

This package performs date arithmetic and conversion; report anything that could
cause incorrect dates, data corruption, or unsafe behavior under adversarial
input (e.g. malformed or maliciously crafted date strings).
