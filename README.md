# Multistream Tools

[![Composer build](https://github.com/artandor/multistream-tools/actions/workflows/symfony.yml/badge.svg)](https://github.com/artandor/multistream-tools/actions/workflows/symfony.yml)
[![Docker build](https://github.com/artandor/multistream-tools/actions/workflows/ci.yml/badge.svg)](https://github.com/artandor/multistream-tools/actions/workflows/ci.yml)
[![Translation status](https://hosted.weblate.org/widgets/multistream-tools/-/glossary/svg-badge.svg)](https://hosted.weblate.org/engage/multistream-tools/)
[![Wallaby.js](https://img.shields.io/badge/wallaby.js-powered-blue.svg?style=flat&logo=github)](https://wallabyjs.com/oss/)
[![codecov](https://codecov.io/gh/artandor/multistream-tools/branch/main/graph/badge.svg?token=VJTVKRZ3DM)](https://codecov.io/gh/artandor/multistream-tools)

## Description

This project aim to make multi-streaming easier for streamers. We made it because it was painfull to update all titles
from all websites, and we have more ideas for the future.

## Features

- [X] Update title and categories from all platforms
- [X] Aggregate stats fromm all platforms
- [ ] Receive alerts from all platforms
- [ ] Have labels from all platforms (Latest follower, latest sub, ...)
- [ ] Receive alerts from donations & have labels for it
- [ ] Create alert overlay ?
- [ ] Aggregate chats from all platforms (Bonus since Brime will do it)

## Supported Platforms

- [X] Youtube
- [X] Twitch
- [X] Brime
- [X] Trovo
- [ ] Facebook Gaming

## Translation contribution
[![Translation contribution tool](https://hosted.weblate.org/widgets/multistream-tools/-/glossary/287x66-white.png)](https://hosted.weblate.org/engage/multistream-tools/)

## Design consideration

- Each page will be designed in a way that you can integrate it into an OBS dock
- Icons are mostly from css.gg

## Install without docker

```
docker-compose up -d database # Optionnal, u can use your local database
symfony composer install
symfony console d:m:m
yarn install
yarn watch
symfony server:ca:install
symfony serve -d
```

## Wallaby.js

[![Wallaby.js](https://img.shields.io/badge/wallaby.js-powered-blue.svg?style=for-the-badge&logo=github)](https://wallabyjs.com/oss/)

This repository contributors are welcome to use
[Wallaby.js OSS License](https://wallabyjs.com/oss/) to get test results immediately as you type, and see the results in
your editor right next to your code.
