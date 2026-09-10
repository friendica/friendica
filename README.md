# LARPnet

This is a customized fork of Friendica for a Polish LARP community. Custom code and configuration live on the `larpnet` branch (production); `develop` tracks upstream Friendica; `larpnet-test` is the staging branch verified before every production release.

### Building and releasing

Merging to `larpnet` does **not** publish a new production image by itself — publishing requires a deliberate release:

```bash
git checkout larpnet && git pull
git tag release-$(date +%Y.%m.%d)      # add -2, -3 if releasing more than once a day
git push origin release-$(date +%Y.%m.%d)
```

Pushing a `release-*` tag triggers CI, which:
- retags the current `:prod` image as `:oldprod` (a one-step rollback target)
- builds and publishes the new image as `:latest`, `:prod`, and the immutable `:prod-<commit-sha>` (a permanent audit trail of every image ever deployed to prod)

For a manual/local build (requires `.env` with registry credentials — copy `.env.example`):

```bash
./build.sh              # builds and pushes only the versioned tag {FRIENDICA_VERSION}-{GIT_SHA}
./build.sh --release    # also promotes to prod (:latest/:prod/:prod-<sha>) - must be run from larpnet
```

See `CLAUDE.md` for the full development setup, architecture notes, and list of larpnet-specific patches.
