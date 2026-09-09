# 68KB

A lightweight knowledge base application — forked from [68kb/68kb](https://github.com/68kb/68kb) with improved accessibility, modern UI, and SQLite support.

## What's Different

- **SQLite or MySQL** — runs on either engine; SQLite needs no database server (a single file), MySQL suits larger/production setups
- **Private mode (optional viewer login)** — gate the entire front-end behind a login; viewer accounts are admin-created (no self-signup) and it's **off by default**, so the knowledge base stays public unless you turn it on
- **Reusable article variables** — define `{{var_name}}` values once in the admin area; each placeholder is substituted into article content for readers, so repeated values (support email, app version, a price or URL) are edited in one place
- **Improved UI and accessibility** — cleaner interface with better usability
- **PHP 7.2+ compatibility** — updated constructors, removed deprecated functions
- **Summernote WYSIWYG editor** — replaces the old js-quicktags toolbar

## Quick Start

**Requirements:** PHP 7.2+ — the PDO SQLite extension for the default SQLite mode, or a MySQL database.

1. Upload the contents of the `upload/` directory to your web server
2. Point your web server's document root to the uploaded directory
3. Create your config files from the shipped templates (see **Configuration** below)
4. Visit your site and follow the setup wizard at `/index.php/setup`

No MySQL needed — the database is a single SQLite file created automatically during setup.

## Configuration

This repository ships **template config files only** — no real credentials are
committed. Before the app will run, copy each `*.example.php` template to its real
filename and fill in your values:

| Copy this template | To this file |
| --- | --- |
| `upload/includes/config.example.php` | `upload/includes/config.php` |
| `upload/includes/application/config/config.example.php` | `upload/includes/application/config/config.php` |
| `upload/includes/application/config/database.example.php` | `upload/includes/application/config/database.php` |

Then edit `upload/includes/config.php` and choose your database driver:

- **SQLite (default, no server):** set `$db['default']['dbdriver'] = "pdo_sqlite";` and
  `$db['default']['database']` to an absolute path for the `.sqlite` file.
- **MySQL:** set `$db['default']['dbdriver'] = "mysqli";` and fill in `hostname`,
  `username`, `password`, and `database`.

`config.php` is git-ignored, so your real credentials never get committed.

## Credits

Originally forked from [68kb/68kb](https://github.com/68kb/68kb). The original project referenced a license at `http://68kb.com/user_guide/license.html` (now defunct).

### Bundled Third-Party Software

- [Summernote](https://github.com/summernote/summernote) — MIT License
