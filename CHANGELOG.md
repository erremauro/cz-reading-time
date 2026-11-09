# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.4] 2026-11-08
### Changed
- Il tempo di lettura è ora visibile anche agli utenti non loggati. Per gli utenti loggati è visibile di default e viene nascosto solo se `czup_show_readingtime` vale esplicitamente `0`.

## [1.0.3] 2026-11-07
### Fixed
- Nei post paginati il tempo totale di lettura (`p.reading-time-total`) non cambia più a seconda della pagina visualizzata. Durante il calcolo cz-outline sostituiva ogni pagina con quella corrente (e iniettava l'indice), quindi il totale era il tempo della pagina corrente moltiplicato per il numero di pagine. Ora il calcolo attiva il filtro `cz_outline_bypass_content_filter` (cz-outline >= 1.3.1).

## [1.0.2] 2026-06-12
### Changed
- Updated author metadata to Roberto Mauro.
- Aligned plugin header version with repository release history.

## [1.0.1] 2026-05-20
### Fix
- Fix reading-time presentation for collapsable shortcode content

## [1.0.0] 2026-03-02
### Added
- Initial release.

[Unreleased]: https://github.com/erremauro/cz-reading-time/compare/v1.0.4...HEAD
[1.0.4]: https://github.com/erremauro/cz-reading-time/releases/tag/v1.0.4
[1.0.3]: https://github.com/erremauro/cz-reading-time/releases/tag/v1.0.3
[1.0.2]: https://github.com/erremauro/cz-reading-time/releases/tag/v1.0.2
[1.0.1]: https://github.com/erremauro/cz-reading-time/releases/tag/v1.0.1
[1.0.0]: https://github.com/erremauro/cz-reading-time/releases/tag/v1.0.0
