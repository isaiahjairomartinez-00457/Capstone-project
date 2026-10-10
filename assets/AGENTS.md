# Flora-Guard Development Instructions

## Project

Flora-Guard is a native flora detection and management system
using computer vision and geoanalytics.

## Important

Read `knowledge.md` before making major changes.

Follow `AGENTS.md` for development rules.

## Architecture

The project contains:

- Mobile application
- Computer vision model
- Database
- Web administration system
- Mapping and geoanalytics

## Development Rules

- Do not overwrite working features unnecessarily.
- Inspect existing code before modifying it.
- Reuse existing components where possible.
- Keep changes focused on the requested task.
- Do not expose API keys or credentials.
- Test changes before committing.

## YOLO

YOLO is responsible for native flora detection.

Do not change model classes or inference configuration
without checking the existing implementation.

## Database

Preserve existing database structures and records.

## UI

Maintain the existing design system and responsive behavior.

## Git

Work on a feature branch.

Do not directly modify `main` unless explicitly instructed.
