# Third-party notices

Starterkit Larawire Agentic depends on open-source Composer packages whose
licenses are installed with those packages under `vendor/` and declared by
their package metadata.

## CMS editing libraries

The optional CMS editor uses [Quill 2.0.3](https://github.com/slab/quill/tree/v2.0.3),
copyright Slab and the contributors named in its BSD 3-Clause license. Its
runtime and full license are included in `public/assets/starter/vendor/quill/`.
Menu ordering uses [SortableJS 1.15.7](https://github.com/SortableJS/Sortable/tree/1.15.7),
copyright its contributors, under the MIT License retained in
`public/assets/starter/vendor/sortable/LICENSE`.
Pinned source URLs and SHA-256 checksums are recorded in `docs/cms-dependencies.json`.
These libraries are served locally; neither requires a cloud service or API key.

Server-side rich-content cleaning uses Symfony HTML Sanitizer under its MIT
License and its Composer-managed dependencies. Their license texts are retained
in `vendor/` by Composer.

Authenticator enrollment QR codes use
[BaconQrCode](https://github.com/Bacon/BaconQrCode), licensed under the
BSD 2-Clause License, together with its transitive
[DASPRiD Enum](https://github.com/DASPRiD/Enum) dependency under the same
license. Their license metadata and texts are installed by Composer.

The Tabler integration is derived from [Tabler](https://github.com/tabler/tabler),
copyright the Tabler authors and licensed under the MIT License. Its license is
retained at `docs/template/tabler/LICENSE`. Raw Tabler HTML/assets are supplied
separately and are not part of the Composer archive.

DashCode is a commercial theme used under the repository owner's applicable
license for internal projects. Its vendor HTML and source package are not part
of the Composer archive.

Vuexy 3.0.0 is a commercial theme licensed by the owner for personal/internal
use. Its runtime is supplied through the owner-managed private local archive;
there is no public redistribution permission or public download URL. The
canonical package contains only the theme-owned integration, source atlas, and
manifest metadata, never the vendor HTML or runtime ZIP.

## Oklab conversion matrices

The small Oklab conversion implementation in `StarterBrandColor` uses the
public-domain conversion matrices published by Björn Ottosson at
https://bottosson.github.io/posts/oklab/ (2021 matrix revision). No external
color library or runtime dependency is included.
