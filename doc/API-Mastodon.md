# Mastodon API

* [Home](help)
  * [Using the APIs](https://docs.joinmastodon.org/api/)

## Overview

Friendica provides the following endpoints defined in [the official Mastodon API reference](https://docs.joinmastodon.org/api/).

Authentication is the same as described in [Using the APIs](help/api#Authentication).

## Entities

These endpoints use the [Mastodon API entities](https://docs.joinmastodon.org/entities/).

## Implemented endpoints

- [GET /api/v1/follow_requests](https://docs.joinmastodon.org/methods/accounts/follow_requests#pending-follows)

## Non-implemented endpoints

- [POST /api/v1/follow_requests/:id/authorize](https://docs.joinmastodon.org/methods/accounts/follow_requests#accept-follow)
- [POST /api/v1/follow_requests/:id/reject](https://docs.joinmastodon.org/methods/accounts/follow_requests#reject-follow)

