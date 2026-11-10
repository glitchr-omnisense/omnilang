---
title: The harness
order: 50
---

# The harness

```sh
cd docker
docker compose run --rm omnilang test       # every package's tests, no network
docker compose run --rm omnilang bare       # plain PHP: the core with nothing else loaded
docker compose run --rm omnilang phpstan    # PHPStan at max, every package
docker compose down -v                      # the harness's volume gone
```

PHP 8.4 (Alpine), Composer - the same image as the other omni harnesses (`docker tag
omnisense/omnitravel-harness omnisense/omnilang-harness` spares the build), on Docker's default
bridge. Providers come from GitHub (branch 1.x) unless their checkout sits under
`OMNILANG_PLUGINS` (`../..` in `docker/.env` when the core sits in the workspace beside them);
omnisense/contracts likewise, under `OMNISENSE_CONTRACTS`, its tests run with the family's.
