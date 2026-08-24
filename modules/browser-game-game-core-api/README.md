# Browser Game Game Core API

This optional adapter exposes the Game Core public boundary under `/api/v1/browser-game/game-core`. It requires Sanctum authentication, resolves tenant/team scope from the authenticated actor, and delegates mutations to `GameCoreManager`.

The OpenAPI fragment in `openapi/v1-browser-game-game-core.yaml` is part of the public contract and must be bundled by the host API manifest.
