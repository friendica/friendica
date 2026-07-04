# Mastodon Streaming Fixtures

Mastodon-compatible Server-Sent Event payloads for protocol and API tests.

These are synthetic fixtures modeled after the structures documented by Mastodon, not captured live traffic. They intentionally use reserved example domains and stable IDs so tests stay deterministic.

Sources used for the shape of these payloads:

- Streaming events: https://docs.joinmastodon.org/methods/streaming/
- Status entity: https://docs.joinmastodon.org/entities/Status/

Keep protocol-specific expectations in the tests. Keep reusable raw payloads here.
