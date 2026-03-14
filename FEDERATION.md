# Federation

## Supported federation protocols and standards

- [ActivityPub](https://www.w3.org/TR/activitypub/) (Server-to-Server, Server-to-Client)
- [WebFinger](https://webfinger.net/)
- [Http Signatures](https://datatracker.ietf.org/doc/html/draft-cavage-http-signatures)
- [NodeInfo](https://nodeinfo.diaspora.software/)
- [Diaspora* Protocol](https://diaspora.github.io/diaspora_federation/)
- [DFRN](https://git.friendi.ca/friendica/friendica/src/branch/develop/spec)

## Supported FEPs

- [FEP-f1d5: NodeInfo in Fediverse Software](https://codeberg.org/fediverse/fep/src/branch/main/fep/f1d5/fep-f1d5.md)
- [FEP-1b12: Group federation](https://codeberg.org/fediverse/fep/src/branch/main/fep/1b12/fep-1b12.md) - basics for federation with lemmy, kbin
- [FEP-2677: Identifying the Application Actor](https://codeberg.org/fediverse/fep/src/branch/main/fep/2677/fep-2677.md)
- [FEP-e232: Object Links](https://codeberg.org/fediverse/fep/src/branch/main/fep/e232/fep-e232.md)
- [FEP-61cf: The OpenWebAuth Protocol](https://codeberg.org/fediverse/fep/src/branch/main/fep/61cf/fep-61cf.md) - basics to log in to Hubzilla
- [FEP-67ff: FEDERATION.md](https://codeberg.org/fediverse/fep/src/branch/main/fep/67ff/fep-67ff.md)
- [FEP-b2b8: Long-form Text](https://codeberg.org/fediverse/fep/src/branch/main/fep/b2b8/fep-b2b8.md) - partial support
- [FEP-044f: Consent-respecting quote posts](https://codeberg.org/fediverse/fep/src/branch/main/fep/044f/fep-044f.md) - initial support

## ActivityPub

- We send a follow activity for the id of a received root post. This is meant as a request to be included in the collection of receivers for this specific post.

## Diaspora protocol

Friendica supports most entities of the Diaspora protocol except polls.

## Additional documentation

- Documentation is available at every Friendica node at `/help` and in the project repository [friendica/doc](https://git.friendi.ca/friendica/friendica/src/branch/develop/doc) (links work on the nodes documentation).

