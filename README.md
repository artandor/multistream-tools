# Multistream Tools

[![Composer build](https://github.com/artandor/multistream-tools/actions/workflows/symfony.yml/badge.svg)](https://github.com/artandor/multistream-tools/actions/workflows/symfony.yml)
[![Docker build](https://github.com/artandor/multistream-tools/actions/workflows/ci.yml/badge.svg)](https://github.com/artandor/multistream-tools/actions/workflows/ci.yml)

## Description

This project aim to make multi-streaming easier for streamers. We made it because it was painfull to update all titles
from all websites, and we have more ideas for the future.

## Features

- [X] Update title and categories from all platforms
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

## Design consideration

- Each page will be designed in a way that you can integrate it into an OBS dock
- Icons are mostly from css.gg

## Install

```
docker-compose up -d
symfony composer install
symfony console d:m:m
yarn install
yarn watch
symfony serve
```
