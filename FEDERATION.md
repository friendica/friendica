# Federation

## Supported federation protocols and standards

- [ActivityPub](https://www.w3.org/TR/activitypub/) (Server-to-Server, Server-to-Client)
- [WebFinger](https://webfinger.net/)
- [HTTP Message Signatures](https://www.rfc-editor.org/rfc/rfc9421) and the older [draft-cavage-http-signatures](https://datatracker.ietf.org/doc/html/draft-cavage-http-signatures)
- [NodeInfo](https://nodeinfo.diaspora.software/)
- [Diaspora* Protocol](https://diaspora.github.io/diaspora_federation/)
- [DFRN](https://git.friendi.ca/friendica/friendica/src/branch/develop/spec)

## Supported FEPs

- [FEP-f1d5: NodeInfo in Fediverse Software](https://codeberg.org/fediverse/fep/src/branch/main/fep/f1d5/fep-f1d5.md)
- [FEP-1b12: Group federation](https://codeberg.org/fediverse/fep/src/branch/main/fep/1b12/fep-1b12.md) - partial support: following groups, `audience` and activities announced by groups (Lemmy, kbin); no moderation activities
- [FEP-2677: Identifying the Application Actor](https://codeberg.org/fediverse/fep/src/branch/main/fep/2677/fep-2677.md) - the application actor is `/friendica`
- [FEP-e232: Object Links](https://codeberg.org/fediverse/fep/src/branch/main/fep/e232/fep-e232.md) - partial support: only used for links to quoted posts
- [FEP-61cf: The OpenWebAuth Protocol](https://codeberg.org/fediverse/fep/src/branch/main/fep/61cf/fep-61cf.md) - partial support: login at Hubzilla via `/magic` and the token endpoint `/owa`; WebFinger only advertises the token endpoint (no separate `#redirect` link)
- [FEP-67ff: FEDERATION.md](https://codeberg.org/fediverse/fep/src/branch/main/fep/67ff/fep-67ff.md)
- [FEP-b2b8: Long-form Text](https://codeberg.org/fediverse/fep/src/branch/main/fep/b2b8/fep-b2b8.md) - partial support: `Article` is used for posts with titles, but no `preview` property
- [FEP-044f: Consent-respecting quote posts](https://codeberg.org/fediverse/fep/src/branch/main/fep/044f/fep-044f.md) - initial support: incoming quote requests are accepted automatically and a `QuoteAuthorization` is provided; `canQuote` always allows everybody for public posts, no outgoing `QuoteRequest`, no verification of received `quoteAuthorization` stamps, no revocation, no `Reject` or manual approval
- [FEP-3b86: Activity Intents](https://codeberg.org/fediverse/fep/src/branch/main/fep/3b86/fep-3b86.md) - partial support (Follow, Create); no `on-success` and `on-cancel` handling
- [FEP-5feb: Search indexing consent for actors](https://codeberg.org/fediverse/fep/src/branch/main/fep/5feb/fep-5feb.md) - basic support (no index rebuilding when the indexable attribute is enabled; a missing attribute is treated as indexable, the FEP says not indexable)

## ActivityPub

- We send a follow activity for the id of a received root post. This is meant as a request to be included in the collection of receivers for this specific post.
- [Interaction policies](https://docs.gotosocial.org/en/latest/user_guide/settings/#default-interaction-policies) (`interactionPolicy`) as introduced by GoToSocial.
- Featured collections (`featured`) for pinned posts as introduced by Mastodon.

## Diaspora protocol

Friendica supports most entities of the Diaspora protocol except polls.

## Additional documentation

- Documentation is available at every Friendica node at `/help` and in the project repository [friendica/doc](https://git.friendi.ca/friendica/friendica/src/branch/develop/doc) (links work on the nodes documentation).

