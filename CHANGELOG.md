# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [8.0.0] - 2026-11-05

### Added
- Adventure overview, guided account setup, eight character classes, and persistent beginner guide.
- Thirty-nine example story quests, ninety-seven items, and sixty NPC encounters with repeatable seeding.
- Turn-based NPC combat and consensual PvP duels with tactical actions, equipment bonuses, ratings, and battle reports.
- Super-administrator setup wizard and NPC encounter management.
- Upgrade and gameplay documentation in [v8.0.0 release notes](docs/releases/v8.0.0.md).

### Changed
- Player navigation, active quest tracking, inventory presentation, and empty states now reflect actual game data.
- Quest item rewards stack correctly and cumulative XP can advance multiple levels.
- Hunting board pagination supports the larger NPC catalog.

### Upgrade
- Run database migrations and rebuild frontend assets before using the new panels.
- Run `php artisan db:seed --class=NpcSeeder` to install the example world.
- Enable PvP in the admin setup wizard, then let players opt in individually.

## [Unreleased]

### Added
- Initial documentation structure with CHANGELOG, CONTRIBUTING, and SECURITY policies

### Changed
- Enhanced project documentation and specifications
